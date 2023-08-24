<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
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
     *              @OA\Property(property="subtitle", type="string", example="Football"),
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
            'id_poll', // ID Poll
            'id_categories', // ID Categories
            'title', // title Poll
            'subtitle', // subtitle Poll
            'thumbnail', // Thumbnail Poll using base64
            'description', // description Poll
            'today_status', // today_status Poll
            'draft', // draft Poll
            'soft_delete', // Soft Delete
        ]);

        $idPoll = $requestData['id_poll'] ?? null;
        $idCategories = $requestData['id_categories'] ?? null;
        $titlePoll = $requestData['title'] ?? null;
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
            
            $data->id_categories = $idCategories;
            $data->title = $titlePoll;
            $data->subtitle = $subtitlePoll;
            $data->thumbnail = $thumbnailUrl ?? '';
            $data->description = $descriptionPoll;
            $data->today_status = $todayStatusPoll;
            $data->draft = $draftPoll;
            $data->soft_delete = $softDeletePoll;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = Poll::where('id_poll', $idPoll)->first();
            if ($data) {
                $data->id_categories = $idCategories;
                $data->title = $titlePoll;
                $data->subtitle = $subtitlePoll;
                $data->thumbnail = $thumbnailUrl;
                $data->description = $descriptionPoll;
                $data->today_status = $todayStatusPoll;
                $data->draft = $draftPoll;
                $data->soft_delete = $softDeletePoll;
                $data->save();
            }else{
                return ResultHelpers::errors('Poll Data Not Found from id_poll inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }
    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/poll/show",
     *      operationId="showPoll",
     *      tags={"Poll"},
     *      summary="Show Poll",
     *      description="Returns Poll data",
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
        $data = Poll::where('soft_delete', 0)
            ->orderBy('id_poll', 'DESC')
            ->with('Categories')
            ->get();

        return ResultHelpers::success($data, 200, true);
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
        // Return Created / Updated Poll Data with its Relation.
        $data = Poll::where('id_categories', $idCategories)->with('Categories')->first();

        return ResultHelpers::success($data, 200, true);
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
        $data = Poll::where('id_poll', $idPoll)->with('Categories','PollListChoice')->first();

        return ResultHelpers::success($data, 200, true);
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
        ]);

        $draftPoll = $requestData['draft'] ?? null;

        if (!$draftPoll) {
            return ResultHelpers::errors('draft status must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        // Return Created / Updated Poll Data with its Relation.
        $data = Poll::where('draft', $draftPoll)->with('Categories')->first();

        return ResultHelpers::success($data, 200, true);
    }
}
