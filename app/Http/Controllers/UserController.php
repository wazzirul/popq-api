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
     *      path="/user/register-login",
     *      operationId="registerloginUser",
     *      tags={"Users"},
     *      summary="Register / Login User",
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
    public function registerLogin(Request $request)
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

        if (!$data) {
            // Create a new user account
            $data = new Users();
            
            $fbId && $data->fb_ids = $fbId;
            $goId && $data->go_ids = $goId;
            $data->soft_delete = 0; // Set other necessary fields
            $data->save();
        }else{
            if($data->status === 1){
                return ResultHelpers::errors('This account has been banned.', 401, false);
            }
        }

        // Add the code for generating and saving the authentication token
        $strToken = Str::random(100);
        $expired = Carbon::now()->endOfDay();

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
     *         <br> - <b>username</b> need for update username of user
     *         <br> - <b>status</b> need for update status of user (default 0 = active, 1 = banned)   ",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="fb_ids", type="string", example="1234567887654321"),
     *              @OA\Property(property="go_ids", type="string", example=null),
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
            'fb_ids', // Facebook ID
            'go_ids', // Google ID
            'username', // Username
            'status', // status
        ]);

        $fbId = $requestData['fb_ids'] ?? null;
        $goId = $requestData['go_ids'] ?? null;
        $username = $requestData['username'] ?? null;
        $status = $requestData['status'] ?? null;

        if (!$fbId && !$goId) {
            return ResultHelpers::errors('Either fb_ids or go_ids must be provided.', 400, false);
        }

        // Check if the user already exists in the table
        if(!$fbId){
            $data = Users::where('go_ids', $goId)->first();
        }
        
        if(!$goId){
            $data = Users::where('fb_ids', $fbId)->first();
        }

        if ($data) {    
            $data->username = $username;
            $data->status = $status;
            $data->save();        
        }else{
            return ResultHelpers::errors('User Data Not Found.', 401, false);
        }

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/user/show",
     *      operationId="showUsers",
     *      tags={"Users"},
     *      summary="Show Users",
     *      description="Returns Users data",
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
        $data = Users::where('soft_delete', 0)
            ->orderBy('id_user', 'DESC')
            ->select([
                'id_user',
                'username',
                'fb_ids',
                'go_ids',
                'status',
            ])
            ->get();

        return ResultHelpers::success($data, 200, true);
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
    public function showById(Request $request)
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
        if(!$fbId){
            $data = Users::where('go_ids', $goId)->first();
        }
        
        if(!$goId){
            $data = Users::where('fb_ids', $fbId)->first();
        }

        return ResultHelpers::success($data, 200, true);
    }
}
