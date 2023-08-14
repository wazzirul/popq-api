<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Administrators;
use App\Models\AuthenticationToken;

class AdministratorsController extends Controller
{
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
                    <br> - soft_delete (0 = active, 1 = deleted)",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_administrator", type="int", example="1"),
     *              @OA\Property(property="email", type="email", example="solikin@test.co"),
     *              @OA\Property(property="username", type="string", example="solikin"),
     *              @OA\Property(property="password", type="string", example="solikin"),
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
            'soft_delete',
        ]);

        $id_administrator = $requestData['id_administrator'] ?? null;
        $email = $requestData['email'] ?? null;
        $username = $requestData['username'] ?? null;
        $password = $requestData['password'] ?? null;
        $soft_delete = $requestData['soft_delete'] ?? null;

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

        $email && $data->email = $email;
        $username && $data->username = $username;
        $password && $password = Hash::make($password);
        $password && $data->password = $password;
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
            ->select([
                'username',
                'email',
                'password',
            ])
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
     * @OA\Get(
     *      path="/administrators/show",
     *      operationId="showAdministrators",
     *      tags={"Administrators"},
     *      summary="Show administrator",
     *      description="Returns administrator data",
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
        $data = Administrators::where('soft_delete', 0)
            ->orderBy('id_administrator', 'DESC')
            ->select([
                'id_administrator',
                'email',
                'username',
                'created_at',
            ])
            ->get();

        return ResultHelpers::success($data, 200, true);
    }
}
