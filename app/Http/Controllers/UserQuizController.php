<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Carbon\Carbon;

use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;

use App\Models\Users;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;

class UserQuizController extends Controller
{
  //
  //
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-quiz/create-edit",
   *      operationId="createEditUserQuiz",
   *      tags={"User Quiz"},
   *      summary="Create / Edit User Quiz Data",
   *      description="Returns User Quiz Data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
         <br /><b>id_user_quiz </b> : null / empty if want to create data, fill the id number if want to update data
         <br /><b>id_user</b> : id_user from table user
         <br /><b>id_quiz</b> : id_quiz from table quiz
         <br /><b>soft_delete</b> : 0 = active, 1 = deleted
         <br /><b>answers</b>: Array of user quiz answers
         <br /><br />
         <br />the return later will have a 
         <br /><b>answered</b> : array of id_quiz_questions that user answered
         <br /><b>status</b>: 0 = not completed, 1 = completed",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user_quiz", type="int", example="1"),
   *              @OA\Property(property="id_user", type="int", example="1"),
   *              @OA\Property(property="id_quiz", type="int", example="1"),
   *              @OA\Property(property="answers", type="array", @OA\Items(
   *                  @OA\Property(property="id_quiz_questions", type="int", example="1"),
   *                  @OA\Property(property="id_quiz_list_choice", type="int", example="1")
   *              )),
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
      'id_user_quiz', 
      'id_user', 
      'id_quiz',
      'answers',
      'soft_delete', 
    ]);

    $idUserQuiz = $requestData['id_user_quiz'] ?? null;
    $idUser = $requestData['id_user'] ?? null;
    $idQuiz = $requestData['id_quiz'] ?? null;
    $dateTime = Carbon::now();
    $answers = $requestData['answers'] ?? [];
    $softDelete = $requestData['soft_delete'] ?? 0;

    $dataUser = Users::where('id_user', $idUser)->first();
    if(!$dataUser){
      return ResultHelpers::errors('User Data Not Found from id_user inputed', 400, false);
    }    

    $dataQuiz = Quiz::where('id_quiz', $idQuiz)->first();
    if(!$dataQuiz){
      return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
    }

    if($idUserQuiz === null){
      // Create a new user account
      $data = new UserQuiz();
      
      $data->id_user = $idUser;
      $data->id_quiz = $idQuiz;
      $data->result = null;
      $data->date_time = $dateTime;
      $data->soft_delete = $softDelete;
      $data->save();
      
      $idUserQuiz = $data->id_user_quiz; // Obtain the new id_user_quiz
    }else{
      // Check if the user already exists in the table
      $data = UserQuiz::where('id_user_quiz', $idUserQuiz)->first();
      if ($data) {
        $data->id_user = $idUser;
        $data->id_quiz = $idQuiz;
        $data->date_time = $dateTime;
        $data->soft_delete = $softDelete;
        $data->save();
      }else{
        return ResultHelpers::errors('User Quiz Data Not Found from id_user_quiz inputed', 400, false);
      }
    }

    $userQuizAnswers = UserQuizAnswer::where('id_user_quiz', $idUserQuiz)->get();
    $answeredQuestionIds = [];

    // Loop through the userQuizAnswers collection
    foreach ($userQuizAnswers as $userQuizAnswer) {
        $answeredQuestionIds[] = $userQuizAnswer->id_quiz_questions;
    }

    foreach ($answers as $answer) {
      $idQuizQuestion = $answer['id_quiz_questions'] ?? null;
      $idQuizListChoice = $answer['id_quiz_list_choice'] ?? null;
      
      // Validate id_quiz_questions against QuizQuestions model
      $quizQuestion = QuizQuestions::find($idQuizQuestion);
      if (!$quizQuestion) {
        return ResultHelpers::errors('Invalid id_quiz_questions provided', 400, false);
      }   
      // Validate if quizquestion is related to the provided id_quiz
      if ($quizQuestion->id_quiz !== $idQuiz) {
        return ResultHelpers::errors('id_quiz_questions is not related to the provided id_quiz', 400, false);
      } 

      // Validate id_quiz_list_choice against QuizListChoice model
      $quizListChoice = QuizListChoice::find($idQuizListChoice);
      if (!$quizListChoice) {
        return ResultHelpers::errors('Invalid id_quiz_list_choice provided', 400, false);
      }
      // Validate if QuizListChoice is related to the provided id_quiz
      if ($quizListChoice->id_quiz !== $idQuiz) {
        return ResultHelpers::errors('id_quiz_list_choice is not related to the provided id_quiz', 400, false);
      } 
      // Validate if QuizListChoice is related to the provided id_quiz_questions
      if ($quizListChoice->id_quiz_questions !== $idQuizQuestion) {
        return ResultHelpers::errors('id_quiz_list_choice is not related to the provided id_quiz_questions', 400, false);
      } 


      if ($idQuizQuestion && $idQuizListChoice) {
        // Check if UserQuizAnswer data exists for the given id_user_quiz and id_quiz_questions
        $userQuizAnswer = UserQuizAnswer::where('id_user_quiz', $idUserQuiz)
                        ->where('id_quiz_questions', $idQuizQuestion)
                        ->first();
      
        if ($userQuizAnswer) {
          // Update the existing record
          $userQuizAnswer->id_quiz_list_choice = $idQuizListChoice;
      
          // Retrieve the QuizListChoice and set 'correct' based on its value
          $quizListChoice = QuizListChoice::find($idQuizListChoice);
          $userQuizAnswer->correct = $quizListChoice->correct;
      
          $userQuizAnswer->soft_delete = $softDelete;
          $userQuizAnswer->save();
        } else {
          // Create a new record
          $userQuizAnswer = new UserQuizAnswer();
          $userQuizAnswer->id_user_quiz = $idUserQuiz;
          $userQuizAnswer->id_quiz_questions = $idQuizQuestion;
          $userQuizAnswer->id_quiz_list_choice = $idQuizListChoice;
      
          // Retrieve the QuizListChoice and set 'correct' based on its value
          $quizListChoice = QuizListChoice::find($idQuizListChoice);
          $userQuizAnswer->correct = $quizListChoice->correct;
      
          $userQuizAnswer->soft_delete = $softDelete;
          $userQuizAnswer->save();
        }
      }     
      // Check if $idQuizQuestion is not already in $answeredQuestionIds and then add it
      if (!in_array($idQuizQuestion, $answeredQuestionIds)) {
        $answeredQuestionIds[] = $idQuizQuestion;
      }
    }
    
    // Calculate the total number of questions in the quiz
    $totalQuestions = Quiz::find($idQuiz)->QuizQuestions->count();
    // Count the number of correct answers in UserQuizAnswer for the given $idUserQuiz
    $totalCorrect = UserQuizAnswer::where('id_user_quiz', $idUserQuiz)
        ->where('correct', 1) // Assuming 'correct' is a column indicating correctness
        ->count();
    // Calculate the result percentage
    $resultPercentage = ($totalCorrect / $totalQuestions) * 100;
    $resultPercentage = round($resultPercentage, 2); // Round to 2 decimal places
    $data->result = $resultPercentage;

    // Convert the array of answered question IDs to a comma-separated string
    $answered = implode(',', $answeredQuestionIds);    
    // Set the 'answered' field
    $data->answered = $answered;    
    // Calculate the total number of questions in the quiz
    $totalQuestions = Quiz::find($idQuiz)->QuizQuestions->count();    
    // Calculate the 'status' field (1 if all questions answered, 0 otherwise)
    $data->status = (count($answeredQuestionIds) === $totalQuestions) ? 1 : 0;
    $data->save();

    $userQuizAnswers = UserQuizAnswer::where('id_user_quiz', $idUserQuiz)->get();
    // Add the user quiz answers to the $data array
    $data['user_quiz_answers'] = $userQuizAnswers;  
    return ResultHelpers::success($data, 200, true);
  }

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-quiz/show",
   *      operationId="showUserQuiz",
   *      tags={"User Quiz"},
   *      summary="Show User Quiz",
   *      description="Returns User Quiz data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
        <br> - id_user as a reference to the User Quiz data that will be returned
        <br> - limit as limit show data (default 10)
        <br> - page as page show data (default 1)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user", type="string", example="1"),
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
  public function show(Request $request)
  {
    $requestData = $request->only([
      'id_user',
      'limit',
      'page',
    ]);
  
    $idUser = $requestData['id_user'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;;
  
    if (!$idUser) {
      return ResultHelpers::errors('id_user must be provided.', 400, false);
    }
  
    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;        
  
    // Fetch paginated data
    $query = UserQuiz::where('id_user', $idUser)
        ->where('soft_delete', 0)
        ->with('Quiz.QuizQuestions.QuizListChoice.UserQuizAnswer');

    $totalRecords = $query->count();

    $data = $query->skip($offset)
        ->take($limit)
        ->get();

    if (!$data) {
        return ResultHelpers::errors('User Quiz not found.', 404, false);
    }

    // Calculate the total number of pages
    $totalPages = ceil($totalRecords / $limit);

    // Create the response array
    $response = [
        'data' => $data,
        'total_pages' => $totalPages,
    ];
  
    return ResultHelpers::success($response, 200, true);
  }    

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-quiz/showByIdQuiz",
   *      operationId="showUserQuizByIdQuiz",
   *      tags={"User Quiz"},
   *      summary="Show User Quiz By Id Quiz",
   *      description="Returns User Quiz By Id Quiz data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
        <br> - id_quiz as a reference to the User Quiz data that will be returned
        <br> - limit as limit show data (default 10)
        <br> - page as page show data (default 1)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_quiz", type="string", example="1"),
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
  public function showByIdQuiz(Request $request)
  {
    $requestData = $request->only([
      'id_quiz',
      'limit',
      'page',
    ]);
  
    $idQuiz = $requestData['id_quiz'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
  
    if (!$idQuiz) {
      return ResultHelpers::errors('id_quiz must be provided.', 400, false);
    }
  
    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;
    
    // Fetch paginated data
    $query = UserQuiz::where('id_quiz', $idQuiz)
      ->where('soft_delete', 0)
      ->with('Quiz.QuizQuestions.QuizListChoice.UserQuizAnswer');

    $totalRecords = $query->count();

    $data = $query->skip($offset)
        ->take($limit)
        ->get();

    if (!$data) {
        return ResultHelpers::errors('User Quiz not found.', 404, false);
    }

    // Calculate the total number of pages
    $totalPages = ceil($totalRecords / $limit);

    // Create the response array
    $response = [
        'data' => $data,
        'total_pages' => $totalPages,
    ];
  
    return ResultHelpers::success($response, 200, true);
  }    
}
