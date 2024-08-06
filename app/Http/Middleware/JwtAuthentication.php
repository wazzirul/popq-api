<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\AuthenticationToken;


class JwtAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization') ?? null;
        $now = Carbon::now();

        if($token === null){
            $res['status_code'] = 401;
            $res['success'] = false;
            $res['error'] = "tokenNotFound";
            $res['detail'] = "Authorization Token not found";

            return response($res, 401);
        }

        $retrieve_token = AuthenticationToken::where('token', $token)
            ->first();

        if($retrieve_token === null){
            $res['status_code'] = 401;
            $res['success'] = false;
            $res['error'] = "tokenInvalid";
            $res['detail'] = "Token is Invalid";

            return response($res, 401);
        }

        if($retrieve_token->expired < $now){
            // Token is expired, so refresh it
            $tomorrow = $now->copy()->addDay(); // Add one day to the current date
            $retrieve_token->expired = $tomorrow;
            $retrieve_token->save();
            
            $res['status_code'] = 200;
            $res['success'] = true;
            $res['message'] = "Token has been refreshed";

            // $res['status_code'] = 401;
            // $res['success'] = false;
            // $res['error'] = "tokenExpired";
            // $res['detail'] = "Token is Expired";
            // return response($res, 401);
        }



        return $next($request);
    }
}
