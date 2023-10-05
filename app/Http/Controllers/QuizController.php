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
use App\Models\Users;
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
               <br><b>slug</b> : slug poll (should be unique, lowercase, and use dash instead of space or underscore, example: this-is-slug)
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
     *              @OA\Property(property="slug", type="string", example="slug-quiz"),
     *              @OA\Property(property="subtitle", type="string", example="Star Wars"),
     *              @OA\Property(property="thumbnail", type="string", example="base_64string"),
     *              @OA\Property(property="description", type="string", example="The Last Jedi"),
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
            'id_quiz', 
            'id_categories', 
            'title', 
            'slug',
            'subtitle',
            'thumbnail',
            'description',
            'today_status',
            'draft',
            'soft_delete',
        ]);

        $idQuiz = $requestData['id_quiz'] ?? null;
        $idCategories = $requestData['id_categories'] ?? null;
        $titleQuiz = $requestData['title'] ?? null;
        $slugQuiz = $requestData['slug'] ?? null;
        $subtitleQuiz = $requestData['subtitle'] ?? null;
        $thumbnailQuiz = $requestData['thumbnail'] ?? null;
        $descriptionQuiz = $requestData['description'] ?? 0;
        $todayStatusQuiz = $requestData['today_status'] ?? 0;
        $draftQuiz = $requestData['draft'] ?? 0;
        $softDeleteQuiz = $requestData['soft_delete'] ?? 0;

        if($idCategories){
          $dataCategories = Categories::find($idCategories);    
          if(!$dataCategories){
            return ResultHelpers::errors('Categories Data Not Found from id_categories inputed', 400, false);
          }
        }
      
        $allQuiz = Quiz::where('soft_delete', 0)->get();
        foreach ($allQuiz as $key => $value) {
            if($value->slug == $slugQuiz && $value->id_quiz != $idQuiz){
                return ResultHelpers::errors('Slug Quiz Already Exists', 400, false);
            }
        }

        // Generate the URL of the uploaded file
        $thumbnailUrl = null;
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
        }else{
            // Check if the user already exists in the table
            $data = Quiz::where('id_quiz', $idQuiz)->first();
            if (!$data) {
              return ResultHelpers::errors('Quiz Data Not Found from id_quiz inputed', 400, false);
            }
        }
            
        $idCategories && $data->id_categories = $idCategories;
        $titleQuiz && $data->title = $titleQuiz;
        $slugQuiz && $data->slug = $slugQuiz;
        $subtitleQuiz && $data->subtitle = $subtitleQuiz;
        $thumbnailUrl && $data->thumbnail = $thumbnailUrl;
        $descriptionQuiz && $data->description = $descriptionQuiz;
        if ($todayStatusQuiz !== null) {
          $data->today_status = $todayStatusQuiz;
        }
        if ($draftQuiz !== null) {
          $data->draft = $draftQuiz;
        }
        if ($softDeleteQuiz !== null) {
          $data->soft_delete = $softDeleteQuiz;
        }
        $data->save();

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/show",
     *      operationId="showQuiz",
     *      tags={"Quiz"},
     *      summary="Show Quiz",
     *      description="Returns Quiz data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
            <br> - draft as draft show data (default 0 / published) (1 = drafted quiz)
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="draft", type="int", example="0"),
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
        'draft',
        'limit',
        'page',
      ]);

      $draft = $requestData['draft'] ?? 0;
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      $data = Quiz::where('soft_delete', 0)
        ->where('draft', $draft)
        ->orderBy('id_quiz', 'DESC')
        ->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Quiz::where('soft_delete', 0)->count();
      $totalPages = ceil($totalRecords / $limit);
  
      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showByCategories",
     *      operationId="showByCategoriesQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Categories Quiz Data",
     *      description="Returns Quiz Data based on id_categories and slug_categories",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_categories as a reference to the categories quiz data that will be returned
                <br> - slug_categories as a reference to the categories quiz data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="string", example="1"),
     *              @OA\Property(property="slug_categories", type="string", example=null),
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
    public function showByCategories(Request $request)
    {
      $requestData = $request->only([
        'id_categories',
        'slug_categories',
        'limit',
        'page',
        ]);

      $idCategories = $requestData['id_categories'] ?? null;
      $slugCategories = $requestData['slug_categories'] ?? null;
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      if (!$idCategories && !$slugCategories) {
          return ResultHelpers::errors('id_categories or slug_categories must be provided.', 400, false);
      }

      $query = Quiz::leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
      ->where('quiz.soft_delete', 0);

      if ($idCategories !== null) {
        // Add a filter for id_categories if idCategories is not null
        $query->where('quiz.id_categories', $idCategories);
      }elseif ($slugCategories !==null) {
        $query->where('categories.slug', $slugCategories);
      }

      // Check if the user already exists in the table
      // Return Created / Updated Quiz Data with its Relation.
      $data = $query->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get([
          'quiz.id_quiz',
          'quiz.id_categories',
          'quiz.title',
          'quiz.slug',
          'quiz.subtitle',
          'quiz.thumbnail',
          'quiz.description',
          'quiz.today_status',
          'quiz.draft',
          'quiz.soft_delete',
        ]);

      $totalRecords = $query->count();
      $totalPages = ceil($totalRecords / $limit);

      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showById",
     *      operationId="showByIdQuiz",
     *      tags={"Quiz"},
     *      summary="Show BY Id Quiz Data",
     *      description="Returns Quiz Data based on id_quiz / slug",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_quiz as a reference to the quiz data that will be returned
                <br> - slug as a reference to the quiz data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_quiz", type="string", example="1"),
     *              @OA\Property(property="slug", type="string", example=null),
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
            'id_quiz',
            'slug',
        ]);

        $idQuiz = $requestData['id_quiz'] ?? null;
        $slugQuiz = $requestData['slug'] ?? null;

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
    
        if (!$data) {
            return ResultHelpers::errors('Quiz not found.', 404, false);
        }
    
        // Retrieve the quiz questions for the quiz
        $quizQuestions = QuizQuestions::where('id_quiz', $data->id_quiz)->get();

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
    public function showDraftQuiz(Request $request)
    {
      $requestData = $request->only([
        'draft', // Facebook ID
        'limit',
        'page',
      ]);

      $draftQuiz = $requestData['draft'] ?? null;
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      if (!$draftQuiz) {
          return ResultHelpers::errors('draft status must be provided.', 400, false);
      }

      // Check if the user already exists in the table
      // Return Created / Updated Quiz Data with its Relation.
      $data = Quiz::where('draft', $draftQuiz)
        ->where('soft_delete', 0)
        ->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Quiz::where('draft', $draftQuiz)
        ->where('soft_delete', 0)
        ->count();
      $totalPages = ceil($totalRecords / $limit);

      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/quiz/showUserLoginQuiz",
     *      operationId="showUserLoginQuiz",
     *      tags={"Quiz"},
     *      summary="Show Quiz",
     *      description="Returns Quiz data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
            <br> - id_user as references quiz already submitted or not (default 10)
            <br> - id_categories as references quiz if you want to filter by categories
            <br> - slug_categories as references quiz if you want to filter by categories
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="int", example="1"),
     *              @OA\Property(property="id_categories", type="int", example=null),
     *              @OA\Property(property="slug_categories", type="string", example=null),
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
    public function showUserLoginQuiz(Request $request)
    {
      $requestData = $request->only([
        'id_user',
        'id_categories',
        'slug_categories',
        'limit',
        'page',
      ]);

      $idUser = $requestData['id_user'] ?? null; // Default limit is null
      $idCategories = $requestData['id_categories'] ?? null; // Default limit is null
      $slugCategories = $requestData['slug_categories'] ?? null; // Default limit is null
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      $user = Users::where('id_user', $idUser)->first();

      if (!$user) {
        return ResultHelpers::error('User not found', 404);
      }else if($user->soft_delete == 1){
        return ResultHelpers::error('User has been deleted', 404);
      }else if($user->status == 1){
        return ResultHelpers::error('User has been blocked', 404);
      }

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;
      
      $query = Quiz::leftJoin('user_quiz', function ($join) use ($idUser) {
        $join->on('quiz.id_quiz', '=', 'user_quiz.id_quiz')
            ->where('user_quiz.id_user', '=', $idUser);
        })->leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
      ->where('quiz.soft_delete', 0);
    

      if ($idCategories !== null) {
        // Add a filter for id_categories if idCategories is not null
        $query->where('quiz.id_categories', $idCategories);
      }elseif ($slugCategories !==null) {
        $query->where('categories.slug', $slugCategories);
      }

      $data = $query->orderBy('quiz.id_quiz', 'DESC')
        ->with('Categories')
        ->select('quiz.*','user_quiz.id_user', 'user_quiz.answered', 'user_quiz.status', 'user_quiz.result')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = $query->orderBy('quiz.id_quiz', 'DESC')->with('Categories')->select('quiz.*','user_quiz.id_user', 'user_quiz.answered', 'user_quiz.status', 'user_quiz.result')->count();
      $totalPages = ceil($totalRecords / $limit);
  
      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }    
}
