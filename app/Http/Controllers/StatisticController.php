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

        $data = [
            'today' => $todayCount,
            'this_month' => $thisMonthCount,
            'per_month' => $monthlyCounts,
            'this_year' => $thisYearCount,
        ];

        return ResultHelpers::success($data, 200, true);
    }
}
