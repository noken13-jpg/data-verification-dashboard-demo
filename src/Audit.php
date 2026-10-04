<?php

declare(strict_types=1);

namespace App;

use PDO;

/** Writes immutable audit-log entries. */
final class Audit
{
    public static function log(
        int $userId,
        string $action,
        string $entity,
        ?int $entityId = null,
        ?string $detail = null,
        ?PDO $pdo = null
    ): void {
        ($pdo ?? Database::connection())->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, detail)
             VALUES (:u, :a, :e, :id, :d)'
        )->execute([
            ':u'  => $userId,
            ':a'  => $action,
            ':e'  => $entity,
            ':id' => $entityId,
            ':d'  => $detail !== null ? mb_substr($detail, 0, 500) : null,
        ]);
    }

    public static function recent(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.name AS actor
               FROM audit_log a JOIN users u ON u.id = a.user_id
              ORDER BY a.id DESC LIMIT ' . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
