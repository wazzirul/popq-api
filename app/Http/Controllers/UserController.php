<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Users;
use App\Models\AuthenticationToken;

class UserController extends Controller
{
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

        if (!$fbId && !$goId) {
            return ResultHelpers::errors('Either fb_ids or go_ids must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        $data = Users::where('fb_ids', $fbId)->orWhere('go_ids', $goId)->first();
        
        if($data->status !== 0){
            return ResultHelpers::errors('This account has been banned.', 401, false);
        }

        if (!$data) {
            // Create a new user account
            $data = new Users();
            
            $fbId && $data->fb_ids = $fbId;
            $goId && $data->go_ids = $goId;
            $data->soft_delete = 0; // Set other necessary fields
            $data->save();
        }

        // Add the code for generating and saving the authentication token
        $strToken = Str::random(100);
        $expired = Carbon::now()->addHours(24);

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
              <br> - <b>status</b> need for update status of user (default 0 = active, 1 = banned)   ",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_user", type="string", example="1"),
     *              @OA\Property(property="username", type="string", example=null),
     *              @OA\Property(property="status", type="number", example=0),
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
        ]);

        $idUser = $requestData['id_user'] ?? null;
        $username = $requestData['username'] ?? null;
        $status = $requestData['status'] ?? 0;

        if (!$idUser) {
            return ResultHelpers::errors('id_user must be provided.', 400, false);
        }

        $data = Users::where('id_user', $idUser)->first();

        if ($data) {    
            $username && $data->username = $username;
            $data->status = $status;
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
