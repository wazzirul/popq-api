<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\UserPoll;
use App\Models\Users;
use App\Models\Quiz;
use App\Models\UserQuiz;

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
        // $categories = Categories::all();

        // // Initialize an array to hold category statistics
        // $categoryStats = [];

        // foreach ($categories as $category) {
        //     $categoryName = $category->name_category;

        //     // Get statistics for today
        //     $todayCount = UserQuiz::distinct('id_quiz')
        //         ->whereDate('created_at', $currentDate)
        //         ->whereHas('Quiz', function ($query) use ($category) {
        //             $query->where('id_categories', $category->id_categories);
        //         })
        //         ->count('id_quiz');

        //     // Get statistics for this month
        //     $thisMonthCount = UserQuiz::distinct('id_quiz')
        //         ->whereYear('created_at', $year)
        //         ->whereMonth('created_at', $currentDate->month)
        //         ->whereHas('Quiz', function ($query) use ($category) {
        //             $query->where('id_categories', $category->id_categories);
        //         })
        //         ->count('id_quiz');

        //     // Get statistics for each month of the given year
        //     $monthlyCounts = [];
        //     for ($month = 1; $month <= 12; $month++) {
        //         $monthlyCounts[$month] = UserQuiz::distinct('id_quiz')
        //             ->whereYear('created_at', $year)
        //             ->whereMonth('created_at', $month)
        //             ->whereHas('Quiz', function ($query) use ($category) {
        //                 $query->where('id_categories', $category->id_categories);
        //             })
        //             ->count('id_quiz');
        //     }

        //     // Get statistics for this year
        //     $thisYearCount = UserQuiz::distinct('id_quiz')
        //         ->whereYear('created_at', $year)
        //         ->whereHas('Quiz', function ($query) use ($category) {
        //             $query->where('id_categories', $category->id_categories);
        //         })
        //         ->count('id_quiz');

        //     $categoryStats[$categoryName] = [
        //         'category' => $category,
        //         'today' => $todayCount,
        //         'this_month' => $thisMonthCount,
        //         'per_month' => $monthlyCounts,
        //         'this_year' => $thisYearCount,
        //     ];
        // }

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
        $categories = Categories::where('soft_delete', 0)->get();

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
                'id_categories' => $category->id_categories,
                'name_category' => $category->name_category,
                'icon' => $category->icon,
                'color' => $category->color,
                'color_hex' => $category->color_hex,
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
        // total quizzes
        $totalQuizzes = Quiz::where('soft_delete', 0)->count();
        // total polls
        $totalPolls = Poll::where('soft_delete', 0)->count();

        // categories total quiz and total poll
        $categories = Categories::where('soft_delete', 0)->get();

        $categoryCounts = [];

        foreach ($categories as $category) {
            $categoryName = $category->name_category;

            $quizCount = Quiz::where('id_categories', $category->id_categories)->where('soft_delete', 0)->count();
            $pollCount = Poll::where('id_categories', $category->id_categories)->where('soft_delete', 0)->count();

            $categoryCounts[$categoryName] = [
                'id_categories' => $category->id_categories,
                'name_category' => $category->name_category,
                'icon' => $category->icon,
                'color' => $category->color,
                'color_hex' => $category->color_hex,
                'total_quizzes' => $quizCount,
                'total_polls' => $pollCount,
            ];
        }
        

        // total quizzes this week
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();

        $totalCompletedQuizzesThisWeek = UserQuiz::whereHas('Quiz', function ($query) use ($startDate, $endDate) {
            $query->where('soft_delete', 0);
        })->where('status', 1)
        ->whereBetween('date_time', [$startDate, $endDate])
        ->count();

        // Count the number of polls polled this week for each id_poll
        $pollCountsThisWeek = UserPoll::whereHas('Poll', function ($query) use ($startDate, $endDate) {
            $query->where('soft_delete', 0);
        })
        ->whereBetween('date_time', [$startDate, $endDate])
        ->groupBy('id_poll')
        ->count();

        $categoryCountsThisWeek = [];
        foreach ($categories as $category) {
            $categoryName = $category->name_category;
    
            $quizCountThisWeek = UserQuiz::leftJoin('quiz', 'user_quiz.id_quiz', '=', 'quiz.id_quiz')
            ->where('quiz.id_categories', $category->id_categories)
            ->where('quiz.soft_delete', 0)
            ->where('user_quiz.soft_delete', 0)
            ->where('user_quiz.status', 1)
            ->whereBetween('user_quiz.date_time', [$startDate, $endDate])
            ->groupBy('user_quiz.id_user_quiz')
            ->count();
            
            $pollCountThisWeek = UserPoll::leftJoin('poll', 'user_poll.id_poll', '=', 'poll.id_poll')
            ->where('poll.id_categories', $category->id_categories)
            ->where('poll.soft_delete', 0)
            ->where('user_poll.soft_delete', 0)
            ->whereBetween('date_time', [$startDate, $endDate])
            ->groupBy('poll.id_poll')
            ->count();
    
            $categoryCountsThisWeek[$categoryName] = [
                'id_categories' => $category->id_categories,
                'name_category' => $category->name_category,
                'icon' => $category->icon,
                'color' => $category->color,
                'color_hex' => $category->color_hex,
                'total_quizzes_this_week' => $quizCountThisWeek,
                'total_polls_this_week' => $pollCountThisWeek,
            ];
        }

        $data = [
            'category' => $categoryCounts,
            'category_submitted_this_week' => $categoryCountsThisWeek,
            'total_all_quizzes' => $totalQuizzes,
            'total_completed_quizzes_this_week' => $totalCompletedQuizzesThisWeek,
            'total_all_polls' => $totalPolls,
            'total_polls_this_week' => $pollCountsThisWeek,
        ];

        return ResultHelpers::success($data, 200, true);
    }

    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/statistics/showScoresUser",
     *      operationId="statisticsshowScoresUser",
     *      tags={"Statistics"},
     *      summary="Show Score User Statistics Data",
     *      description="Returns User score total quiz submit and average score, and highest score from each category",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>id_user</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="string", example="1"),
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
    public function showScoresUser(Request $request)
    {
        $requestData = $request->only([
            'id_user',
        ]);

        $idUser = $requestData['id_user'];

        $user = Users::where('id_user', $idUser)->first();

        if (!$user) {
            return ResultHelpers::error('User not found', 404);
        }

        // Calculate the average score for the user's quizzes
        $totalQuizSubmitted = UserQuiz::where('id_user', $idUser)->count();
        $averageScore = number_format(UserQuiz::where('id_user', $idUser)->avg('result') / 10, 1) . '/10';

        // Get the user's top score for each category
        $categories = Categories::all();

        // Initialize an array to store top scores for each category
        $topScores = [];
        foreach ($categories as $category) {
            $categoryId = $category->id_categories;
            $topScore = number_format(UserQuiz::join('quiz', 'user_quiz.id_quiz', '=', 'quiz.id_quiz')
                ->join('categories', 'quiz.id_categories', '=', 'categories.id_categories')
                ->where('user_quiz.id_user', $idUser)
                ->where('quiz.id_categories', $categoryId)
                ->where('quiz.soft_delete', 0)
                ->where('quiz.draft', 0)
                ->where('categories.soft_delete', 0)
                ->where('user_quiz.status', 1)
                ->max('user_quiz.result') / 10, 0) . '/10';
                
            $topScores[$category->name_category] = [
                'id_categories' => $category->id_categories,
                'name_category' => $category->name_category,
                'icon' => $category->icon,
                'color' => $category->color,
                'color_hex' => $category->color_hex,
                'top_score' => $topScore,
            ];
        }

        $data = [
            'total_quiz' => $totalQuizSubmitted,
            'average_score' => $averageScore,
            'top_score' => $topScores,
        ];
        

        return ResultHelpers::success($data, 200, true);
    }
}
