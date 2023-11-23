<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use App\Models\Users;
use App\Models\AuthenticationToken;

class UserController extends Controller
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
      $path = 'profile_image/' . $filename; // Set the desired directory within the public storage folder
  
      // Save the file to the storage disk
      Storage::disk('public')->put($path, $decodedData);
  
      // Return the path to the uploaded file
      return $path;
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/user/login",
     *      operationId="loginUser",
     *      tags={"Users"},
     *      summary=" / Login User",
     *      description="Returns User data with auth token",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>fb_ids || go_ids</b> get from the firebase",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="fb_ids", type="string", example="1234567887654321"),
     *              @OA\Property(property="go_ids", type="string", example=null),
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
    public function login(Request $request)
    {
        $requestData = $request->only([
            'fb_ids', // Facebook ID
            'go_ids', // Google ID
        ]);

        $fbId = $requestData['fb_ids'] ?? null;
        $goId = $requestData['go_ids'] ?? null;

        if($fbId){
            // Check if the user already exists in the table
            $data = Users::where('fb_ids', $fbId)->first();
        }else if($goId){
            // Check if the user already exists in the table
            $data = Users::where('go_ids', $goId)->first();
        }else{
            return ResultHelpers::errors('Either fb_ids or go_ids must be provided.', 400, false);
        }
        
        if (!$data) {
            // Create a new user account
            $data = new Users();
            
            $fbId && $data->fb_ids = $fbId;
            $goId && $data->go_ids = $goId;
            $data->soft_delete = 0; // Set other necessary fields
            $data->save();
        }else{
            if($data->status !== 0){
                return ResultHelpers::errors('This account has been banned.', 401, false);
            }
        }

        // Add the code for generating and saving the authentication token
        $strToken = Str::random(100);
        $expired = Carbon::now()->addHours(25);

        $authToken = new AuthenticationToken();
        $authToken->user_auth = "user-" . $data->id_user;
        $authToken->token = $strToken;
        $authToken->expired = $expired;
        $authToken->save();

        $data['token'] = $strToken;

        // Clean up old authentication tokens
        AuthenticationToken::where('user_auth', 'api')
            ->where('expired', '<=', $expired->copy()->subDays(3))
            ->forceDelete();

        return ResultHelpers::success($data, 200, true);
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/user/edit-user",
     *      operationId="editUser",
     *      tags={"Users"},
     *      summary="Edit User Data",
     *      description="Returns updated username on User Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>fb_ids || go_ids</b> as a reference to the user data that will be updated
              <br> - <b>username</b> need for update username of user
              <br> - <b>status</b> need for update status of user (default null, 0 = active, 1 = banned)   
              <br> - <+b>profile_img</b> : must be base64 string
              <br> - <b>soft_delete</b> (0 = active, 1 = deleted)
              <br>________________________________________________
              <br><br><b>Example of Thumbnail Base64:</b>
              data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="string", example="1"),
     *              @OA\Property(property="username", type="string", example=null),
     *              @OA\Property(property="status", type="number", example=0),
    *              @OA\Property(property="profile_img", type="string", example=null),
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
    public function editUser(Request $request)
    {
        $requestData = $request->only([
            'id_user',
            'username',
            'status', 
            'profile_img',
        ]);

        $idUser = $requestData['id_user'] ?? null;
        $username = $requestData['username'] ?? null;
        $status = $requestData['status'] ?? null;
        $profileIMG = $requestData['profile_img'] ?? null;

        if (!$idUser) {
            return ResultHelpers::errors('id_user must be provided.', 400, false);
        }

        $data = Users::where('id_user', $idUser)->first();
        
        // Generate the URL of the uploaded file
        $profileIMGUrl = null;
        if ($profileIMG) {
          // Check if profile image input data is a valid base64 string
          if (!preg_match('/data:image\/(.*?);base64,/', $profileIMG)) {
              return ResultHelpers::errors('profile image must be a valid base64 string', 400, false);
          }
          
          // Upload the base64 profile image to the public folder and get the path
          $profileIMGPath = $this->uploadImageFromBase64($profileIMG);

          // Generate the URL of the uploaded file
          $profileIMGUrl = Storage::disk('public')->url($profileIMGPath);
        }

        if ($data) {    
            $username && $data->username = $username;
            $status != null && $data->status = $status;
            $profileIMGUrl && $data->profile_img = $profileIMGUrl;
            $data->save();        
        }else{
            return ResultHelpers::errors('User Data Not Found.', 401, false);
        }

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/user/show",
     *      operationId="showUsers",
     *      tags={"Users"},
     *      summary="Show Users",
     *      description="Returns Users data",
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

        $data = Users::where('soft_delete', 0)
            ->orderBy('id_user', 'DESC')
            ->select([
                'id_user',
                'username',
                'fb_ids',
                'go_ids',
                'status',
            ])
            ->skip($offset)
            ->take($limit)
            ->get();

        $totalRecords = Users::where('soft_delete', 0)->count();
        $totalPages = ceil($totalRecords / $limit);
    
        return ResultHelpers::success([
            'data' => $data,
            'total_pages' => $totalPages,
        ], 200, true);
    }

    
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/user/showById",
     *      operationId="showByIdUser",
     *      tags={"Users"},
     *      summary="Show BY Id User Data",
     *      description="Returns User Data based on fb_ids or go_ids",
     *      @OA\RequestBody(
     *          description="<b>Note:</b><br> - <b>fb_ids || go_ids</b> as a reference to the user data that will be updated",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="string", example="1"),
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
            'id_user',
        ]);

        $idUser = $requestData['id_user'] ?? null;

        if (!$idUser) {
            return ResultHelpers::errors('id_user must be provided.', 400, false);
        }
        
        $data = Users::where('id_user', $idUser)->first();

        return ResultHelpers::success($data, 200, true);
    }
}
