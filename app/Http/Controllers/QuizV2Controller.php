<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use App\Models\Categories;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;
use App\Models\Users;

class QuizV2Controller extends Controller
{
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/quiz/showById",
   *      operationId="showbyidquizv2",
   *      tags={"Quiz V2"},
   *      summary="Show BY Id Quiz with related quiz Data",
   *      description="Returns Quiz with related quiz Data based on id_user, id_quiz / slug",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
                <br> - id_user (optional) as a reference to the user data that will be returned with prioritize on recent
                <br> - id_quiz as a reference to the quiz data that will be returned
                <br> - slug as a reference to the quiz data that will be returned
                <br> - show_qwizard as condition(boolean) to show qwizard (default: false)
                <br> - limit_related as limit show data related (default 10)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user", type="int", example="5"),
   *              @OA\Property(property="id_quiz", type="int", example="825"),
   *              @OA\Property(property="slug", type="string", example=null),
   *              @OA\Property(property="limit_related", type="int", example="10"),
   *              @OA\Property(property="show_qwizard", type="boolean", example=false),
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
  public function showById(Request $request)
  {
    $requestData = $request->only([
      'id_user',
      'id_quiz',
      'slug',
      'limit_related',
      'show_qwizard',
    ]);

    $idUser = $requestData['id_user'] ?? null;
    $idQuiz = $requestData['id_quiz'] ?? null;
    $slugQuiz = $requestData['slug'] ?? null;
    $limitRelated = $requestData['limit_related'] ?? 10;
    $showQwizard = $requestData['show_qwizard'] ?? false;

    if (!$idQuiz && !$slugQuiz) {
      return ResultHelpers::errors('id_quiz or slug quiz must be provided.', 400, false);
    }

    $data = null;

    if ($idQuiz) {
      // Check by idQuiz if provided
      $data = Quiz::where('id_quiz', $idQuiz)->with('Categories')->first();
    } elseif ($slugQuiz) {
      // Check by slugQuiz if provided
      $data = Quiz::where('slug', $slugQuiz)->with('Categories')->first();
    }
    if (!$data) return ResultHelpers::errors('Data quiz not found.', 400, false);

    if ($idUser) {
      $user = Users::find($idUser);
      if (!$user) return ResultHelpers::errors('User not found.', 404, false);

      $relatedQuizUser = UserQuiz::where('user_quiz.id_user', $idUser)
        ->where('user_quiz.soft_delete', 0)
        ->rightJoin('quiz', 'quiz.id_quiz', '=', 'user_quiz.id_quiz')
        ->where(function ($q) use ($data) {
          $q->orwhere('quiz.topic', 'LIKE', "%$data->topic%");
          $q->orwhere('quiz.title', 'LIKE', "%$data->title%");
          $q->orwhere('quiz.subtitle', 'LIKE', "%$data->subtitle%");
        })
        ->where('user_quiz.id_quiz', '!=', $data->id_quiz)
        ->orderBy('user_quiz.updated_at', 'DESC')
        ->limit($limitRelated)
        ->get();

      foreach ($relatedQuizUser as $key => $value) {
        $userQuizAnswer = UserQuizAnswer::where('soft_delete', 0)
          ->where('id_user_quiz', $value->id_user_quiz);

        $totalQuestions = $userQuizAnswer->count();
        $correctAnswers = $userQuizAnswer->where('correct', 1)->count();

        $categories = Categories::find($value->id_categories);

        $value['total_questions'] = $totalQuestions;
        $value['correct_answers'] = $correctAnswers;
        $value['categories'] = $categories;
      }

      $newLimit = $limitRelated - $relatedQuizUser->count();
      if ($newLimit > 0) {
        $relatedQuiz = Quiz::where('soft_delete', 0)
          ->where(function ($q) use ($data) {
            $q->orwhere('topic', 'LIKE', "%$data->topic%");
            $q->orwhere('title', 'LIKE', "%$data->title%");
            $q->orwhere('subtitle', 'LIKE', "%$data->subtitle%");
          })
          ->orderBy('created_at', 'DESC')
          ->where('id_quiz', '!=', $data->id_quiz)
          ->whereNotIn('id_quiz', $relatedQuizUser->pluck('id_quiz'))
          ->where(function ($q) use ($showQwizard) {
            $showQwizard == false && $q->where('id_user', null);
          })
          ->with('Categories')
          ->limit($newLimit)
          ->get();

        $relatedQuiz = [...$relatedQuizUser, ...$relatedQuiz];
      }
    } else {
      $relatedQuiz = Quiz::where('soft_delete', 0)
        ->where(function ($q) use ($data) {
          $q->orwhere('topic', 'LIKE', "%$data->topic%");
          $q->orwhere('title', 'LIKE', "%$data->title%");
          $q->orwhere('subtitle', 'LIKE', "%$data->subtitle%");
        })
        ->where('quiz.id_quiz', '!=', $data->id_quiz)
        ->orderBy('created_at', 'DESC')
        ->where(function ($q) use ($showQwizard) {
          $showQwizard == false && $q->where('id_user', null);
        })
        ->with('Categories')
        ->limit($limitRelated)
        ->get();
    }
    $data['related_quiz'] = $relatedQuiz;

    if (!$data) {
      return ResultHelpers::errors('Quiz not found.', 404, false);
    }

    // Retrieve the quiz questions for the quiz
    $quizQuestions = QuizQuestions::where('id_quiz', $data->id_quiz)->where('soft_delete', 0)->get();

    // Shuffle the quiz list choices for each quiz question
    $quizQuestions->each(function ($question) {
      $question->QuizListChoice = QuizListChoice::where('id_quiz_questions', $question->id_quiz_questions)->get()->shuffle();
    });

    // Assign the quiz questions to the quiz
    $data->QuizQuestions = $quizQuestions;

    return ResultHelpers::success($data, 200, true);
  }
}
