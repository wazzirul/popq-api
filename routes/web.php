<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

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


Route::get('/', [DocumentationController::class, 'login'])->name('login');
Route::post('/login-process', [DocumentationController::class, 'loginProcess'])->name('login-process');
Route::get('/logout', [DocumentationController::class, 'logout'])->name('logout');