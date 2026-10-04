<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Records;
use Throwable;

final class RecordController
{
    public static function index(): string
    {
        Auth::requirePermission('record.view');
        $status = $_GET['status'] ?? '';
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        [$rows, $total] = Records::paginate($status !== '' ? $status : null, $q, $page, $perPage);
        return view('records/index', [
            'title'   => 'Records',
            'rows'    => $rows,
            'total'   => $total,
            'status'  => $status,
            'q'       => $q,
            'page'    => $page,
            'perPage' => $perPage,
        ]);
    }

    public static function show(): string
    {
        Auth::requirePermission('record.view');
        $id = (int) ($_GET['id'] ?? 0);
        $record = Records::find($id);
        if (!$record) {
            http_response_code(404);
            return view('error', ['title' => 'Not found', 'message' => 'Record not found.']);
        }
        return view('records/show', ['title' => 'Record ' . $record['nik'], 'record' => $record]);
    }

    public static function verify(): void
    {
        self::changeStatus('verified', 'record.verify');
    }

    public static function reject(): void
    {
        self::changeStatus('rejected', 'record.reject');
    }

    private static function changeStatus(string $status, string $permission): void
    {
        Auth::requirePermission($permission);
        Csrf::check($_POST['csrf_token'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));
        try {
            Records::setStatus($id, $status, Auth::user()['id'], $note);
            flash('Record marked as ' . $status . '.', 'success');
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
        }
        redirect('/records/show?id=' . $id);
    }

    public static function export(): void
    {
        Auth::requirePermission('record.export');
        $status = $_GET['status'] ?? '';
        $csv = Records::exportCsv($status !== '' ? $status : null);
        $name = 'records-export' . ($status !== '' ? "-{$status}" : '') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
    }
}
