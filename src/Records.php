<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

/**
 * Core model for resident records: querying, status counts, the
 * verify/reject workflow, CSV import (with duplicate prevention and
 * validation) and CSV export.
 */
final class Records
{
    private const EXPECTED_HEADERS = ['nik', 'full_name', 'address', 'category'];

    /** @return array{0: array, 1: int} rows and the total matching count */
    public static function paginate(?string $status, string $q, int $page, int $perPage = 20): array
    {
        $where = [];
        $params = [];
        if ($status !== null && $status !== '') {
            $where[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        if ($q !== '') {
            $where[] = '(r.nik LIKE :q OR r.full_name LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $pdo = Database::connection();
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM records r {$clause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT r.*, u.name AS author
               FROM records r JOIN users u ON u.id = r.created_by
               {$clause}
              ORDER BY r.id DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return [$stmt->fetchAll(), $total];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, u.name AS author FROM records r
               JOIN users u ON u.id = r.created_by WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function counts(): array
    {
        $rows = Database::connection()
            ->query('SELECT status, COUNT(*) c FROM records GROUP BY status')
            ->fetchAll();
        $out = ['pending' => 0, 'verified' => 0, 'rejected' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['c'];
            $out['total'] += (int) $row['c'];
        }
        return $out;
    }

    public static function setStatus(int $id, string $status, int $userId, string $note = ''): void
    {
        if (!in_array($status, ['verified', 'rejected'], true)) {
            throw new RuntimeException('Invalid target status.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT status FROM records WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $cur = $stmt->fetchColumn();
            if ($cur === false) {
                throw new RuntimeException('Record not found.');
            }
            $pdo->prepare(
                'UPDATE records SET status = :s, note = :n, verified_by = :u, verified_at = NOW()
                  WHERE id = :id'
            )->execute([':s' => $status, ':n' => ($note !== '' ? $note : null), ':u' => $userId, ':id' => $id]);
            Audit::log($userId, $status === 'verified' ? 'verify' : 'reject', 'record', $id, $note, $pdo);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Validate a single NIK: exactly 16 digits. */
    public static function isValidNik(string $nik): bool
    {
        return (bool) preg_match('/^\d{16}$/', $nik);
    }

    /**
     * Import a CSV file. Returns a summary:
     *   [total, inserted, duplicates, invalid, errors[]]
     * Duplicate prevention works on three levels:
     *   - duplicates already in the database (unique index on nik),
     *   - duplicates appearing twice in the same file,
     *   - a final INSERT ... guarded by the unique constraint.
     */
    public static function importCsv(string $path, string $filename, int $userId): array
    {
        $maxRows = (int) env('IMPORT_MAX_ROWS', '5000');
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open the uploaded file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException('The CSV file is empty.');
        }
        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);
        $missing = array_diff(self::EXPECTED_HEADERS, $header);
        if ($missing) {
            fclose($handle);
            throw new RuntimeException('Missing CSV columns: ' . implode(', ', $missing));
        }
        $idx = array_flip($header);

        $total = $inserted = $duplicates = $invalid = 0;
        $errors = [];
        $seen = [];

        $pdo = Database::connection();
        $insert = $pdo->prepare(
            'INSERT INTO records (nik, full_name, address, category, status, source, created_by)
             VALUES (:nik, :name, :addr, :cat, "pending", :src, :uid)'
        );
        $existsStmt = $pdo->prepare('SELECT 1 FROM records WHERE nik = :nik LIMIT 1');

        $pdo->beginTransaction();
        try {
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($total >= $maxRows) {
                    $errors[] = "Row {$line}: import stopped at IMPORT_MAX_ROWS ({$maxRows}).";
                    break;
                }
                // Skip completely blank lines.
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }
                $total++;

                $nik  = trim((string) ($row[$idx['nik']] ?? ''));
                $name = trim((string) ($row[$idx['full_name']] ?? ''));
                $addr = trim((string) ($row[$idx['address']] ?? ''));
                $cat  = trim((string) ($row[$idx['category']] ?? ''));

                if (!self::isValidNik($nik) || $name === '') {
                    $invalid++;
                    $errors[] = "Row {$line}: invalid (NIK must be 16 digits and name is required).";
                    continue;
                }
                if (isset($seen[$nik])) {
                    $duplicates++;
                    $errors[] = "Row {$line}: duplicate NIK within file ({$nik}).";
                    continue;
                }
                $seen[$nik] = true;

                $existsStmt->execute([':nik' => $nik]);
                if ($existsStmt->fetchColumn()) {
                    $duplicates++;
                    $errors[] = "Row {$line}: NIK already exists in database ({$nik}).";
                    continue;
                }

                try {
                    $insert->execute([
                        ':nik' => $nik, ':name' => mb_substr($name, 0, 160),
                        ':addr' => ($addr !== '' ? mb_substr($addr, 0, 255) : null),
                        ':cat' => ($cat !== '' ? mb_substr($cat, 0, 60) : null),
                        ':src' => mb_substr($filename, 0, 120), ':uid' => $userId,
                    ]);
                    $inserted++;
                } catch (\PDOException $e) {
                    // Unique-constraint backstop (race-safe duplicate prevention).
                    if ($e->getCode() === '23000') {
                        $duplicates++;
                        $errors[] = "Row {$line}: duplicate NIK rejected by database ({$nik}).";
                    } else {
                        throw $e;
                    }
                }
            }

            $pdo->prepare(
                'INSERT INTO import_batches (filename, user_id, total_rows, inserted, duplicates, invalid)
                 VALUES (:f, :u, :t, :i, :d, :v)'
            )->execute([
                ':f' => mb_substr($filename, 0, 255), ':u' => $userId,
                ':t' => $total, ':i' => $inserted, ':d' => $duplicates, ':v' => $invalid,
            ]);
            $batchId = (int) $pdo->lastInsertId();
            Audit::log(
                $userId, 'import', 'import_batch', $batchId,
                "file={$filename} total={$total} inserted={$inserted} duplicates={$duplicates} invalid={$invalid}",
                $pdo
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            fclose($handle);
            throw $e;
        }
        fclose($handle);

        return [
            'total' => $total, 'inserted' => $inserted,
            'duplicates' => $duplicates, 'invalid' => $invalid, 'errors' => $errors,
        ];
    }

    /** Stream all records (optionally filtered by status) as CSV text. */
    public static function exportCsv(?string $status): string
    {
        $params = [];
        $clause = '';
        if ($status !== null && $status !== '') {
            $clause = 'WHERE status = :s';
            $params[':s'] = $status;
        }
        $stmt = Database::connection()->prepare(
            "SELECT nik, full_name, address, category, status, created_at FROM records {$clause} ORDER BY id"
        );
        $stmt->execute($params);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['nik', 'full_name', 'address', 'category', 'status', 'created_at']);
        foreach ($stmt as $r) {
            fputcsv($out, [$r['nik'], $r['full_name'], $r['address'], $r['category'], $r['status'], $r['created_at']]);
        }
        rewind($out);
        return (string) stream_get_contents($out);
    }
}
