<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\PollsImport;

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
        $filename = 'thumbnail_' . time() . '.' . $extension;
  
        // Convert base64 to binary data and save it to a file
        $decodedData = base64_decode($base64Data);
        $path = 'poll_thumbnail/' . $filename; // Set the desired directory within the public storage folder

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
               <br><b>topic</b> : topic quiz
               <br><b>title</b> : title poll
               <br><b>slug</b> : slug poll (should be unique, lowercase, and use dash instead of space or underscore, example: this-is-slug)
               <br><b>subtitle</b> : subtitle poll
               <br><b>images_id</b> : images_id for importing process (nullable)
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
     *              @OA\Property(property="topic", type="string", example="Premier League Players"),
     *              @OA\Property(property="title", type="string", example="Who is your favorite player in the Premier League?"),
     *              @OA\Property(property="slug", type="string", example="slug-poll"),
     *              @OA\Property(property="subtitle", type="string", example="Football"),
     *              @OA\Property(property="images_id", type="string", example=null),
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
        'topic',
        'title', 
        'slug',
        'subtitle', 
        'images_id',
        'thumbnail',
        'description',
        'today_status',
        'draft',
        'soft_delete',
      ]);

      $idPoll = $requestData['id_poll'] ?? null;
      $idCategories = $requestData['id_categories'] ?? null;
      $topicPoll = $requestData['topic'] ?? null;
      $titlePoll = $requestData['title'] ?? null;
      $slugPoll = $requestData['slug'] ?? null;
      $subtitlePoll = $requestData['subtitle'] ?? null;
      $imagesIdPoll = $requestData['images_id'] ?? null;
      $thumbnailPoll = $requestData['thumbnail'] ?? null;
      $descriptionPoll = $requestData['description'] ?? "0";
      $todayStatusPoll = $requestData['today_status'] ?? "0";
      $draftPoll = $requestData['draft'] ?? "0";
      $softDeletePoll = $requestData['soft_delete'] ?? "0";

      if($idCategories){
        $dataCategories = Categories::find($idCategories);
  
        if(!$dataCategories){
          return ResultHelpers::errors('Categories Data Not Found from id_categories inputed', 400, false);
        }
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
      $topicPoll && $data->topic = $topicPoll;
      $titlePoll && $data->title = $titlePoll;
      $slugPoll && $data->slug = $slugPoll;
      $subtitlePoll && $data->subtitle = $subtitlePoll;
      $imagesIdPoll && $data->images_id = $imagesIdPoll;
      $thumbnailUrl && $data->thumbnail = $thumbnailUrl;
      $descriptionPoll && $data->description = $descriptionPoll;
      if ($todayStatusPoll !== null) {
        $data->today_status = $todayStatusPoll;
      }
      if ($draftPoll !== null) {
        $data->draft = $draftPoll;
      }
      if ($softDeletePoll !== null) {
        $data->soft_delete = $softDeletePoll;
      }
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
            <br> - draft as draft show data (default 0 / published) (1 = drafted poll) (100 = all data published / drafted)
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)
            <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="search", type="string", example=null),
     *              @OA\Property(property="id_categories", type="int", example=null),
     *              @OA\Property(property="draft", type="int", example="0"),
     *              @OA\Property(property="limit", type="int", example="10"),
     *              @OA\Property(property="page", type="int", example="1"),
     *              @OA\Property(property="sort", type="int", example="1"),
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
        'search',
        'id_categories',
        'draft',
        'limit',
        'page',
        'sort',
      ]);

      $search = $requestData['search'] ?? null;
      $idCategories = $requestData['id_categories'] ?? null;
      $draft = $requestData['draft'] ?? 0;
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;
      $sort = $requestData['sort'] ?? 1; // Default sort is 1 (1 = most recent, 2 = least recent, 3 = alphabetical)

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      // Initialize the query builder for the main data retrieval
      $query = Poll::where('soft_delete', 0)
          ->with('Categories')
          ->withCount('PollListChoice'); // Load the total poll list choices count 

      if($sort === 1){
        $query->orderBy('created_at', 'DESC');
      }else if($sort === 2 ){
        $query->orderBy('created_at', 'ASC');
      }else if($sort === 3 ){
        $query->orderBy('title', 'ASC');
      }else{ 
        $query->orderBy('created_at', 'DESC');
      }

      $query->skip($offset)
          ->take($limit);

      // Initialize the query builder for the total records count
      $totalRecordsQuery = Poll::where('soft_delete', 0);
  
      if ($search) {
        $query->orWhere(function ($query) use ($search) {
            $query->where('topic', 'LIKE', '%' . $search . '%')
                  ->orWhere('title', 'LIKE', '%' . $search . '%')
                  ->orWhere('subtitle', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
        });
    
        $totalRecordsQuery->orWhere(function ($query) use ($search) {
            $query->where('topic', 'LIKE', '%' . $search . '%')
                  ->orWhere('title', 'LIKE', '%' . $search . '%')
                  ->orWhere('subtitle', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
        });
      }    
  
      if ($idCategories) {
          $query->where('id_categories', $idCategories);
          $totalRecordsQuery->where('id_categories', $idCategories);
      }    
  
      // Conditionally add the 'draft' where clause if draft is not 100
      if ($draft != 100) {
          $query->where('draft', $draft);
          $totalRecordsQuery->where('draft', $draft);
      }
  
      // Execute the query to retrieve data
      $data = $query->get();
  
      // Execute the query to get the total records count
      $totalRecords = $totalRecordsQuery->count();
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
     *      description="Returns Poll Data based on id_categories or slug",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_categories as a reference to the categories poll data that will be returned
                <br> - slug_categories as a reference to the categories poll data that will be returned
                <br> - draft as a reference to the categories poll data that will be returned, use (2) for all published and draft, default (0) for published polls
                <br> - limit as limit show data (default 10)
                <br> - page as page show data (default 1)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="string", example="1"),
     *              @OA\Property(property="slug_categories", type="string", example=null),
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
    public function showByCategories(Request $request)
    {
      $requestData = $request->only([
        'id_categories', 
        'slug_categories',
        'draft',
        'limit',
        'page',
      ]);

      $draft = $requestData['draft'] ?? 0; // Default draft is 0 / published
      $limit = $requestData['limit'] ?? 10; // Default limit is 10
      $page = $requestData['page'] ?? 1;

      // Calculate the offset based on the page and limit
      $offset = ($page - 1) * $limit;

      $idCategories = $requestData['id_categories'] ?? null;
      $slugCategories = $requestData['slug_categories'] ?? null;

      if (!$idCategories && !$slugCategories) {
        return ResultHelpers::errors('id_categories or slug categories must be provided.', 400, false);
      }
        
      $query = Poll::leftJoin('categories', 'categories.id_categories', '=', 'poll.id_categories')
      ->where('poll.soft_delete', 0);

      if ($idCategories !== null) {
        // Add a filter for id_categories if idCategories is not null
        $query->where('poll.id_categories', $idCategories);
      }
      
      if ($slugCategories !== null) {
        $query->where('categories.slug', $slugCategories);
      }
      
      if ($draft !== 2){
        $query->where('poll.draft', $draft);
      }
      

      // Check if the user already exists in the table
      // Return Created / Updated Quiz Data with its Relation.
      $data = $query->with('Categories')
        ->orderBy('poll.id_poll', 'DESC')
        ->skip($offset)
        ->take($limit)
        ->get([
          'poll.id_poll',
          'poll.id_categories',
          'poll.topic',
          'poll.title',
          'poll.slug',
          'poll.subtitle',
          'poll.images_id',
          'poll.thumbnail',
          'poll.description',
          'poll.today_status',
          'poll.draft',
          'poll.soft_delete',
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
     *      path="/poll/showById",
     *      operationId="showByIdPoll",
     *      tags={"Poll"},
     *      summary="Show BY Id Poll Data",
     *      description="Returns Poll Data based on id_poll",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - id_poll as a reference to the poll data that will be returned
                <br> - slug as a reference to the poll data that will be returned
                <br> - id_user as a reference to the poll data that will be returned (if the user already logged in)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_poll", type="string", example="1"),
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
            'id_poll',
            'slug',
        ]);

        $idPoll = $requestData['id_poll'] ?? null;
        $slugPoll = $requestData['slug'] ?? null;

        if (!$idPoll && !$slugPoll) {
          return ResultHelpers::errors('id_poll or slug poll must be provided.', 400, false);
        }
        
        $query = Poll::where('poll.soft_delete', 0);
        $poll = null;
    
        if ($idPoll) {
          // Check by idPoll if provided
          $query->where('id_poll', $idPoll);
        } elseif ($slugPoll) {
          // Check by slugPoll if provided
          $query->where('slug', $slugPoll);
        }

        $poll = $query->with('Categories', 'PollListChoice')->first();

        if (!$poll) {
          return ResultHelpers::errors('Poll not found.', 404, false);
        }

        // Calculate the total number of votes for all choices for this poll
        $totalVotes = UserPoll::whereIn('id_poll', [$poll->id_poll])->count();

        // Load the associated PollListChoice records
        $poll->load(['PollListChoice']);
        
        // Calculate and set the percentage for each choice
        foreach ($poll->PollListChoice as $choice) {
            $pollCount = UserPoll::where('id_poll_list_choice', $choice->id_poll_list_choice)
                ->count();
            
            // Calculate the percentage (rounded to 2 decimal places)
            $percentage = $totalVotes > 0 ? round(($pollCount / $totalVotes) * 100, 2) : 0;
            
            $choice->poll_count = $pollCount;
            $choice->percentage = $percentage;
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
        
        // Get a random poll where today_status = 1
        $data = Poll::where('today_status', $todayStatusPoll)->where('soft_delete', 0)->inRandomOrder()->with('Categories', 'PollListChoice')->first();

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
            <br> - slug_categories as references poll if you want to filter by categories
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
    public function showUserLoginPoll(Request $request)
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


      $query = Poll::leftJoin('user_poll', function ($join) use ($idUser) {
        $join->on('poll.id_poll', '=', 'user_poll.id_poll')
            ->where('user_poll.id_user', '=', $idUser);
        })->leftJoin('categories', 'categories.id_categories', '=', 'poll.id_categories')
        ->where('poll.soft_delete', 0)
        ->where('poll.draft', 0);

        if ($idCategories !== null) {
          // Add a filter for id_categories if idCategories is not null
          $query->where('poll.id_categories', $idCategories);
        }elseif ($slugCategories !==null) {
          $query->where('categories.slug', $slugCategories);
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



    

    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/importPoll",
     *      operationId="importPoll",
     *      tags={"Poll"},
     *      summary="Import Poll Data",
     *      description="Returns Poll Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                 <br><b>file_poll</b> : file excel of poll data that will be imported",
    *          required=true,
    *          @OA\MediaType(
    *              mediaType="multipart/form-data",
    *              @OA\Schema(
    *                  required={"file_poll"},
    *                  @OA\Property(
    *                      property="file_poll",
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
    public function importPoll(Request $request){
      $requestData = $request->only([
        'file_poll',
      ]);

      $this->validate($request, [
        'file_poll' => 'required|mimes:xlsx,txt,csv',
      ]);

      $filePoll = $requestData['file_poll'] ?? null;

      Excel::import(new PollsImport, $filePoll);

      $latestPoll = Poll::latest()->first();
      if ($latestPoll) {
        $latestPoll->today_status = 1;
        $latestPoll->save();
      }    

      return ResultHelpers::success("Success Importing Data", 200, true);
    }


    

    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/poll/importImagesPoll",
     *      operationId="importImagesPoll",
     *      tags={"Poll"},
     *      summary="Import Images Poll Data",
     *      description="Returns Images Poll Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                 <br><b>zip_file</b> : file zip of images with names of images_id references for images poll that will be imported",
    *          required=true,
    *          @OA\MediaType(
    *              mediaType="multipart/form-data",
    *              @OA\Schema(
    *                  required={"zip_file"},
    *                  @OA\Property(
    *                      property="zip_file",
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
    public function importImagesPoll(Request $request){
      $requestData = $request->only([
        'zip_file',
      ]);

      $this->validate($request, [
        'zip_file' => 'required|mimes:zip,rar',
      ]);

      $zipFile = $request->file('zip_file');
      $extractPath = storage_path('app/temp_zip_extract');

      $zipFile->move($extractPath, $zipFile->getClientOriginalName());

      // Extract images from the zip file
      $zip = new \ZipArchive();
      $zip->open($extractPath . '/' . $zipFile->getClientOriginalName());
      $images = [];

      $errors = [];

      for ($i = 0; $i < $zip->numFiles; $i++) {
          $filename = $zip->getNameIndex($i);
          $imageId = pathinfo($filename, PATHINFO_FILENAME);
          $extension = pathinfo($filename, PATHINFO_EXTENSION);

          if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
              $path = "images/{$imageId}.{$extension}";
              // Move the image to your desired storage directory
              Storage::disk('public')->put($path, $zip->getFromIndex($i));
              // Generate the URL of the uploaded file
              $imageUrl = Storage::disk('public')->url($path);

              $data = Poll::where('poll.images_id', $imageId)->first();
              if ($data) {
                  $data->thumbnail = $imageUrl;
                  $data->save();
              }
              
              $images[] = [
                'image_id' => $imageId,
                'file_path' => $filename,
                'extension' => $extension,
                'image_url' => $imageUrl,
                'data' => $data,
            ];
          } else {
              $errors[] = "File {$filename} is not an image";
          }
      }

      $zip->close();

      // Clean up the extracted files
      unlink($extractPath . '/' . $zipFile->getClientOriginalName());

      $results = [
          'message' => "Complete Importing Images Data",
          'images' => $images,
          'errors' => $errors,
      ];

      return ResultHelpers::success($results , 200, true);
    }
}