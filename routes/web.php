<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController; 
use SimpleSoftwareIO\QrCode\Facades\QrCode; 

Route::get('/', function () { return view('welcome');});

// qr code routes
Route::get('/qr/{code}/download', function ($code) {
    $qr = QrCode::format('png')
        ->size(300)
        ->generate(url('/scan/'.$code));

    return response($qr)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'attachment; filename="qr-'.$code.'.png"');
})->name('qr.download');

// Scan routes
Route::get('/scan/{code}', [App\Http\Controllers\ScanController::class, 'show']);
Route::post('/scan/{code}', [App\Http\Controllers\ScanController::class, 'assign']);


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        return view('dashboard');
    })->name('dashboard');
    
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // member costume routes
    Route::get('/members/{group}/costumes', [App\Http\Controllers\Member\CostumeController::class, 'index'])
        ->name('members.costumes.index'); // Available items

    Route::get('/members/{group}/costumes/assigned', [App\Http\Controllers\Member\CostumeController::class, 'assigned'])
        ->name('members.costumes.assigned'); // Assigned to this user

    Route::post('/members/costumes/{item}/unassign', [App\Http\Controllers\Member\CostumeController::class, 'unassign'])
        ->name('members.costumes.unassign'); // Unassign self

});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');
    Route::resource('costumes', App\Http\Controllers\Admin\CostumeController::class);
    Route::post('/costumes/items/{item}/unassign', [App\Http\Controllers\Admin\CostumeController::class, 'unassign'])->name('costumes.items.unassign');
    
    // members
    Route::get('/members/create', [App\Http\Controllers\Admin\MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [App\Http\Controllers\Admin\MemberController::class, 'store'])->name('members.store');
    Route::get('/members', [App\Http\Controllers\Admin\MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{user}', [App\Http\Controllers\Admin\MemberController::class, 'show'])->name('members.show');
    Route::delete('/members/{user}', [App\Http\Controllers\Admin\MemberController::class, 'destroy'])->name('members.destroy');

});


require __DIR__.'/auth.php';
