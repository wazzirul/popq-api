<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\UserPoll;
use App\Models\Users;
use App\Models\Quiz;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;

class StatisticV2Controller extends Controller
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/v2/statistics/showScoresUser",
     *      operationId="statisticsshowscoresuserv2",
     *      tags={"Statistics V2"},
     *      summary="Show Score User Statistics Data",
     *      description="Returns User score total quiz submit, correct answer with percentage, and correct answer percentage from each category",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>id_user</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="int", example="1"),
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
        if (!$user) return ResultHelpers::error('User not found', 404);

        // Calculate the correct answer with percentage for the user's quizzes
        $totalQuizSubmitted = UserQuiz::where('id_user', $idUser)->count();
        $allAnswer = UserQuizAnswer::rightJoin('user_quiz', 'user_quiz_answer.id_user_quiz', '=', 'user_quiz.id_user_quiz')
            ->where('user_quiz.id_user', $idUser)
            ->where('user_quiz.soft_delete', 0)
            ->where('user_quiz_answer.soft_delete', 0)
            ->count();
        $correctAnswer = UserQuizAnswer::rightJoin('user_quiz', 'user_quiz_answer.id_user_quiz', '=', 'user_quiz.id_user_quiz')
            ->where('user_quiz.id_user', $idUser)
            ->where('user_quiz.soft_delete', 0)
            ->where('user_quiz_answer.soft_delete', 0)
            ->where('user_quiz_answer.correct', 1)
            ->count();
        $correctAnswerPercentage = ($allAnswer > 0 && $correctAnswer > 0) ? number_format($correctAnswer / $allAnswer * 100, 2) : 0;

        // Get the user's top score for each category
        $categories = Categories::where('soft_delete', 0)->get();

        // Initialize an array to store correct answer with percentage for each category
        foreach ($categories as $category) {
            $allAnswerCategory = UserQuizAnswer::rightJoin('user_quiz', 'user_quiz_answer.id_user_quiz', '=', 'user_quiz.id_user_quiz')
                ->rightJoin('quiz', 'user_quiz.id_quiz', '=', 'quiz.id_quiz')
                ->where('user_quiz.id_user', $idUser)
                ->where('user_quiz.soft_delete', 0)
                ->where('user_quiz_answer.soft_delete', 0)
                ->where('quiz.id_categories', $category->id_categories)
                ->count();
            $correctAnswerCategory = UserQuizAnswer::rightJoin('user_quiz', 'user_quiz_answer.id_user_quiz', '=', 'user_quiz.id_user_quiz')
                ->rightJoin('quiz', 'user_quiz.id_quiz', '=', 'quiz.id_quiz')
                ->where('user_quiz.id_user', $idUser)
                ->where('user_quiz.soft_delete', 0)
                ->where('user_quiz_answer.soft_delete', 0)
                ->where('user_quiz_answer.correct', 1)
                ->where('quiz.id_categories', $category->id_categories)
                ->count();
            $correctAnswerPercentageCategory = ($allAnswerCategory > 0 && $correctAnswerCategory > 0) ? number_format($correctAnswerCategory / $allAnswerCategory * 100, 2) : 0;

            unset($category->soft_delete);
            unset($category->created_at);
            unset($category->updated_at);

            $category['all_answer'] = $allAnswerCategory;
            $category['correct_answer'] = $correctAnswerCategory;
            $category['correct_answer_percentage'] = $correctAnswerPercentageCategory;
        }

        $data = [
            'total_quiz' => $totalQuizSubmitted,
            'all_answer' => $allAnswer,
            'correct_answer' => $correctAnswer,
            'correct_answer_percentage' => $correctAnswerPercentage,
            'categories' => $categories,
        ];

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/v2/statistics/showCompletedQuiz",
     *      operationId="statisticsshowCompletedQuizv2",
     *      tags={"Statistics V2"},
     *      summary="Show Score User Statistics Data",
     *      description="Returns User completed quiz",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>id_user</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="int", example="1"),
     *              @OA\Property(property="limit", type="int", example="10"),
     *              @OA\Property(property="page", type="int", example="1"),
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
    public function showCompletedQuiz(Request $request)
    {
        $requestData = $request->only([
            'id_user',
            'limit',
            'page',
        ]);

        $idUser = $requestData['id_user'];
        $limit = $requestData['limit'] ?? 10; // Default limit is 10
        $page = $requestData['page'] ?? 1;

        $user = Users::where('id_user', $idUser)->first();
        if (!$user) return ResultHelpers::error('User not found', 404);

        $offset = ($page - 1) * $limit;
        $data = UserQuiz::where('soft_delete', 0)
            ->where('id_user', $idUser)
            ->with('Quiz.Categories')
            ->orderBy('updated_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/v2/statistics/showCompletedPoll",
     *      operationId="statisticsshowCompletedPollv2",
     *      tags={"Statistics V2"},
     *      summary="Show Score User Statistics Data",
     *      description="Returns User completed poll",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>id_user</b> as a reference to the user data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="int", example="1"),
     *              @OA\Property(property="limit", type="int", example="10"),
     *              @OA\Property(property="page", type="int", example="1"),
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
    public function showCompletedPoll(Request $request)
    {
        $requestData = $request->only([
            'id_user',
            'limit',
            'page',
        ]);

        $idUser = $requestData['id_user'];
        $limit = $requestData['limit'] ?? 10; // Default limit is 10
        $page = $requestData['page'] ?? 1;

        $user = Users::where('id_user', $idUser)->first();
        if (!$user) return ResultHelpers::error('User not found', 404);

        $offset = ($page - 1) * $limit;
        $data = UserPoll::where('soft_delete', 0)
            ->where('id_user', $idUser)
            ->with('Poll.Categories')
            ->orderBy('updated_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();

        return ResultHelpers::success($data, 200, true);
    }
}
