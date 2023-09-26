<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;

class QuizListChoiceController extends Controller
{
    //
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-list-choice/create-edit",
     *      operationId="createEditQuizListChoice",
     *      tags={"Quiz List Choice"},
     *      summary="Create / Edit Quiz List Choice Data",
     *      description="Returns Quiz List Choice Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_quiz_list_choice</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_quiz</b> : id quiz from table quiz
               <br><b>id_quiz_questions</b> : id_quiz_questions from table quiz questions
               <br><b>answer</b> : string of answer choice
               <br><b>correct</b> : correct answer, 0 = false, 1 = true, default 0
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz_list_choice", type="int", example="1"),
     *              @OA\Property(property="id_quiz", type="int", example="1"),
     *              @OA\Property(property="id_quiz_questions", type="int", example="1"),
     *              @OA\Property(property="answer", type="string", example="Tauntaun"),
     *              @OA\Property(property="correct", type="int", example="0"),
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
            'id_quiz_list_choice', 
            'id_quiz', 
            'id_quiz_questions', 
            'answer',
            'correct',
            'soft_delete', 
        ]);

        $idQuizListChoice = $requestData['id_quiz_list_choice'] ?? null;
        $idQuiz = $requestData['id_quiz'] ?? null;
        $idQuizQuestions = $requestData['id_quiz_questions'] ?? null;
        $answer = $requestData['answer'] ?? null;
        $correct = $requestData['correct'] ?? null;
        $softDelete = $requestData['soft_delete'] ?? 0;
        
        $dataQuiz = Quiz::where('id_quiz', $idQuiz)->first();
        if(!$dataQuiz){
            return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
        }
        
        $dataQuizQuestions = QuizQuestions::where('id_quiz_questions', $idQuizQuestions)->first();
        if(!$dataQuizQuestions){
            return ResultHelpers::errors('Quiz Questions Data Not Found from id_quiz_questions inputed', 400, false);
        }

        // Check if the provided id_quiz_questions matches the id_quiz_questions from QuizQuestions
        if ($dataQuizQuestions->id_quiz !== $idQuiz) {
            return ResultHelpers::errors('id_quiz_questions does not match the provided id_quiz', 400, false);
        }

        if($idQuizListChoice === null){
            // Create a new user account
            $data = new QuizListChoice();
            
            $idQuiz && $data->id_quiz = $idQuiz;
            $idQuizQuestions && $data->id_quiz_questions = $idQuizQuestions;
            $answer && $data->answer = $answer;
            $correct && $data->correct = $correct;
            $softDelete && $data->soft_delete = $softDelete;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = QuizListChoice::where('id_quiz_list_choice', $idQuizListChoice)->first();
            if ($data) {
                $idQuiz && $data->id_quiz = $idQuiz;
                $idQuizQuestions && $data->id_quiz_questions = $idQuizQuestions;
                $answer && $data->answer = $answer;
                $correct && $data->correct = $correct;
                $softDelete && $data->soft_delete = $softDelete;
                $data->save();
            }else{
                return ResultHelpers::errors('Quiz List Choice Data Not Found from id_quiz_list_choice inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }

    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-list-choice/create-edit-v2",
     *      operationId="createEditQuizListChoiceV2",
     *      tags={"Quiz List Choice"},
     *      summary="Create / Edit Quiz List Choice Data",
     *      description="Returns Quiz List Choice Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_quiz_list_choice</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_quiz</b> : id quiz from table quiz
               <br><b>id_quiz_questions</b> : id_quiz_questions from table quiz questions
               <br><b>answer</b> : string of answer choice
               <br><b>correct</b> : correct answer, 0 = false, 1 = true, default 0
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id_quiz_list_choice", type="int", example="1"),
     *                  @OA\Property(property="id_quiz", type="int", example="1"),
     *                  @OA\Property(property="id_quiz_questions", type="int", example="1"),
     *                  @OA\Property(property="answer", type="string", example="Tauntaun"),
     *                  @OA\Property(property="correct", type="int", example="0"),
     *                  @OA\Property(property="soft_delete", type="int", example="0"),
     *              )),
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
    public function createEditV2(Request $request)
    {
        $requestData = $request->input('data', []);
    
        if (empty($requestData)) {
            return ResultHelpers::errors('No data provided in the request', 400, false);
        }

        $storedData = [];
        foreach ($requestData as $data) {
            $idQuizListChoice = $data['id_quiz_list_choice'] ?? null;
            $idQuiz = $data['id_quiz'] ?? null;
            $idQuizQuestions = $data['id_quiz_questions'] ?? null;
            $answer = $data['answer'] ?? null;
            $correct = $data['correct'] ?? null;
            $softDelete = $data['soft_delete'] ?? 0;
    
            $dataQuiz = Quiz::where('id_quiz', $idQuiz)->first();
            if (!$dataQuiz) {
                return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
            }
    
            $dataQuizQuestions = QuizQuestions::where('id_quiz_questions', $idQuizQuestions)->first();
            if (!$dataQuizQuestions) {
                return ResultHelpers::errors('Quiz Questions Data Not Found from id_quiz_questions inputed', 400, false);
            }
    
            // Check if the provided id_quiz_questions matches the id_quiz_questions from QuizQuestions
            if ($dataQuizQuestions->id_quiz !== $idQuiz) {
                return ResultHelpers::errors('id_quiz_questions does not match the provided id_quiz', 400, false);
            }
    
            if ($idQuizListChoice === null) {
                // Create a new user account
                $newData = new QuizListChoice();
    
                $idQuiz && $newData->id_quiz = $idQuiz;
                $idQuizQuestions && $newData->id_quiz_questions = $idQuizQuestions;
                $answer && $newData->answer = $answer;
                $correct && $newData->correct = $correct;
                $softDelete && $newData->soft_delete = $softDelete;
                $newData->save();
                $storedData[] = $newData;
            } else {
                // Check if the user already exists in the table
                $existingData = QuizListChoice::where('id_quiz_list_choice', $idQuizListChoice)->first();
                if ($existingData) {
                    $idQuiz && $existingData->id_quiz = $idQuiz;
                    $idQuizQuestions && $existingData->id_quiz_questions = $idQuizQuestions;
                    $answer && $existingData->answer = $answer;
                    $correct && $existingData->correct = $correct;
                    $softDelete && $existingData->soft_delete = $softDelete;
                    $existingData->save();
                    $storedData[] = $existingData;
                } else {
                    return ResultHelpers::errors('Quiz List Choice Data Not Found from id_quiz_list_choice inputed', 400, false);
                }
            }
        }
    
        return ResultHelpers::success($storedData, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/quiz-list-choice/show",
     *      operationId="showQuizListChoice",
     *      tags={"Quiz List Choice"},
     *      summary="Show Quiz List Choice",
     *      description="Returns Quiz List Choice data",
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
        $data = QuizListChoice::where('soft_delete', 0)
            ->orderBy('id_quiz_list_choice', 'DESC')
            ->get();

        return ResultHelpers::success($data, 200, true);
    }    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-list-choice/showByQuiz",
     *      operationId="showQuizListChoiceByQuiz",
     *      tags={"Quiz List Choice"},
     *      summary="Show Quiz List Choice BY ID Quiz",
     *      description="Returns Quiz List Choice Data based on id_quiz",
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
        $data = QuizListChoice::where('id_quiz', $idQuiz)->where('soft_delete', 0)->with('Quiz','QuizQuestions')->first();

        return ResultHelpers::success($data, 200, true);
    }    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz-list-choice/showByQuizQuestions",
     *      operationId="showQuizListChoiceByshowByQuizQuestions",
     *      tags={"Quiz List Choice"},
     *      summary="Show Quiz List Choice BY ID Quiz Questions",
     *      description="Returns Quiz List Choice Data based on id_quiz_questions",
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
    public function showByQuizQuestions(Request $request)
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
        $data = QuizListChoice::where('id_quiz_questions', $idQuizQuestions)->where('soft_delete', 0)->with('Quiz','QuizQuestions')->first();

        return ResultHelpers::success($data, 200, true);
    } 

}
