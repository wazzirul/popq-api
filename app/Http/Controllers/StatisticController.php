<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\UserPoll;
use App\Models\Users;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;

class StatisticController extends Controller
{

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/statistics/showUser",
     *      operationId="statisticsShowUser",
     *      tags={"Statistics"},
     *      summary="Show BY Statistics User Data",
     *      description="Returns User today, this month, permonth (january - december), and this year Data based on year",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>year</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="year", type="string", example="2023"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server Error"
     *      )
     * )
     */
    public function showUser(Request $request)
    {
        $requestData = $request->only([
            'year',
        ]);
        $currentDate = now();
        $year = $requestData['year'] ?? $currentDate->year;

        // Get statistics for today
        $todayCount = Users::whereDate('created_at', $currentDate)->count();

        // Get statistics for this month
        $thisMonthCount = Users::whereYear('created_at', $year)
            ->whereMonth('created_at', $currentDate->month)
            ->count();

        // Get statistics for each month of the given year
        $monthlyCounts = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyCounts[$month] = Users::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
        }

        // Get statistics for this year
        $thisYearCount = Users::whereYear('created_at', $year)->count();
        $totalCount = Users::count();

        // Calculate percentages
        $percentageToday = $totalCount > 0 ? ($todayCount / $totalCount) * 100 : 0;
        $percentageThisMonth = $totalCount > 0 ? ($thisMonthCount / $totalCount) * 100 : 0;
        $percentageThisYear = $totalCount > 0 ? ($thisYearCount / $totalCount) * 100 : 0;
        // Calculate percentages for each month
        $percentagePerMonth = [];
        foreach ($monthlyCounts as $month => $count) {
            $percentagePerMonth[$month] = $totalCount > 0 ? ($count / $totalCount) * 100 : 0;
        }

        $data = [
            'today' => $todayCount,
            'today_percentage' => $percentageToday,
            'this_month' => $thisMonthCount,
            'this_month_percentage' => $percentageThisMonth,
            'per_month' => $monthlyCounts,
            'per_month_percentage' => $percentagePerMonth,
            'this_year' => $thisYearCount,
            'this_year_percentage' => $percentageThisYear,
            'total' => $totalCount,
        ];

        return ResultHelpers::success($data, 200, true);
    }

    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/statistics/showQuiz",
     *      operationId="statisticsShowQuiz",
     *      tags={"Statistics"},
     *      summary="Show BY Statistics User Data",
     *      description="Returns User today, this month, permonth (january - december), and this year Data based on year",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>year</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="year", type="string", example="2023"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server Error"
     *      )
     * )
     */
    public function showQuiz(Request $request)
    {
        $requestData = $request->only([
            'year',
        ]);
        $currentDate = now();
        $year = $requestData['year'] ?? $currentDate->year;

        // Get statistics for today
        $todayCount = UserQuiz::distinct('id_quiz')->whereDate('created_at', $currentDate)->count('id_quiz');

        // Get statistics for this month
        $thisMonthCount = UserQuiz::distinct('id_quiz')->whereYear('created_at', $year)
            ->whereMonth('created_at', $currentDate->month)
            ->count('id_quiz');

        // Get statistics for each month of the given year
        $monthlyCounts = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyCounts[$month] = UserQuiz::distinct('id_quiz')->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count('id_quiz');
        }

        // Get statistics for this year
        $thisYearCount = UserQuiz::distinct('id_quiz')->whereYear('created_at', $year)->count('id_quiz');

        $data = [
            'today' => $todayCount,
            'this_month' => $thisMonthCount,
            'per_month' => $monthlyCounts,
            'this_year' => $thisYearCount,
        ];

        // Get all categories
        $categories = Categories::all();

        // Initialize an array to hold category statistics
        $categoryStats = [];

        foreach ($categories as $category) {
            $categoryName = $category->name_category;

            // Get statistics for today
            $todayCount = UserQuiz::distinct('id_quiz')
                ->whereDate('created_at', $currentDate)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            // Get statistics for this month
            $thisMonthCount = UserQuiz::distinct('id_quiz')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $currentDate->month)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            // Get statistics for each month of the given year
            $monthlyCounts = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyCounts[$month] = UserQuiz::distinct('id_quiz')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->whereHas('Quiz', function ($query) use ($category) {
                        $query->where('id_categories', $category->id_categories);
                    })
                    ->count('id_quiz');
            }

            // Get statistics for this year
            $thisYearCount = UserQuiz::distinct('id_quiz')
                ->whereYear('created_at', $year)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            $categoryStats[$categoryName] = [
                'today' => $todayCount,
                'this_month' => $thisMonthCount,
                'per_month' => $monthlyCounts,
                'this_year' => $thisYearCount,
            ];
        }

        return ResultHelpers::success($data, 200, true);
    }

    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/statistics/showQuizByCategories",
     *      operationId="statisticsShowQuizByCategories",
     *      tags={"Statistics"},
     *      summary="Show BY Statistics User Data",
     *      description="Returns User today, this month, permonth (january - december), and this year Data based on year",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>year</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="year", type="string", example="2023"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server Error"
     *      )
     * )
     */
    public function showQuizByCategories(Request $request)
    {
        $requestData = $request->only([
            'year',
        ]);
        $currentDate = now();
        $year = $requestData['year'] ?? $currentDate->year;

        // Get all categories
        $categories = Categories::all();

        // Initialize an array to hold category statistics
        $categoryStats = [];

        foreach ($categories as $category) {
            $categoryName = $category->name_category;

            // Get statistics for today
            $todayCount = UserQuiz::distinct('id_quiz')
                ->whereDate('created_at', $currentDate)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            // Get statistics for this month
            $thisMonthCount = UserQuiz::distinct('id_quiz')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $currentDate->month)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            // Get statistics for each month of the given year
            $monthlyCounts = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyCounts[$month] = UserQuiz::distinct('id_quiz')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->whereHas('Quiz', function ($query) use ($category) {
                        $query->where('id_categories', $category->id_categories);
                    })
                    ->count('id_quiz');
            }

            // Get statistics for this year
            $thisYearCount = UserQuiz::distinct('id_quiz')
                ->whereYear('created_at', $year)
                ->whereHas('Quiz', function ($query) use ($category) {
                    $query->where('id_categories', $category->id_categories);
                })
                ->count('id_quiz');

            $categoryStats[$categoryName] = [
                'today' => $todayCount,
                'this_month' => $thisMonthCount,
                'per_month' => $monthlyCounts,
                'this_year' => $thisYearCount,
            ];
        }

        return ResultHelpers::success($categoryStats, 200, true);
    }


    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/statistics/countTotalQuizzesAndPolls",
     *      operationId="statisticsCountTotalQuizzesAndPolls",
     *      tags={"Statistics"},
     *      summary="Show Statistics",
     *      description="Returns Statistics count Total Quizzes And Polls data",
     *      @OA\Response(
     *          response=200,
     *          description="Successful Operation",
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server Error"
     *      )
     * )
     */
    public function countTotalQuizzesAndPolls()
    {
        $categories = Categories::all();

        $categoryCounts = [];

        foreach ($categories as $category) {
            $categoryName = $category->name_category;

            $quizCount = Quiz::where('id_categories', $category->id_categories)->count();
            $pollCount = Poll::where('id_categories', $category->id_categories)->count();

            $categoryCounts[$categoryName] = [
                'total_quizzes' => $quizCount,
                'total_polls' => $pollCount,
            ];
        }

        return ResultHelpers::success($categoryCounts, 200, true);
    }
}
