<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use App\Models\Categories;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use App\Models\UserQuiz;
use App\Models\UserQuizAnswer;

class QuizController extends Controller
{
    /**
     * Convert base64 icon to file and upload it to public folder.
     *
     * @param string $base64Icon
     * @return string|null
     */
    private function uploadImageFromBase64($base64Icon)
    {
        // Extract the MIME type and base64 data from the input
        list($mime, $base64Data) = explode(';', $base64Icon);
        list(, $base64Data) = explode(',', $base64Data);

        // Determine the file extension based on the MIME type
        $mimeParts = explode('/', $mime);
        $extension = end($mimeParts);

        // Generate a unique filename
        $filename = 'icon_' . time() . '.' . $extension;

        // Convert base64 to binary data and save it to a file
        $decodedData = base64_decode($base64Data);
        $path = 'icons/' . $filename; // Set the desired directory within the public storage folder

        // Save the file to the storage disk
        Storage::disk('public')->put($path, $decodedData);

        // Return the path to the uploaded file
        return $path;
    }

    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/create-edit",
     *      operationId="createEditQuiz",
     *      tags={"Quiz"},
     *      summary="Create / Edit Quiz Data",
     *      description="Returns Quiz Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_quiz</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_categories</b> : id of categories
               <br><b>title</b> : title quiz
               <br><b>subtitle</b> : subtitle quiz
               <br><b>thumbnail</b> : must be base64 string
               <br><b>description</b> : description quiz
               <br><b>today_status</b> : today_status quiz for todays trivia (default 0 = false, 1 = true)
               <br><b>draft</b> : status draft quiz for todays trivia (default 0 = false, 1 = true)
               <br><b>soft_delete</b> : 0 = active, 1 = deleted
               <br>________________________________________________
               <br><br><b>Example of Thumbnail Base64:</b>
               data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz", type="int", example="1"),
     *              @OA\Property(property="id_categories", type="int", example="1"),
     *              @OA\Property(property="title", type="string", example="Which scene in The Empire Strikes Back best showcases Luke's growth as a character?"),
     *              @OA\Property(property="subtitle", type="string", example="STAR WARS"),
     *              @OA\Property(property="thumbnail", type="string", example="base_64string"),
     *              @OA\Property(property="description", type="string", example="lorem ipsum sir dolor amet"),
     *              @OA\Property(property="today_status", type="int", example="0"),
     *              @OA\Property(property="draft", type="int", example="0"),
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
            'id_quiz', // ID Quiz
            'id_categories', // ID Categories
            'title', // title Quiz
            'subtitle', // subtitle Quiz
            'thumbnail', // Thumbnail Quiz using base64
            'description', // description Quiz
            'today_status', // today_status Quiz
            'draft', // draft Quiz
            'soft_delete', // Soft Delete
        ]);

        $idQuiz = $requestData['id_quiz'] ?? null;
        $idCategories = $requestData['id_categories'] ?? null;
        $titleQuiz = $requestData['title'] ?? null;
        $subtitleQuiz = $requestData['subtitle'] ?? null;
        $thumbnailQuiz = $requestData['thumbnail'] ?? null;
        $descriptionQuiz = $requestData['description'] ?? 0;
        $todayStatusQuiz = $requestData['today_status'] ?? 0;
        $draftQuiz = $requestData['draft'] ?? 0;
        $softDeleteQuiz = $requestData['soft_delete'] ?? 0;

        $dataCategories = Categories::where('id_categories', $idCategories)->first();

        if(!$dataCategories){
            return ResultHelpers::errors('Categories Data Not Found from id_categories inputed', 400, false);
        }

        if ($thumbnailQuiz) {
            // Check if thumbnail input data is a valid base64 string
            if (!preg_match('/data:image\/(.*?);base64,/', $thumbnailQuiz)) {
                return ResultHelpers::errors('thumbnail must be a valid base64 string', 400, false);
            }
            
            // Upload the base64 thumbnail to the public folder and get the path
            $thumbnailPath = $this->uploadImageFromBase64($thumbnailQuiz);

            // Generate the URL of the uploaded file
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        } else {
            $thumbnailPath = null;
        }

        if($idQuiz === null){
            // Create a new user account
            $data = new Quiz();
            
            $data->id_categories = $idCategories;
            $data->title = $titleQuiz;
            $data->subtitle = $subtitleQuiz;
            $data->thumbnail = $thumbnailUrl ?? '';
            $data->description = $descriptionQuiz;
            $data->today_status = $todayStatusQuiz;
            $data->draft = $draftQuiz;
            $data->soft_delete = $softDeleteQuiz;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = Quiz::where('id_quiz', $idQuiz)->first();
            if ($data) {
                $data->id_categories = $idCategories;
                $data->title = $titleQuiz;
                $data->subtitle = $subtitleQuiz;
                $data->thumbnail = $thumbnailUrl;
                $data->description = $descriptionQuiz;
                $data->today_status = $todayStatusQuiz;
                $data->draft = $draftQuiz;
                $data->soft_delete = $softDeleteQuiz;
                $data->save();
            }else{
                return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/quiz/show",
     *      operationId="showQuiz",
     *      tags={"Quiz"},
     *      summary="Show Quiz",
     *      description="Returns Quiz data",
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
        $data = Quiz::where('soft_delete', 0)
            ->orderBy('id_quiz', 'DESC')
            ->with('Categories')
            ->get();

        return ResultHelpers::success($data, 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showByCategories",
     *      operationId="showByCategoriesQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Categories Quiz Data",
     *      description="Returns Quiz Data based on id_categories",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_categories as a reference to the categories quiz data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="string", example="1"),
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
    public function showByCategories(Request $request)
    {
        $requestData = $request->only([
            'id_categories', // Facebook ID
        ]);

        $idCategories = $requestData['id_categories'] ?? null;

        if (!$idCategories) {
            return ResultHelpers::errors('id_categories must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Quiz Data with its Relation.
        $data = Quiz::where('id_categories', $idCategories)->with('Categories')->first();

        return ResultHelpers::success($data, 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showById",
     *      operationId="showByIdQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Id Quiz Data",
     *      description="Returns Quiz Data based on id_quiz",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_quiz as a reference to the quiz data that will be returned",
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
    public function showById(Request $request)
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
        $data = Quiz::where('id_quiz', $idQuiz)->with('Categories')->first();
    
        if (!$data) {
            return ResultHelpers::errors('Quiz not found.', 404, false);
        }
    
        // Retrieve the quiz questions for the quiz
        $quizQuestions = QuizQuestions::where('id_quiz', $idQuiz)->get();

        // Shuffle the quiz list choices for each quiz question
        $quizQuestions->each(function ($question) {
            $question->QuizListChoice = QuizListChoice::where('id_quiz_questions', $question->id_quiz_questions)->get()->shuffle();
        });

        // Assign the quiz questions to the quiz
        $data->QuizQuestions = $quizQuestions;

        return ResultHelpers::success($data, 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showTodayQuiz",
     *      operationId="showByTodayStatusQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Today Quiz Data",
     *      description="Returns Quiz Data based on today_status",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - today_status as a reference to the quiz data that will be returned ( 1 = Today Trivia Quiz )",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="today_status", type="string", example="1"),
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
    public function showTodayQuiz(Request $request)
    {
        $requestData = $request->only([
            'today_status', // Facebook ID
        ]);

        $todayStatusQuiz = $requestData['today_status'] ?? null;

        if (!$todayStatusQuiz) {
            return ResultHelpers::errors('today_status must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Quiz Data with its Relation.
        $data = Quiz::where('today_status', $todayStatusQuiz)->with('Categories','QuizQuestions.QuizListChoice')->first();

        return ResultHelpers::success($data, 200, true);
    } 
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showDraftQuiz",
     *      operationId="showByDraftQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Draft Quiz Data",
     *      description="Returns Quiz Data based on draft status",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - draft as a reference to the quiz data that will be returned ( 1 = Drafted Quiz, 0 = Published Quiz )",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="draft", type="string", example="1"),
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
    public function showDraftQuiz(Request $request)
    {
        $requestData = $request->only([
            'draft', // Facebook ID
        ]);

        $draftQuiz = $requestData['draft'] ?? null;

        if (!$draftQuiz) {
            return ResultHelpers::errors('draft status must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Quiz Data with its Relation.
        $data = Quiz::where('draft', $draftQuiz)->with('Categories')->first();

        return ResultHelpers::success($data, 200, true);
    }
}
