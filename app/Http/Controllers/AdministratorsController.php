<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use App\Models\Administrators;
use App\Models\AuthenticationToken;

class AdministratorsController extends Controller
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
   *      path="/administrators/store",
   *      operationId="storeAdministrators",
   *      tags={"Administrators"},
   *      summary="Store new or update administrator",
   *      description="Returns administrator data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b><br> - Remove the id_administrator object for create new data Administrator <b>or</b>
                  <br> - Fill in the id_administrator object for update data Administrator
                  <br> - Empty object if you don't want to change data
                  <br> - profile_img : must be base64 string
                  <br> - soft_delete (0 = active, 1 = deleted)
                  <br>________________________________________________
                  <br><br><b>Example of Thumbnail Base64:</b>
                  data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
    *          required=true,
    *          @OA\JsonContent(
    *              type="object",
    *              @OA\Property(property="id_administrator", type="int", example="1"),
    *              @OA\Property(property="email", type="email", example="solikin@test.co"),
    *              @OA\Property(property="username", type="string", example="solikin"),
    *              @OA\Property(property="password", type="string", example="solikin"),
    *              @OA\Property(property="profile_img", type="string", example=null),
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
  public function store(Request $request)
  {
    $requestData = $request->only([
      'id_administrator',
      'email',
      'username',
      'password',
      'profile_img',
      'soft_delete',
    ]);

    $id_administrator = $requestData['id_administrator'] ?? null;
    $email = $requestData['email'] ?? null;
    $username = $requestData['username'] ?? null;
    $password = $requestData['password'] ?? null;
    $soft_delete = $requestData['soft_delete'] ?? null;
    $profileIMG = $requestData['profile_img'] ?? null;

    if($id_administrator != null){
      $data = Administrators::find($id_administrator);
      if (!$data) return ResultHelpers::errors('Data administrator not found.', 400, false);

    }else{
      if (!$email) return ResultHelpers::errors('Email must filled for new data.', 400, false);
      if (!$username) return ResultHelpers::errors('Username must filled for new data.', 400, false);
      if (!$password) return ResultHelpers::errors('Password must filled for new data', 400, false);

      $usedData = Administrators::where('email', $email)
        ->orWhere('username', $username)
        ->first();

      if ($usedData) return ResultHelpers::errors('Email or Username is already in use.', 400, false);
      
      $data = new Administrators();
    }
    
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

    $email && $data->email = $email;
    $username && $data->username = $username;
    $password && $password = Hash::make($password);
    $password && $data->password = $password;
    $profileIMGUrl && $data->profile_img = $profileIMGUrl;
    $soft_delete != null && $data->soft_delete = $soft_delete;
    $data->save();

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/administrators/login",
   *      operationId="loginAdministrators",
   *      tags={"Administrators"},
   *      summary="Login administrator",
   *      description="Returns administrator data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b><br> - <b>Email</b> can be filled by email or username",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="email", type="email", example="solikin@test.co"),
   *              @OA\Property(property="password", type="string", example="solikin"),
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
          'email',
          'password',
      ]);

      $email = $requestData['email'] ?? null;
      $password = $requestData['password'] ?? null;

      $data = Administrators::where('email', $email)
          ->orWhere('username', $email)
          ->first();

      if (!$data) return ResultHelpers::errors('Data administrator not found.', 400, false);
      if ($data->soft_delete == 1) return ResultHelpers::errors('Data administrator was deleted.', 400, false);

      $decrypt = Hash::check($password, $data->password);
      if (!$decrypt) return ResultHelpers::errors('Wrong password.', 400, false);

      $str_token = Str::random(100);
      $expired = Carbon::now()->endOfDay();

      $auth_token = new AuthenticationToken();
      $auth_token->user_auth = "administrator-".$data->id_administrator;
      $auth_token->token = $str_token;
      $auth_token->expired = $expired;
      $auth_token->save();

      $data['token'] = $str_token;
      unset($data['password']);

      AuthenticationToken::where('user_auth', 'api')
          ->where('expired', '<=', $expired->copy()->subDays(3))
          ->forceDelete();

      return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/administrators/show",
   *      operationId="showAdministrators",
   *      tags={"Administrators"},
   *      summary="Show administrator",
   *      description="Returns administrator data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
          <br> - search as search for administrators username, email
          <br> - limit as limit show data (default 10)
          <br> - page as page show data (default 1)
          <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example=null),
   *              @OA\Property(property="id_administrator", type="int", example=null),
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
      'id_administrator',
      'limit',
      'page',
      'sort',
    ]);

    $search = $requestData['search'] ?? null;
    $idAdministrator = $requestData['id_administrator'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
    $sort = $requestData['sort'] ?? 1; // Default sort is 1 (1 = most recent, 2 = least recent, 3 = alphabetical)

    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;

    // Initialize the query builder for the main data retrieval
    $query = Administrators::where('soft_delete', 0); 

    if($sort === 1){
      $query->orderBy('id_administrator', 'DESC');
    }else if($sort === 2 ){
      $query->orderBy('id_administrator', 'ASC');
    }else if($sort === 3 ){
      $query->orderBy('username', 'ASC');
    }else{ 
      $query->orderBy('id_administrator', 'DESC');
    }

    $query->skip($offset)
    ->take($limit);

    // Initialize the query builder for the total records count
    $totalRecordsQuery = Administrators::where('soft_delete', 0);
    
    if ($search) {
      $query->where(function ($query) use ($search) {
          $query->where('email', 'LIKE', '%' . $search . '%')
                ->orWhere('username', 'LIKE', '%' . $search . '%');
      });
  
      $totalRecordsQuery->where(function ($query) use ($search) {
        $query->where('email', 'LIKE', '%' . $search . '%')
              ->orWhere('username', 'LIKE', '%' . $search . '%');
      });
    }  

    if ($idAdministrator) {
        $query->where('id_administrator', $idAdministrator);
        $totalRecordsQuery->where('id_administrator', $idAdministrator);
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
}
