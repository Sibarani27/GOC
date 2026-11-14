<?php

use App\Livewire\Alerts\Index as AlertsIndex;
use App\Livewire\Assets\Index as AssetsIndex;
use App\Livewire\Dashboard;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Handover\Index as HandoverIndex;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\IpAllowList;
use App\Http\Middleware\SecurityHeaders;
use App\Livewire\Security\Challenge as TwoFactorChallenge;
use App\Livewire\Security\Index as SecurityIndex;
use App\Livewire\Incidents\Index as IncidentsIndex;
use App\Livewire\Incidents\Show as IncidentsShow;
use App\Models\IncidentAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware([
    IpAllowList::class . ':panel',
    'auth',
    EnsureUserIsActive::class,
    EnsureTwoFactor::class,
    SecurityHeaders::class,
])->group(function () {
    Route::get('/dashboard', Dashboard::class)->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/alerts', AlertsIndex::class)->middleware('can:alerts.view')->name('goc.alerts');
    Route::get('/assets', AssetsIndex::class)->middleware('can:assets.view')->name('goc.assets');

    Route::get('/incidents', IncidentsIndex::class)->middleware('can:incidents.view')->name('goc.incidents');
    Route::get('/incidents/{incident}', IncidentsShow::class)->middleware('can:incidents.view')->name('goc.incidents.show');

    Route::get('/incidents/attachments/{attachment}', function (IncidentAttachment $attachment) {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    })->middleware('can:incidents.view')->name('goc.incidents.attachment');

    Route::get('/reports', ReportsIndex::class)->middleware('can:reports.view')->name('goc.reports');
    Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->middleware(['can:reports.export', 'throttle:20,1'])->name('goc.reports.pdf');
    Route::get('/reports/export/xlsx', [ReportExportController::class, 'excel'])->middleware(['can:reports.export', 'throttle:20,1'])->name('goc.reports.xlsx');

    Route::get('/handover', HandoverIndex::class)->middleware('can:handover.manage')->name('goc.handover');
    Route::get('/security', SecurityIndex::class)->name('goc.security');
    Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('goc.2fa.challenge');

    Route::get('/users', UsersIndex::class)->middleware('can:users.manage')->name('goc.users');
    Route::get('/audit', AuditIndex::class)->middleware('can:audit.view')->name('goc.audit');

    Route::post('/goc/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('goc.logout');
});
