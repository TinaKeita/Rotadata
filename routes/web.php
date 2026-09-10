<?php

use App\Http\Controllers\Admin\CostumeController as AdminCostumeController;
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
    Route::get('/dashboard', function () {
        return auth()->user()->hasRole('admin')
            ? redirect()->route('admin.dashboard')
            : view('dashboard');
    })->name('dashboard');

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
});

/*
|--------------------------------------------------------------------------
| Administratora maršruti (role:admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Tērpi un to vienības
    Route::post('/costumes/items/{item}/unassign', [AdminCostumeController::class, 'unassign'])
        ->name('costumes.items.unassign');
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
    Route::resource('members', AdminMemberController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['members' => 'user']);

    // Grupas iestatījumi un dzēšana (ar 30 dienu atjaunošanas logu)
    Route::get('/group/settings', [AdminGroupController::class, 'edit'])->name('group.settings');
    Route::patch('/group', [AdminGroupController::class, 'update'])->name('group.update');
    Route::get('/group/delete', [AdminGroupController::class, 'confirm'])->name('group.delete');
    Route::delete('/group', [AdminGroupController::class, 'destroy'])->name('group.destroy');
    Route::post('/group/restore', [AdminGroupController::class, 'restore'])->name('group.restore');
    Route::delete('/group/force', [AdminGroupController::class, 'forceDestroy'])->name('group.force-destroy');
});

require __DIR__.'/auth.php';
