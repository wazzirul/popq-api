<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;

class QuizQuestionsController extends Controller
{
    //
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-question/create-edit",
     *      operationId="createEditQuizQuestion",
     *      tags={"Quiz Question"},
     *      summary="Create / Edit Quiz Question Data",
     *      description="Returns Quiz Question Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_quiz_questions </b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_quiz</b> : id quiz from table quiz
               <br><b>number_question</b> : number of question
               <br><b>question</b> : question of quiz
               <br><b>duration</b> : duration of quiz by(seconds)
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz_questions", type="int", example="1"),
     *              @OA\Property(property="id_quiz", type="int", example="1"),
     *              @OA\Property(property="number_question", type="int", example="1"),
     *              @OA\Property(property="question", type="string", example="What is the name of the creature that captures Luke Skywalker on Hoth? What is the name of the creature that captures Luke?"),
     *              @OA\Property(property="duration", type="int", example="30"),
     *              @OA\Property(property="soft_delete", type="int", example="0"),
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
    public function createEdit(Request $request)
    {
        $requestData = $request->only([
            'id_quiz_questions', 
            'id_quiz', 
            'number_question',
            'question',
            'duration',
            'soft_delete', 
        ]);

        $idQuizQuestions = $requestData['id_quiz_questions'] ?? null;
        $idQuiz = $requestData['id_quiz'] ?? null;
        $numberQuestion = $requestData['number_question'] ?? null;
        $question = $requestData['question'] ?? null;
        $duration = $requestData['duration'] ?? null;
        $softDelete = $requestData['soft_delete'] ?? 0;
        

        $dataQuiz = Quiz::where('id_quiz', $idQuiz)->first();

        if(!$dataQuiz){
            return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
        }

        if($idQuizQuestions === null){
            // Create a new user account
            $data = new QuizQuestions();
            
            $data->id_quiz = $idQuiz;
            $data->number_question = $numberQuestion;
            $data->question = $question;
            $data->duration = $duration;
            $data->soft_delete = $softDelete;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = QuizQuestions::where('id_quiz_questions', $idQuizQuestions)->first();
            if ($data) {
                $data->id_quiz = $idQuiz;
                $data->number_question = $numberQuestion;
                $data->question = $question;
                $data->duration = $duration;
                $data->soft_delete = $softDelete;
                $data->save();
            }else{
                return ResultHelpers::errors('Quiz Questions Data Not Found from id_quiz_questions inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/quiz-question/show",
     *      operationId="showQuizQuestion",
     *      tags={"Quiz Question"},
     *      summary="Show Quiz Question",
     *      description="Returns Quiz Question data",
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
    public function show(Request $request)
    {
        $data = QuizQuestions::where('soft_delete', 0)
            ->orderBy('id_quiz_questions', 'DESC')
            ->with('Quiz')
            ->get();

        return ResultHelpers::success($data, 200, true);
    }    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-question/showByQuiz",
     *      operationId="showByQuizQuizQuestion",
     *      tags={"Quiz Question"},
     *      summary="Show Quiz Question BY ID Quiz",
     *      description="Returns Quiz Question Data based on id_quiz",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_quiz as a reference to the quiz question data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz", type="string", example="1"),
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
    public function showByQuiz(Request $request)
    {
        $requestData = $request->only([
            'id_quiz', // Facebook ID
        ]);

        $idQuiz = $requestData['id_quiz'] ?? null;

        if (!$idQuiz) {
            return ResultHelpers::errors('id_quiz must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Quiz Data with its Relation.
        $data = QuizQuestions::where('id_quiz', $idQuiz)->where('soft_delete', 0)->with('Quiz')->first();

        return ResultHelpers::success($data, 200, true);
    }    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-question/showByID",
     *      operationId="showByIDQuizQuestion",
     *      tags={"Quiz Question"},
     *      summary="Show Quiz Question BY ID Quiz Question",
     *      description="Returns Quiz Question Data based on id_quiz_questions",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_quiz_questions as a reference to the quiz question data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz_questions", type="string", example="1"),
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
    public function showByID(Request $request)
    {
        $requestData = $request->only([
            'id_quiz_questions', // Facebook ID
        ]);

        $idQuizQuestions = $requestData['id_quiz_questions'] ?? null;

        if (!$idQuizQuestions) {
            return ResultHelpers::errors('id_quiz_questions must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Quiz Data with its Relation.
        $data = QuizQuestions::where('id_quiz_questions', $idQuizQuestions)->with('Quiz','QuizListChoice')->first();

        return ResultHelpers::success($data, 200, true);
    }    
}
