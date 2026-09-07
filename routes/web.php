<?php

use App\Http\Controllers\Admin\CostumeController as AdminCostumeController;
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
Route::post('/scan/{code}/authenticate', [ScanController::class, 'authenticate'])->name('scan.authenticate');
Route::post('/scan/{code}/assign', [ScanController::class, 'assign'])->name('scan.assign');

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
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

    // Tērpi un to vienības
    Route::post('/costumes/items/{item}/unassign', [AdminCostumeController::class, 'unassign'])
        ->name('costumes.items.unassign');
    Route::post('/costumes/items/{item}/regenerate-qr', [AdminCostumeController::class, 'regenerateQr'])
        ->name('costumes.items.regenerate-qr');
    Route::get('/costumes/{costume}/labels', [AdminCostumeController::class, 'labels'])
        ->name('costumes.labels');
    Route::resource('costumes', AdminCostumeController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    // Dalībnieki (studenti)
    Route::resource('members', AdminMemberController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['members' => 'user']);
});

require __DIR__.'/auth.php';
