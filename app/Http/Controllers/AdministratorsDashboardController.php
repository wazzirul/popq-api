<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use App\Models\UserPoll;
use App\Models\UserQuiz;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdministratorsDashboardController extends Controller
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/administrators-dashboard/user",
     *      operationId="useradministratorsdashboard",
     *      tags={"Administrators Dashboard"},
     *      summary="Show user administrators dashboard data",
     *      description="Returns user administrators dashboard",
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
    public function user()
    {
        $popularTrivia = UserQuiz::where('soft_delete', 0)
            ->select('id_quiz', DB::raw('COUNT(*) as plays'))
            ->groupBy('id_quiz')
            ->with('Quiz')
            ->orderBy('plays', 'DESC')
            ->limit(5)
            ->get();

        $popularPoll = UserPoll::where('soft_delete', 0)
            ->select('id_poll', DB::raw('COUNT(*) as plays'))
            ->groupBy('id_poll')
            ->with('Poll')
            ->orderBy('plays', 'DESC')
            ->limit(5)
            ->get();

        $now = Carbon::now();
        $lastWeek = $now->copy()->subWeeks(1)->endOfWeek();

        $averageTrivia = UserQuiz::where('soft_delete', 0)
            ->select('id_user', DB::raw('COUNT(*) as total_quiz'))
            ->groupBy('id_user')
            ->get()
            ->avg('total_quiz');
        $averageTrivia = ceil($averageTrivia);

        $increaseAverageTrivia = UserQuiz::where('soft_delete', 0)
            ->select('id_user', DB::raw('COUNT(*) as total_quiz'))
            ->groupBy('id_user')
            ->whereDate('updated_at', '<=', $lastWeek->copy()->toDateString())
            ->get()
            ->avg('total_quiz');
        $increaseAverageTrivia = ceil($increaseAverageTrivia);
        $increaseAverageTrivia = $increaseAverageTrivia - $averageTrivia;
        if ($increaseAverageTrivia != 0) $increaseAverageTrivia = round((($increaseAverageTrivia / $averageTrivia) * 100), 2);

        $averagePoll = UserPoll::where('soft_delete', 0)
            ->select('id_poll', DB::raw('COUNT(*) as total_quiz'))
            ->groupBy('id_poll')
            ->get()
            ->avg('total_quiz');
        $averagePoll = ceil($averagePoll);

        $increaseAveragePoll = UserPoll::where('soft_delete', 0)
            ->select('id_poll', DB::raw('COUNT(*) as total_quiz'))
            ->groupBy('id_poll')
            ->whereDate('updated_at', '<=', $lastWeek->copy()->toDateString())
            ->get()
            ->avg('total_quiz');
        $increaseAveragePoll = ceil($increaseAveragePoll);
        $increaseAveragePoll = $increaseAveragePoll - $averagePoll;
        if ($increaseAveragePoll != 0) $increaseAveragePoll = round((($increaseAveragePoll / $averagePoll) * 100), 2);

        $data['average_trivia'] = $averageTrivia;
        $data['increase_average_trivia'] = $increaseAverageTrivia;
        $data['average_poll'] = $averagePoll;
        $data['increase_average_poll'] = $increaseAveragePoll;
        $data['popular_trivia'] = $popularTrivia;
        $data['popular_poll'] = $popularPoll;

        return ResultHelpers::success($data, 200, true);
    }
}
