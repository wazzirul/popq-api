<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdministratorsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionsController;
use App\Http\Controllers\QuizListChoiceController;

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

    // Quiz
    Route::post('/quiz/create-edit', [QuizController::class, 'createEdit'])->name('quiz--createEdit');
    Route::get('/quiz/show', [QuizController::class, 'show'])->name('quiz--show');
    Route::post('/quiz/showByCategories', [QuizController::class, 'showByCategories'])->name('quiz--showByCategories');
    Route::post('/quiz/showById', [QuizController::class, 'showById'])->name('quiz--showById');
    Route::post('/quiz/showDraftQuiz', [QuizController::class, 'showDraftQuiz'])->name('quiz--showDraftQuiz');

    // Quiz Questions
    Route::post('/quiz-question/create-edit', [QuizQuestionsController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::get('/quiz-question/show', [QuizQuestionsController::class, 'show'])->name('quiz-question--show');
    Route::post('/quiz-question/showByQuiz', [QuizQuestionsController::class, 'showByQuiz'])->name('quiz-question--showByQuiz');
    Route::post('/quiz-question/showByID', [QuizQuestionsController::class, 'showByID'])->name('quiz-question--showByID');

    // Quiz List Choice
    Route::post('/quiz-list-choice/create-edit', [QuizListChoiceController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::get('/quiz-list-choice/show', [QuizListChoiceController::class, 'show'])->name('quiz-question--show');
    Route::post('/quiz-list-choice/showByQuiz', [QuizListChoiceController::class, 'showByQuiz'])->name('quiz-question--showByQuiz');
    Route::post('/quiz-list-choice/showByQuizQuestions', [QuizListChoiceController::class, 'showByQuizQuestions'])->name('quiz-question--showByQuizQuestions');
});
///////////////////////////////////////////////////////////////////////////////////////////
// END GROUP OF JTW TOKEN
///////////////////////////////////////////////////////////////////////////////////////////