<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CVFileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\IDCardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/cv', [CVFileController::class, 'index'])->name('cv.index');
    Route::post('/cv', [CVFileController::class, 'store'])->name('cv.upload');
    Route::get('/cv/download', [CVFileController::class, 'download'])->name('cv.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/idcard', [IDCardController::class, 'index'])->name('idcard.index');
    Route::post('/idcard', [IDCardController::class, 'store'])->name('idcard.upload');
    Route::get('/idcard/download', [IDCardController::class, 'download'])->name('idcard.download');

    Route::get('/video', [VideoController::class, 'index'])->name('video.index');
});

require __DIR__ . '/auth.php';
