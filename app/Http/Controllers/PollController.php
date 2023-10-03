<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\Users;
use App\Models\UserPoll;

class PollController extends Controller
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
     *      path="/poll/create-edit",
     *      operationId="createEditPoll",
     *      tags={"Poll"},
     *      summary="Create / Edit Poll Data",
     *      description="Returns Poll Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_poll</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>id_categories</b> : id of categories
               <br><b>title</b> : title poll
               <br><b>slug</b> : slug poll (should be unique, lowercase, and use dash instead of space or underscore, example: this-is-slug)
               <br><b>subtitle</b> : subtitle poll
               <br><b>thumbnail</b> : must be base64 string
               <br><b>description</b> : description poll
               <br><b>today_status</b> : today_status poll for todays trivia (default 0 = false, 1 = true)
               <br><b>draft</b> : status draft poll for todays trivia (default 0 = false, 1 = true)
               <br><b>soft_delete</b> : 0 = active, 1 = deleted
               <br>________________________________________________
               <br><br><b>Example of Thumbnail Base64:</b>
               data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_poll", type="int", example="1"),
     *              @OA\Property(property="id_categories", type="int", example="1"),
     *              @OA\Property(property="title", type="string", example="Who is your favorite player in the Premier League?"),
     *              @OA\Property(property="slug", type="string", example="slug-poll"),
     *              @OA\Property(property="subtitle", type="string", example="Football"),
     *              @OA\Property(property="thumbnail", type="string", example="base_64string"),
     *              @OA\Property(property="description", type="string", example="Premier League"),
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
        'id_poll', 
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

      $idPoll = $requestData['id_poll'] ?? null;
      $idCategories = $requestData['id_categories'] ?? null;
      $titlePoll = $requestData['title'] ?? null;
      $slugPoll = $requestData['slug'] ?? null;
      $subtitlePoll = $requestData['subtitle'] ?? null;
      $thumbnailPoll = $requestData['thumbnail'] ?? null;
      $descriptionPoll = $requestData['description'] ?? 0;
      $todayStatusPoll = $requestData['today_status'] ?? 0;
      $draftPoll = $requestData['draft'] ?? 0;
      $softDeletePoll = $requestData['soft_delete'] ?? 0;

      $dataCategories = Categories::find($idCategories);

      if(!$dataCategories){
        return ResultHelpers::errors('Categories Data Not Found from id_categories inputed', 400, false);
      }
      
      $allPoll = Poll::where('soft_delete', 0)->get();
      foreach ($allPoll as $key => $value) {
          if($value->slug == $slugPoll && $value->id_poll != $idPoll){
              return ResultHelpers::errors('Slug Poll Already Exists', 400, false);
          }
      }

      $thumbnailUrl = null;
      if ($thumbnailPoll) {
        // Check if thumbnail input data is a valid base64 string
        if (!preg_match('/data:image\/(.*?);base64,/', $thumbnailPoll)) {
          return ResultHelpers::errors('thumbnail must be a valid base64 string', 400, false);
        }
        
        // Upload the base64 thumbnail to the public folder and get the path
        $thumbnailPath = $this->uploadImageFromBase64($thumbnailPoll);

        // Generate the URL of the uploaded file
        $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
      } else {
        $thumbnailPath = null;
      }

      if($idPoll === null){
        // Create a new user account
        $data = new Poll();
      }else{
        // Check if the user already exists in the table
        $data = Poll::where('id_poll', $idPoll)->first();
        if (!$data) {
          return ResultHelpers::errors('Poll Data Not Found from id_poll inputed', 400, false);
        }
      }
      
      $idCategories && $data->id_categories = $idCategories;
      $titlePoll && $data->title = $titlePoll;
      $slugPoll && $data->slug = $slugPoll;
      $subtitlePoll && $data->subtitle = $subtitlePoll;
      $thumbnailUrl && $data->thumbnail = $thumbnailUrl;
      $descriptionPoll && $data->description = $descriptionPoll;
      $todayStatusPoll && $data->today_status = $todayStatusPoll;
      $draftPoll && $data->draft = $draftPoll;
      $softDeletePoll && $data->soft_delete = $softDeletePoll;
      $data->save();

      return ResultHelpers::success($data, 200, true);
    }
    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/show",
     *      operationId="showPoll",
     *      tags={"Poll"},
     *      summary="Show Poll",
     *      description="Returns Poll data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
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
        'limit',
        'page',
      ]);

      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      $data = Poll::where('soft_delete', 0)
        ->orderBy('id_poll', 'DESC')
        ->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Poll::where('soft_delete', 0)->count();
      $totalPages = ceil($totalRecords / $limit);
  
      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }   
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/showByCategories",
     *      operationId="showByCategoriesPoll",
     *      tags={"Poll"},
     *      summary="Show BY Categories Poll Data",
     *      description="Returns Poll Data based on id_categories",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_categories as a reference to the categories poll data that will be returned",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="string", example="1"),
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
        'id_categories', // Facebook ID
        'limit',
        'page',
      ]);

      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      $idCategories = $requestData['id_categories'] ?? null;

      if (!$idCategories) {
        return ResultHelpers::errors('id_categories must be provided.', 400, false);
      }

      // Check if the user already exists in the table
      // Return Created / Updated Poll Data with its Relation.
      $data = Poll::where('id_categories', $idCategories)
        ->where('soft_delete', 0)->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Poll::where('id_categories', $idCategories)
        ->where('soft_delete', 0)->count();
      $totalPages = ceil($totalRecords / $limit);
  
      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/showById",
     *      operationId="showByIdPoll",
     *      tags={"Poll"},
     *      summary="Show BY Id Poll Data",
     *      description="Returns Poll Data based on id_poll",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_poll as a reference to the poll data that will be returned",
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
    public function showById(Request $request)
    {
        $requestData = $request->only([
            'id_poll', // Facebook ID
        ]);

        $idPoll = $requestData['id_poll'] ?? null;

        if (!$idPoll) {
          return ResultHelpers::errors('id_poll must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Poll Data with its Relation.
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
            $pollCount = UserPoll::where('id_poll_list_choice', $choice->id_poll_list_choice)
                ->count();
            $choice->poll_count = $pollCount;
        }
    
        return ResultHelpers::success($poll, 200, true);
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/showTodayPoll",
     *      operationId="showByTodayStatusPoll",
     *      tags={"Poll"},
     *      summary="Show BY Today Poll Data",
     *      description="Returns Poll Data based on today_status",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - today_status as a reference to the poll data that will be returned ( 1 = Today Trivia Poll, 0 = Not Today Trivia Poll )",
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
    public function showTodayPoll(Request $request)
    {
        $requestData = $request->only([
            'today_status', // Facebook ID
        ]);

        $todayStatusPoll = $requestData['today_status'] ?? null;

        if (!$todayStatusPoll) {
            return ResultHelpers::errors('today_status must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Poll Data with its Relation.
        $data = Poll::where('today_status', $todayStatusPoll)->with('Categories','PollListChoice')->first();

        return ResultHelpers::success($data, 200, true);
    } 
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/showDraftPoll",
     *      operationId="showByDraftPoll",
     *      tags={"Poll"},
     *      summary="Show BY Draft Poll Data",
     *      description="Returns Poll Data based on draft status",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - draft as a reference to the poll data that will be returned ( 1 = Drafted Poll, 0 = Published Poll )",
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
    public function showDraftPoll(Request $request)
    {
      $requestData = $request->only([
          'draft', // Facebook ID
          'limit',
          'page',
      ]);

      $draftPoll = $requestData['draft'] ?? null;
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      if (!$draftPoll) {
          return ResultHelpers::errors('draft status must be provided.', 400, false);
      }

      // Check if the user already exists in the table
      // Return Created / Updated Poll Data with its Relation.
      $data = Poll::where('draft', $draftPoll)
        ->where('soft_delete', 0)
        ->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Poll::where('draft', $draftPoll)
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
     *      path="/poll/showUserLoginPoll",
     *      operationId="showUserLoginPoll",
     *      tags={"Poll"},
     *      summary="Show Poll",
     *      description="Returns Poll data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
            <br> - id_user as references poll already submitted
            <br> - id_categories as references poll if you want to filter by categories
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="int", example="1"),
     *              @OA\Property(property="id_categories", type="int", example="null"),
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
    public function showUserLoginPoll(Request $request)
    {
      $requestData = $request->only([
        'id_user',
        'id_categories',
        'limit',
        'page',
      ]);

      $idUser = $requestData['id_user'] ?? null; // Default limit is null
      $idCategories = $requestData['id_categories'] ?? null; // Default limit is null
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


      $query = Poll::leftJoin('user_poll', function ($join) use ($idUser) {
        $join->on('poll.id_poll', '=', 'user_poll.id_poll')
            ->where('user_poll.id_user', '=', $idUser);
        })
        ->where('poll.soft_delete', 0);

      if ($idCategories !== null) {
          // Add a filter for id_categories if idCategories is not null
          $query->where('poll.id_categories', $idCategories);
      }

      $data = $query->orderBy('poll.id_poll', 'DESC')
        ->select('poll.*', 'user_poll.id_user_poll', 'user_poll.id_user', 'user_poll.id_poll_list_choice', 'user_poll.date_time')
        ->with('Categories')
        ->skip($offset)
        ->take($limit)
        ->get();

      $totalRecords = Poll::where('soft_delete', 0)->count();
      $totalPages = ceil($totalRecords / $limit);
  
      return ResultHelpers::success([
        'data' => $data,
        'total_pages' => $totalPages,
      ], 200, true);
    }   
}
