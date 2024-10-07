<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdministratorsController;
use App\Http\Controllers\AdministratorsDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserQuizController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionsController;
use App\Http\Controllers\QuizListChoiceController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollListChoiceController;
use App\Http\Controllers\PollV2Controller;
use App\Http\Controllers\QuizV2Controller;
use App\Http\Controllers\SearchV2Controller;
use App\Http\Controllers\UserPollController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\StatisticV2Controller;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OthersController;
use App\Http\Controllers\QwizardController;

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

// Others
Route::post('/others/sitemap-quiz', [OthersController::class, 'sitemapQuiz'])->name('others--sitemap-quiz');
Route::post('/others/sitemap-poll', [OthersController::class, 'sitemapPoll'])->name('others--sitemap-poll');


////////
// V2 //
////////
Route::prefix('v2')->group(function () {
    // Statistics
    Route::post('/statistics/showScoresUser', [StatisticV2Controller::class, 'showScoresUser'])->name('v2-statistics--showScoresUser');
    Route::post('/statistics/showCompletedQuiz', [StatisticV2Controller::class, 'showCompletedQuiz'])->name('v2-statistics--showCompletedQuiz');
    Route::post('/statistics/showCompletedPoll', [StatisticV2Controller::class, 'showCompletedPoll'])->name('v2-statistics--showCompletedPoll');

    // Search
    Route::get('/search/showFilter', [SearchV2Controller::class, 'showFilter'])->name('v2-search--showFilter');
    Route::post('/search', [SearchV2Controller::class, 'search'])->name('v2-search');
    Route::post('/search/quiz', [SearchV2Controller::class, 'quiz'])->name('v2-search--quiz');
    Route::post('/search/poll', [SearchV2Controller::class, 'poll'])->name('v2-search--poll');
    Route::post('/search/category', [SearchV2Controller::class, 'category'])->name('v2-search--category');

    // Quiz
    Route::post('/quiz/showById', [QuizV2Controller::class, 'showById'])->name('v2-quiz--showById');

    // Poll
    Route::post('/poll/showById', [PollV2Controller::class, 'showById'])->name('v2-poll--showById');
});



///////////////////////////////////////////////////////////////////////////////////////////
// GROUP OF JWT TOKEN
///////////////////////////////////////////////////////////////////////////////////////////
Route::group(['middleware' => 'jwt.authentication'], function () {
    // Administrator
    Route::post('/administrators/store', [AdministratorsController::class, 'store'])->name('administrators--store');
    Route::post('/administrators/show', [AdministratorsController::class, 'show'])->name('administrators--show');

    // Administrator Dashboard
    Route::get('/administrators-dashboard/user', [AdministratorsDashboardController::class, 'user'])->name('administrators-dashboard--user');

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
    Route::post('/quiz/importQuiz', [QuizController::class, 'importQuiz'])->name('quiz--importQuiz');
    Route::post('/quiz/importImagesQuiz', [QuizController::class, 'importImagesQuiz'])->name('quiz--importImagesQuiz');
    Route::post('/quiz/showUserLoginQuiz', [QuizController::class, 'showUserLoginQuiz'])->name('quiz--showUserLoginQuiz');
    Route::post('/quiz/set-featured', [QuizController::class, 'setFeatured'])->name('quiz--setFeatured');

    // Quiz
    Route::post('/qwizard/store', [QwizardController::class, 'store'])->name('qwizard--store');
    Route::post('/qwizard/myQwizard', [QwizardController::class, 'myQwizard'])->name('qwizard--myQwizard');
    Route::post('/qwizard/show', [QwizardController::class, 'show'])->name('qwizard--show');

    // Quiz Questions
    Route::post('/quiz-question/create-edit', [QuizQuestionsController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::post('/quiz-question/create-edit-v2', [QuizQuestionsController::class, 'createEditV2'])->name('quiz-question--createEditV2');

    // Quiz List Choice
    Route::post('/quiz-list-choice/create-edit', [QuizListChoiceController::class, 'createEdit'])->name('quiz-question--createEdit');
    Route::post('/quiz-list-choice/create-edit-v2', [QuizListChoiceController::class, 'createEditV2'])->name('quiz-question--createEditV2');

    // Poll
    Route::post('/poll/create-edit', [PollController::class, 'createEdit'])->name('poll--createEdit');
    Route::post('/poll/importPoll', [PollController::class, 'importPoll'])->name('poll--importPoll');
    Route::post('/poll/importImagesPoll', [PollController::class, 'importImagesPoll'])->name('poll--importImagesPoll');
    Route::post('/poll/showUserLoginPoll', [PollController::class, 'showUserLoginPoll'])->name('poll--showUserLoginPoll');
    Route::post('/poll/set-featured', [PollController::class, 'setFeatured'])->name('poll--setFeatured');

    // Imports
    Route::post('/import/importTriviaPoll', [ImportController::class, 'importTriviaPoll'])->name('import--importTriviaPoll');
    Route::post('/import/importImages', [ImportController::class, 'importImages'])->name('import--importImages');

    // Poll List Choice
    Route::post('/poll-list-choice/create-edit', [PollListChoiceController::class, 'createEdit'])->name('poll-question--createEdit');
    Route::post('/poll-list-choice/create-edit-v2', [PollListChoiceController::class, 'createEditV2'])->name('poll-question--createEditV2');

    // User Poll
    Route::post('/user-poll/create-edit', [UserPollController::class, 'createEdit'])->name('user-poll--createEdit');
    Route::post('/user-poll/show', [UserPollController::class, 'show'])->name('user-poll--show');
    Route::post('/user-poll/showByIdPoll', [UserPollController::class, 'showByIdPoll'])->name('user-poll--showByIdPoll');

    ////////
    // V2 //
    ////////
    Route::prefix('v2')->group(function () {
        // Route for V2 with JWT
    });
});
///////////////////////////////////////////////////////////////////////////////////////////
// END GROUP OF JTW TOKEN
///////////////////////////////////////////////////////////////////////////////////////////