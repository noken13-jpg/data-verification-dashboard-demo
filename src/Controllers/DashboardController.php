<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Audit;
use App\Records;

final class DashboardController
{
    public static function index(): string
    {
        Auth::requireLogin();
        return view('dashboard', [
            'title'  => 'Dashboard',
            'counts' => Records::counts(),
            'audit'  => Auth::can('audit.view') ? Audit::recent(10) : [],
        ]);
    }
}
