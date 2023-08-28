<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Carbon\Carbon;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\UserPoll;
use App\Models\Users;

class UserPollController extends Controller
{
    //
  //
  //
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-poll/create-edit",
   *      operationId="createEditUserPoll",
   *      tags={"User Poll"},
   *      summary="Create / Edit User Poll Data",
   *      description="Returns User Poll Data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
         <br><b>id_user_poll </b> : null / empty if want to create data, fill the id number if want to update data
         <br><b>id_user</b> : id_user from table user
         <br><b>id_poll</b> : id_poll from table poll
         <br><b>id_poll_list_choice</b> : id_poll_list_choice from table poll list choice
         <br><b>soft_delete</b> : 0 = active, 1 = deleted (default = 0)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user_poll", type="int", example="1"),
   *              @OA\Property(property="id_user", type="int", example="1"),
   *              @OA\Property(property="id_poll", type="int", example="1"),
   *              @OA\Property(property="id_poll_list_choice", type="int", example="1"),
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
      'id_user_poll', 
      'id_user', 
      'id_poll',
      'id_poll_list_choice',
      'soft_delete', 
    ]);

    $idUserPoll = $requestData['id_user_poll'] ?? null;
    $idUser = $requestData['id_user'] ?? null;
    $idPoll = $requestData['id_poll'] ?? null;
    $idPollListChoice = $requestData['id_poll_list_choice'] ?? null;
    $dateTime = Carbon::now();
    $softDelete = $requestData['soft_delete'] ?? 0;

    $dataUser = Users::where('id_user', $idUser)->first();
    if(!$dataUser){
      return ResultHelpers::errors('User Data Not Found from id_user inputed', 400, false);
    }    

    $dataPoll = Poll::where('id_poll', $idPoll)->first();
    if(!$dataPoll){
      return ResultHelpers::errors('Poll Data Not Found from id_poll inputed', 400, false);
    }

    $dataPollListChoice = PollListChoice::where('id_poll', $idPoll)->where('id_poll_list_choice', $idPollListChoice)->first();
    if(!$dataPollListChoice){
      return ResultHelpers::errors('Poll List Choice Data Not Found from id_poll & id_poll_list_choice inputed', 400, false);
    }

    if($idUserPoll === null){
      // Create a new user account
      $data = new UserPoll();
      
      $data->id_user = $idUser;
      $data->id_poll = $idPoll;
      $data->id_poll_list_choice = $idPollListChoice;
      $data->date_time = $dateTime;
      $data->soft_delete = $softDelete;
      $data->save();
    }else{
      // Check if the user already exists in the table
      $data = UserPoll::where('id_user_poll', $idUserPoll)->first();
      if ($data) {
      
        $data->id_user = $idUser;
        $data->id_poll = $idPoll;
        $data->id_poll_list_choice = $idPollListChoice;
        $data->date_time = $dateTime;
        $data->soft_delete = $softDelete;
        $data->save();
      }else{
        return ResultHelpers::errors('User Poll Data Not Found from id_user_poll inputed', 400, false);
      }
    }

    // Fetch the poll and its associated data as described earlier
    $poll = Poll::where('id_poll', $idPoll)
    ->with('Categories', 'PollListChoice')
    ->first();

    if (!$poll) {
    return ResultHelpers::errors('Poll not found.', 404, false);
    }

    // Load the associated PollListChoice records
    $poll->load(['PollListChoice']);

    // Count the number of selections for each PollListChoice using UserPoll
    foreach ($poll->PollListChoice as $choice) {
    $pollCount = UserPoll::where('id_poll', $idPoll)
        ->where('id_poll_list_choice', $choice->id_poll_list_choice)
        ->count();
    $choice->poll_count = $pollCount;
    }

    // Include the $poll object in the $data response
    $finalData = new \stdClass();
    $finalData->user_poll = $data;
    $finalData->poll_data = $poll;

    return ResultHelpers::success($finalData, 200, true);
  }

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-poll/show",
   *      operationId="showUserPoll",
   *      tags={"User Poll"},
   *      summary="Show User Poll",
   *      description="Returns User Poll data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
        <br> - id_user as a reference to the User Poll data that will be returned
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
    $data = UserPoll::where('id_user', $idUser)
      ->where('soft_delete', 0)
      ->with('Poll.PollListChoice')
      ->skip($offset)
      ->take($limit)
      ->get();
      
    if (!$data) {
      return ResultHelpers::errors('User Poll not found.', 404, false);
    }
  
    return ResultHelpers::success($data, 200, true);
  }    

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/user-poll/showByIdPoll",
   *      operationId="showUserPollByIdPoll",
   *      tags={"User Poll"},
   *      summary="Show User Poll By Id Poll",
   *      description="Returns User Poll By Id Poll data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
        <br> - id_poll as a reference to the User Poll data that will be returned
        <br> - limit as limit show data (default 10)
        <br> - page as page show data (default 1)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_poll", type="string", example="1"),
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
  public function showByIdPoll(Request $request)
  {
    $requestData = $request->only([
      'id_poll',
      'limit',
      'page',
    ]);
  
    $idPoll = $requestData['id_poll'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;;
  
    if (!$idPoll) {
      return ResultHelpers::errors('id_poll must be provided.', 400, false);
    }
  
    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;
  
    // Fetch paginated data
    $data = UserPoll::where('id_poll', $idPoll)
      ->where('soft_delete', 0)
      ->skip($offset)
      ->take($limit)
      ->get();

    // Fetch the poll and its associated data as described earlier
    $poll = Poll::where('id_poll', $idPoll)
    ->with('Categories', 'PollListChoice')
    ->first();

    if (!$poll) {
    return ResultHelpers::errors('Poll not found.', 404, false);
    }

    // Load the associated PollListChoice records
    $poll->load(['PollListChoice']);

    // Count the number of selections for each PollListChoice using UserPoll
    foreach ($poll->PollListChoice as $choice) {
    $pollCount = UserPoll::where('id_poll', $idPoll)
        ->where('id_poll_list_choice', $choice->id_poll_list_choice)
        ->count();
    $choice->poll_count = $pollCount;
    }

    // Include the $poll object in the $data response
    $finalData = new \stdClass();
    $finalData->poll_data = $poll;
    $finalData->user_poll = $data;
  
    return ResultHelpers::success($finalData, 200, true);
  }    
}
