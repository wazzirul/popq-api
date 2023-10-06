<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\UserPoll;

class PollListChoiceController extends Controller
{
    //
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll-list-choice/create-edit",
     *      operationId="createEditPollListChoice",
     *      tags={"Poll List Choice"},
     *      summary="Create / Edit Poll List Choice Data",
     *      description="Returns Poll List Choice Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_poll_list_choice</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_poll</b> : id poll from table poll
               <br><b>poll_choice</b> : string of poll_choice example (Cristiano Ronaldo)
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_poll_list_choice", type="int", example="1"),
     *              @OA\Property(property="id_poll", type="int", example="1"),
     *              @OA\Property(property="poll_choice", type="string", example="Cristiano Ronaldo"),
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
            'id_poll_list_choice', 
            'id_poll', 
            'poll_choice', 
            'soft_delete', 
        ]);

        $idPollListChoice = $requestData['id_poll_list_choice'] ?? null;
        $idPoll = $requestData['id_poll'] ?? null;
        $poll_choice = $requestData['poll_choice'] ?? '';
        $softDelete = $requestData['soft_delete'] ?? 0;
        
        $dataPoll = Poll::where('id_poll', $idPoll)->first();
        if(!$dataPoll){
            return ResultHelpers::errors('Poll Data Not Found from id_poll inputed', 400, false);
        }

        if($idPollListChoice === null){
            // Create a new user account
            $data = new PollListChoice();
            
            $idPoll && $data->id_poll = $idPoll;
            $poll_choice && $data->poll_choice = $poll_choice;
            if ($softDelete !== null) {
              $data->soft_delete = $softDelete;
            }
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = PollListChoice::where('id_poll_list_choice', $idPollListChoice)->first();
            if ($data) {
                $idPoll && $data->id_poll = $idPoll;
                $poll_choice && $data->poll_choice = $poll_choice;
                if ($softDelete !== null) {
                  $data->soft_delete = $softDelete;
                }
                $data->save();
            }else{
                return ResultHelpers::errors('Poll List Choice Data Not Found from id_poll_list_choice inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }


    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll-list-choice/create-edit-v2",
     *      operationId="createEditPollListChoiceV2",
     *      tags={"Poll List Choice"},
     *      summary="Create / Edit Poll List Choice Data",
     *      description="Returns Poll List Choice Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_poll_list_choice</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_poll</b> : id poll from table poll
               <br><b>poll_choice</b> : string of poll_choice example (Cristiano Ronaldo)
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
    *          required=true,
    *          @OA\JsonContent(
    *              type="object",
    *              @OA\Property(property="data", type="array", @OA\Items(
    *                  @OA\Property(property="id_poll_list_choice", type="int", example="1"),
    *                  @OA\Property(property="id_poll", type="int", example="1"),
    *                  @OA\Property(property="poll_choice", type="string", example="Cristiano Ronaldo"),
    *                  @OA\Property(property="soft_delete", type="int", example="0"),
    *              )),
    *          )
    *      ),
    *      @OA\Response(
    *          response=200,
    *          description="Successful Operation",
    *      ),
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
            $idPollListChoice = $data['id_poll_list_choice'] ?? null;
            $idPoll = $data['id_poll'] ?? null;
            $poll_choice = $data['poll_choice'] ?? '';
            $softDelete = $data['soft_delete'] ?? 0;

            // $dataPoll = Poll::where('id_poll', $idPoll)->first();
            // if(!$dataPoll){
            //     return ResultHelpers::errors('Poll Data Not Found from id_poll inputed', 400, false);
            // }
    
            if ($idPollListChoice === null) {
                $newData = new PollListChoice();
                
                $idPoll && $newData->id_poll = $idPoll;
                $poll_choice && $newData->poll_choice = $poll_choice;
                if ($softDelete !== null) {
                  $newData->soft_delete = $softDelete;
                }
                $newData->save();
                $storedData[] = $newData;
            } else {
                $existingData = PollListChoice::where('id_poll_list_choice', $idPollListChoice)->first();
                if ($existingData) {
                    $idPoll && $existingData->id_poll = $idPoll;
                    $poll_choice && $existingData->poll_choice = $poll_choice;
                    if ($softDelete !== null) {
                      $existingData->soft_delete = $softDelete;
                    }
                    $existingData->save();
                    $storedData[] = $existingData;
                } else {
                    return ResultHelpers::errors('Poll List Choice Data Not Found from id_poll_list_choice inputed', 400, false);
                }
            }
        }
    
        return ResultHelpers::success($storedData, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll-list-choice/showByPoll",
     *      operationId="showPollListChoiceByPoll",
     *      tags={"Poll List Choice"},
     *      summary="Show Poll List Choice BY ID Poll",
     *      description="Returns Poll List Choice Data based on id_poll",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_poll as a reference to the poll list choice data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_poll", type="string", example="1"),
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
    public function showByPoll(Request $request)
    {
        $requestData = $request->only([
            'id_poll',
        ]);

        $idPoll = $requestData['id_poll'] ?? null;

        if (!$idPoll) {
            return ResultHelpers::errors('id_poll must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Poll Data with its Relation.
        $poll = Poll::where('id_poll', $idPoll)
        ->with(['Categories', 'PollListChoice' => function ($query) {
            $query->where('soft_delete', "0");
        }])
        ->first();


        if (!$poll) {
          return ResultHelpers::errors('Poll not found.', 404, false);
        }

        // Load the associated PollListChoice records
        $poll->load(['PollListChoice']);
    
        // Count the number of selections for each PollListChoice using UserPoll
        foreach ($poll->PollListChoice as $choice) {
            $pollCount = UserPoll::where('id_poll_list_choice', $choice->id_poll_list_choice)
                ->count();
            $choice->poll_count = $pollCount;
        }
    
        return ResultHelpers::success($poll, 200, true);
    }    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll-list-choice/showById",
     *      operationId="showPollListChoiceByID",
     *      tags={"Poll List Choice"},
     *      summary="Show Poll List Choice BY ID Poll List Choice",
     *      description="Returns Poll List Choice Data based on id_poll_list_choice",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_poll_list_choice as a reference to the poll list choice data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_poll_list_choice", type="string", example="1"),
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
            'id_poll_list_choice',
        ]);

        $idPoll = $requestData['id_poll_list_choice'] ?? null;

        if (!$idPoll) {
            return ResultHelpers::errors('id_poll_list_choice must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Poll Data with its Relation.
        $data = PollListChoice::where('id_poll_list_choice', $idPoll)->where('soft_delete', 0)->with('Poll')->first();

        return ResultHelpers::success($data, 200, true);
    }  
}
