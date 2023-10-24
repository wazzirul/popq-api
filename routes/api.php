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
use App\Http\Controllers\UserPollController;
use App\Http\Controllers\StatisticController;

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
Route::post('/user/login', [UserController::class, 'login'])->name('users--Login');

// Categories
Route::get('/categories/show', [CategoriesController::class, 'show'])->name('categories--show');
Route::post('/categories/showByID', [CategoriesController::class, 'showByID'])->name('categories--showByID');

// Quiz
Route::post('/quiz/show', [QuizController::class, 'show'])->name('quiz--show');
Route::post('/quiz/showByCategories', [QuizController::class, 'showByCategories'])->name('quiz--showByCategories');
Route::post('/quiz/showById', [QuizController::class, 'showById'])->name('quiz--showById');
Route::post('/quiz/showTodayQuiz', [QuizController::class, 'showTodayQuiz'])->name('quiz--showTodayQuiz');
Route::post('/quiz/showDraftQuiz', [QuizController::class, 'showDraftQuiz'])->name('quiz--showDraftQuiz');

// Quiz Questions
Route::get('/quiz-question/show', [QuizQuestionsController::class, 'show'])->name('quiz-question--show');
Route::post('/quiz-question/showByQuiz', [QuizQuestionsController::class, 'showByQuiz'])->name('quiz-question--showByQuiz');
Route::post('/quiz-question/showByID', [QuizQuestionsController::class, 'showByID'])->name('quiz-question--showByID');

// Quiz List Choice
Route::get('/quiz-list-choice/show', [QuizListChoiceController::class, 'show'])->name('quiz-question--show');
Route::post('/quiz-list-choice/showByQuiz', [QuizListChoiceController::class, 'showByQuiz'])->name('quiz-question--showByQuiz');
Route::post('/quiz-list-choice/showByQuizQuestions', [QuizListChoiceController::class, 'showByQuizQuestions'])->name('quiz-question--showByQuizQuestions');

// Poll
Route::post('/poll/show', [PollController::class, 'show'])->name('poll--show');
Route::post('/poll/showByCategories', [PollController::class, 'showByCategories'])->name('poll--showByCategories');
Route::post('/poll/showById', [PollController::class, 'showById'])->name('poll--showById');
Route::post('/poll/showTodayPoll', [PollController::class, 'showTodayPoll'])->name('poll--showTodayPoll');
Route::post('/poll/showDraftPoll', [PollController::class, 'showDraftPoll'])->name('poll--showDraftPoll');

// Poll List Choice
Route::post('/poll-list-choice/showByPoll', [PollListChoiceController::class, 'showByPoll'])->name('poll-question--showByPoll');
Route::post('/poll-list-choice/showById', [PollListChoiceController::class, 'showByPoll'])->name('poll-question--showById');

// Statistics
Route::post('/statistics/showUser', [StatisticController::class, 'showUser'])->name('statistics--showUser');
Route::post('/statistics/showQuiz', [StatisticController::class, 'showQuiz'])->name('statistics--showQuiz');
Route::post('/statistics/showQuizByCategories', [StatisticController::class, 'showQuizByCategories'])->name('statistics--showQuizByCategories');
Route::get('/statistics/countTotalQuizzesAndPolls', [StatisticController::class, 'countTotalQuizzesAndPolls'])->name('statistics--countTotalQuizzesAndPolls');
Route::post('/statistics/showScoresUser', [StatisticController::class, 'showScoresUser'])->name('statistics--showScoresUser');

///////////////////////////////////////////////////////////////////////////////////////////
// GROUP OF JWT TOKEN
///////////////////////////////////////////////////////////////////////////////////////////
Route::group(['middleware' => 'jwt.authentication'], function () {
    // Administrator
    Route::post('/administrators/store', [AdministratorsController::class, 'store'])->name('administrators--store');
    Route::get('/administrators/show', [AdministratorsController::class, 'show'])->name('administrators--show');

    // User
    Route::post('/user/edit-user', [UserController::class, 'editUser'])->name('users--editUser');
    Route::post('/user/show', [UserController::class, 'show'])->name('users--show');
    Route::post('/user/showById', [UserController::class, 'showById'])->name('users--showById');

    // User Quiz
    Route::post('/user-quiz/create-edit', [UserQuizController::class, 'createEdit'])->name('user-quiz--createEdit');
    Route::post('/user-quiz/show', [UserQuizController::class, 'show'])->name('user-quiz--show');
    Route::post('/user-quiz/showByIdQuiz', [UserQuizController::class, 'showByIdQuiz'])->name('user-quiz--showByIdQuiz');

    // Categories
    Route::post('/categories/create-edit', [CategoriesController::class, 'createEdit'])->name('categories--createEdit');
    Route::post('/categories/delete', [CategoriesController::class, 'delete'])->name('categories--delete');

    // Quiz
    Route::post('/quiz/create-edit', [QuizController::class, 'createEdit'])->name('quiz--createEdit');
    Route::post('/quiz/showUserLoginQuiz', [QuizController::class, 'showUserLoginQuiz'])->name('quiz--showUserLoginQuiz');

    // Quiz Questions
    Route::post('/quiz-question/create-edit', [QuizQuestionsController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::post('/quiz-question/create-edit-v2', [QuizQuestionsController::class, 'createEditV2'])->name('quiz-question--createEditV2');

    // Quiz List Choice
    Route::post('/quiz-list-choice/create-edit', [QuizListChoiceController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::post('/quiz-list-choice/create-edit-v2', [QuizListChoiceController::class, 'createEditV2'])->name('quiz-question--createEditV2');

    // Poll
    Route::post('/poll/create-edit', [PollController::class, 'createEdit'])->name('poll--createEdit');
    Route::post('/poll/importPoll', [PollController::class, 'importPoll'])->name('poll--importPoll');
    Route::post('/poll/showUserLoginPoll', [PollController::class, 'showUserLoginPoll'])->name('poll--showUserLoginPoll');

    // Poll List Choice
    Route::post('/poll-list-choice/create-edit', [PollListChoiceController::class, 'createEdit'])->name('poll-question--createEdit');
    Route::post('/poll-list-choice/create-edit-v2', [PollListChoiceController::class, 'createEditV2'])->name('poll-question--createEditV2');

    // User Poll
    Route::post('/user-poll/create-edit', [UserPollController::class, 'createEdit'])->name('user-poll--createEdit');
    Route::post('/user-poll/show', [UserPollController::class, 'show'])->name('user-poll--show');
    Route::post('/user-poll/showByIdPoll', [UserPollController::class, 'showByIdPoll'])->name('user-poll--showByIdPoll');
});
///////////////////////////////////////////////////////////////////////////////////////////
// END GROUP OF JTW TOKEN
///////////////////////////////////////////////////////////////////////////////////////////