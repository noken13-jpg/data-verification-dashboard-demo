<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Records;
use Throwable;

final class ImportController
{
    public static function form(): string
    {
        Auth::requirePermission('record.import');
        return view('import/form', ['title' => 'Import CSV']);
    }

    public static function upload(): string
    {
        Auth::requirePermission('record.import');
        Csrf::check($_POST['csrf_token'] ?? null);

        $file = $_FILES['csv'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('Please choose a CSV file to upload.', 'error');
            redirect('/import');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash('Only .csv files are accepted.', 'error');
            redirect('/import');
        }

        try {
            $summary = Records::importCsv($file['tmp_name'], (string) $file['name'], Auth::user()['id']);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('/import');
        }

        return view('import/result', [
            'title'    => 'Import result',
            'summary'  => $summary,
            'filename' => (string) $file['name'],
        ]);
    }
}
