<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\QuizPollImport;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\Users;
use App\Models\UserPoll;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;

class ImportController extends Controller
{
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/import/importTriviaPoll",
     *      operationId="importTriviaPoll",
     *      tags={"Import"},
     *      summary="import Trivia & Poll Data",
     *      description="route / url for import trivia & poll data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                 <br><b>file_import</b> : file excel of trivia & poll data that will be imported",
    *          required=true,
    *          @OA\MediaType(
    *              mediaType="multipart/form-data",
    *              @OA\Schema(
    *                  required={"file_import"},
    *                  @OA\Property(
    *                      property="file_import",
    *                      type="string",
    *                      format="binary",
    *                  ),
    *              )
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
    public function importTriviaPoll(Request $request){
      $requestData = $request->only([
        'file_import',
      ]);

      $this->validate($request, [
        'file_import' => 'required|mimes:xlsx',
      ]);

      $fileImport = $requestData['file_import'] ?? null;
      Excel::import(new QuizPollImport, $fileImport);
      return ResultHelpers::success("Success Importing Data", 200, true);
    }
}
