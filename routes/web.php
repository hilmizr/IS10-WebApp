<?php

use App\Http\Controllers\Auth\CompanyRegisteredUserController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CVFileController;
use App\Http\Controllers\JobController;
use Faker\Provider\ar_EG\Company;
use Illuminate\Support\Facades\Auth;
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
    $auth = Auth::user();
    return view('dashboard')->with('auth', $auth);
})->middleware(['auth'])->name('dashboard');

Route::middleware('company.auth')->group(function () {
    Route::get('/company-dashboard', [CompanyController::class, 'dashboard'])
                ->name('company-dashboard');
    Route::get('/company-profile', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::patch('/company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
    Route::delete('/company-profile', [CompanyProfileController::class, 'destroy'])->name('company-profile.destroy');
    Route::put('company-password', [PasswordController::class, 'update'])->name('company-password.update');
    Route::get('/company-job', [JobController::class, 'company_index'])->name('company-job.index');
    Route::get('/company-logout', [CompanyRegisteredUserController::class, 'logout'])
                ->name('company-logout');
    Route::get('/create-job', [JobController::class, 'create'])->name('create-job');
    Route::post('/create-job', [JobController::class, 'store']);
    Route::get('/edit-job/{id}', [JobController::class, 'edit'])->name('edit-job');
    Route::post('/edit-job/{id}', [JobController::class, 'update']);
    Route::delete('/delete-job/{id}', [JobController::class, 'destroy'])->name('delete-job');
    Route::get('/appliers/{id}', [JobController::class, 'appliers'])->name('appliers');

});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'company.auth'])->name('dashboard');

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

    Route::get('/job', [JobController::class, 'index'])->name('job.index');
    Route::get('/video', [VideoController::class, 'index'])->name('video.index');
    Route::get('/apply/{id}', [JobController::class, 'apply'])->name('apply-job');

});

Route::middleware('guest')->group(function () {
    Route::get('company-register', [CompanyRegisteredUserController::class, 'create'])
                ->name('company-register');
    Route::post('company-register', [CompanyRegisteredUserController::class, 'store']);
    Route::get('company-login', [CompanyRegisteredUserController::class, 'login'])
                ->name('company-login');
    Route::post('company-login', [CompanyRegisteredUserController::class, 'login_store']);
});



require __DIR__ . '/auth.php';
