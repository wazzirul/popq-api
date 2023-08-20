<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdministratorsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/administrators/login', [AdministratorsController::class, 'login'])->name('administrators--login');

///////////////////////////////////////////////////////////////////////////////////////////
// GROUP OF JWT TOKEN
///////////////////////////////////////////////////////////////////////////////////////////
Route::group(['middleware' => 'jwt.authentication'], function () {
    // Administrator
    Route::post('/administrators/store', [AdministratorsController::class, 'store'])->name('administrators--store');
    Route::get('/administrators/show', [AdministratorsController::class, 'show'])->name('administrators--show');

    // User
    Route::post('/user/register-login', [UserController::class, 'registerLogin'])->name('users--registerLogin');
    Route::post('/user/edit-user', [UserController::class, 'editUser'])->name('users--editUser');
    Route::get('/user/show', [UserController::class, 'show'])->name('users--show');
    Route::post('/user/showById', [UserController::class, 'showById'])->name('users--showById');

    // Categories
    Route::post('/categories/create-edit', [CategoriesController::class, 'createEdit'])->name('categories--createEdit');
    Route::post('/categories/delete', [CategoriesController::class, 'delete'])->name('categories--delete');
    Route::get('/categories/show', [CategoriesController::class, 'show'])->name('categories--show');
});
///////////////////////////////////////////////////////////////////////////////////////////
// END GROUP OF JTW TOKEN
///////////////////////////////////////////////////////////////////////////////////////////