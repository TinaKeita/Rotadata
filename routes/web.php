<?php

use App\Http\Controllers\Admin\CostumeController as AdminCostumeController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Member\CostumeController as MemberCostumeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/*
|--------------------------------------------------------------------------
| Publiskie maršruti
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome');

// QR koda skenēšana – dalībnieks apstiprina sevi ar paroli skenēšanas laikā
Route::get('/scan/{code}', [ScanController::class, 'show'])->name('scan.show');
Route::post('/scan/{code}/authenticate', [ScanController::class, 'authenticate'])
    ->middleware('throttle:20,1') // maks. 20 mēģinājumi minūtē no vienas ierīces
    ->name('scan.authenticate');
Route::post('/scan/{code}/assign', [ScanController::class, 'assign'])->name('scan.assign');

// pārņem citam dalībniekam izsniegtu vienību sev (kad tērps fiziski jau nonācis pie skenētāja)
Route::post('/scan/{code}/takeover', [ScanController::class, 'takeover'])->name('scan.takeover');

// QR koda PNG lejupielāde – faila nosaukumā izmanto salasāmo kodu (piem. qr-BRU-01.png)
Route::get('/qr/{code}/download', function ($code) {
    $label = \App\Models\CostumeItem::where('qr_code', $code)->value('code') ?? $code;

    $png = QrCode::format('png')->size(300)->generate(url('/scan/'.$code));

    return response($png)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'attachment; filename="qr-'.$label.'.png"');
})->name('qr.download');

/*
|--------------------------------------------------------------------------
| Autentificēti maršruti (jebkurš pieslēdzies lietotājs)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // skolotāju pāradresē uz admin paneli, studentam – koncerti, gatavība un grupas
    Route::get('/dashboard', [App\Http\Controllers\Member\DashboardController::class, 'index'])->name('dashboard');

    // Obligātā paroles maiņa pēc pieslēgšanās ar pagaidu paroli
    Route::get('/password/change', [App\Http\Controllers\Auth\ForcePasswordController::class, 'edit'])
        ->name('password.change');
    Route::put('/password/change', [App\Http\Controllers\Auth\ForcePasswordController::class, 'update'])
        ->name('password.change.update');

    // Profils
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dalībnieka tērpu inventārs
    Route::get('/members/{group}/costumes', [MemberCostumeController::class, 'index'])
        ->name('members.costumes.index');
    Route::post('/members/costumes/{item}/unassign', [MemberCostumeController::class, 'unassign'])
        ->name('members.costumes.unassign');
    // students pats pamet grupu – konts vienmēr paliek, mainās tikai piederība šai grupai
    Route::post('/members/{group}/leave', [MemberCostumeController::class, 'leave'])
        ->name('members.costumes.leave');
});

/*
|--------------------------------------------------------------------------
| Administratora maršruti (role:admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // meklēšana: tērpu vienības (statuss un vēsture) un studenti
    Route::get('/search', [App\Http\Controllers\Admin\SearchController::class, 'index'])->name('search');

    // Tērpi un to vienības
    Route::post('/costumes/items/{item}/unassign', [AdminCostumeController::class, 'unassign'])
        ->name('costumes.items.unassign');
    // skolotājs pats izsniedz brīvu vienību studentam (bez skenēšanas)
    Route::post('/costumes/items/{item}/assign', [AdminCostumeController::class, 'assign'])
        ->name('costumes.items.assign');
    Route::post('/costumes/items/{item}/regenerate-qr', [AdminCostumeController::class, 'regenerateQr'])
        ->name('costumes.items.regenerate-qr');
    Route::delete('/costumes/items/{item}', [AdminCostumeController::class, 'destroyItem'])
        ->name('costumes.items.destroy');
    Route::post('/costumes/{costume}/items', [AdminCostumeController::class, 'addItems'])
        ->name('costumes.items.add');
    Route::get('/costumes/{costume}/labels', [AdminCostumeController::class, 'labels'])
        ->name('costumes.labels');
    Route::resource('costumes', AdminCostumeController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Dalībnieki (studenti)
    Route::post('/members/{member}/resend-invite', [AdminMemberController::class, 'resendInvite'])
        ->name('members.resend-invite');
    // skolotājs paroli jau iedevis citādi – noņem "uzaicinājums nav piegādāts" brīdinājumu
    Route::post('/members/{member}/dismiss-invite', [AdminMemberController::class, 'dismissInvite'])
        ->name('members.dismiss-invite');
    // skolotājs izsniedz studentam izvēlētā tērpa nākamo brīvo vienību
    Route::post('/members/{user}/hand-out', [AdminMemberController::class, 'handOut'])->name('members.hand-out');
    // viena vai vairāku studentu tērpu komplekta maiņa
    Route::patch('/members/set', [AdminMemberController::class, 'updateSet'])->name('members.set');
    Route::resource('members', AdminMemberController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['members' => 'user']);
    // nesen izņemta dalībnieka atjaunošana vai galīga dzēšana (30 dienu logs, tāpat kā grupām)
    Route::post('/members/{user}/restore', [AdminMemberController::class, 'restore'])
        ->name('members.restore')->withTrashed();
    Route::delete('/members/{user}/force', [AdminMemberController::class, 'forceDestroy'])
        ->name('members.force-destroy')->withTrashed();

    // Koncerti
    Route::resource('events', AdminEventController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Sezonas atskaite (drukājama) – kas vēl nav atdots un kā šosezon izmantoti tērpi
    Route::get('/season-report', [App\Http\Controllers\Admin\SeasonReportController::class, 'show'])
        ->name('season-report.show');

    // Grupas iestatījumi un dzēšana (ar 30 dienu atjaunošanas logu)
    Route::get('/group/settings', [AdminGroupController::class, 'edit'])->name('group.settings');
    // vēl viena grupa (no profila) un pārslēgšanās starp savām grupām (navigācijā)
    Route::post('/groups', [AdminGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}/open', [AdminGroupController::class, 'open'])->name('groups.open');
    Route::patch('/group', [AdminGroupController::class, 'update'])->name('group.update');
    Route::get('/group/delete', [AdminGroupController::class, 'confirm'])->name('group.delete');
    Route::delete('/group', [AdminGroupController::class, 'destroy'])->name('group.destroy');
    Route::post('/group/restore', [AdminGroupController::class, 'restore'])->name('group.restore');

    // Grupas nodošana citam skolotājam: meklēšana, pieprasījums, atcelšana; saņēmējs pieņem vai noraida
    Route::get('/group/transfer/teachers', [App\Http\Controllers\Admin\GroupTransferController::class, 'teachers'])->name('group.transfer.teachers');
    Route::post('/group/transfer', [App\Http\Controllers\Admin\GroupTransferController::class, 'store'])->name('group.transfer.store');
    Route::delete('/group/transfer', [App\Http\Controllers\Admin\GroupTransferController::class, 'cancel'])->name('group.transfer.cancel');
    Route::get('/group/transfer/{token}', [App\Http\Controllers\Admin\GroupTransferController::class, 'show'])->name('group.transfer.show');
    Route::post('/group/transfer/{token}/accept', [App\Http\Controllers\Admin\GroupTransferController::class, 'accept'])->name('group.transfer.accept');
    Route::post('/group/transfer/{token}/decline', [App\Http\Controllers\Admin\GroupTransferController::class, 'decline'])->name('group.transfer.decline');

    // Tērpu komplekti (piem. "Meitenes", "Puiši") – pārvalda grupas iestatījumos
    Route::resource('costume-sets', App\Http\Controllers\Admin\CostumeSetController::class)
        ->only(['store', 'update', 'destroy']);
    Route::delete('/group/force', [AdminGroupController::class, 'forceDestroy'])->name('group.force-destroy');

    // Paziņojumi (piem. students pametis grupu)
    Route::post('/notifications/{notification}/dismiss', [App\Http\Controllers\Admin\NotificationController::class, 'dismiss'])
        ->name('notifications.dismiss');
});

require __DIR__.'/auth.php';
