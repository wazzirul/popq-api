<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Poll;
use App\Models\PollListChoice;

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
            
            $data->id_poll = $idPoll;
            $data->poll_choice = $poll_choice;
            $data->soft_delete = $softDelete;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = PollListChoice::where('id_poll_list_choice', $idPollListChoice)->first();
            if ($data) {
                $data->id_poll = $idPoll;
                $data->poll_choice = $poll_choice;
                $data->soft_delete = $softDelete;
                $data->save();
            }else{
                return ResultHelpers::errors('Poll List Choice Data Not Found from id_poll_list_choice inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }
}
