<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdministratorsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserQuizController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionsController;
use App\Http\Controllers\QuizListChoiceController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollListChoiceController;

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

    // User Quiz
    Route::post('/user-quiz/create-edit', [UserQuizController::class, 'createEdit'])->name('user-quiz--createEdit');
    Route::post('/user-quiz/show', [UserQuizController::class, 'show'])->name('user-quiz--show');
    Route::post('/user-quiz/showByIdQuiz', [UserQuizController::class, 'showByIdQuiz'])->name('user-quiz--showByIdQuiz');

    // Categories
    Route::post('/categories/create-edit', [CategoriesController::class, 'createEdit'])->name('categories--createEdit');
    Route::post('/categories/delete', [CategoriesController::class, 'delete'])->name('categories--delete');
    Route::get('/categories/show', [CategoriesController::class, 'show'])->name('categories--show');

    // Quiz
    Route::post('/quiz/create-edit', [QuizController::class, 'createEdit'])->name('quiz--createEdit');
    Route::get('/quiz/show', [QuizController::class, 'show'])->name('quiz--show');
    Route::post('/quiz/showByCategories', [QuizController::class, 'showByCategories'])->name('quiz--showByCategories');
    Route::post('/quiz/showById', [QuizController::class, 'showById'])->name('quiz--showById');
    Route::post('/quiz/showTodayQuiz', [QuizController::class, 'showTodayQuiz'])->name('quiz--showTodayQuiz');
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

    // Poll
    Route::post('/poll/create-edit', [PollController::class, 'createEdit'])->name('poll--createEdit');
    Route::get('/poll/show', [PollController::class, 'show'])->name('poll--show');
    Route::post('/poll/showByCategories', [PollController::class, 'showByCategories'])->name('poll--showByCategories');
    Route::post('/poll/showById', [PollController::class, 'showById'])->name('poll--showById');
    Route::post('/poll/showTodayPoll', [PollController::class, 'showTodayPoll'])->name('poll--showTodayPoll');
    Route::post('/poll/showDraftPoll', [PollController::class, 'showDraftPoll'])->name('poll--showDraftPoll');
});
///////////////////////////////////////////////////////////////////////////////////////////
// END GROUP OF JTW TOKEN
///////////////////////////////////////////////////////////////////////////////////////////