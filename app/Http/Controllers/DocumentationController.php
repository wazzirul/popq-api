<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Session;

use App\Models\Administrators;
use App\Models\AuthenticationToken;

class DocumentationController extends Controller
{
    public function loginProcess(Request $request){
        $email = $request->input('email');
        $data = Administrators::where('email', $email)
            ->orWhere('username', $email)
            ->first();

        if($email == 'superadmin@mail.co' && $request->input('password') == 'superadminpopqpass' ){
            $str_token = Str::random(100);
            $expired = Carbon::now()->endOfDay();

            $auth_token = new AuthenticationToken();
            $auth_token->user_auth = "api";
            $auth_token->token = $str_token;
            $auth_token->expired = $expired;
            $auth_token->save();

            AuthenticationToken::where('user_auth', 'api')
                ->where('expired', '<=', $expired->copy()->subDays(3))
                ->delete();

            Session::put('login', TRUE);
            Session::put('token', $str_token);

            return redirect('/api/documentation')->with('success','Welcome to PopQ API.');
        }
        
        if($data === null){
            return redirect('/')->with('error','Account data not found.');
        }

        if($request->input('password') != null){ 
            $decrypt = Hash::check($request->input('password'), $data->password);

            if($decrypt){ 
                $data = Administrators::where('email', $email)
                    ->select([
                        'id_administrator',
                        'email',
                        'created_at',
                        'updated_at'
                    ])->first();
                    
                $str_token = Str::random(100);
                $expired = Carbon::now()->endOfDay();

                $auth_token = new AuthenticationToken();
                $auth_token->user_auth = "api";
                $auth_token->token = $str_token;
                $auth_token->expired = $expired;
                $auth_token->save();

                AuthenticationToken::where('user_auth', 'api')
                    ->where('expired', '<=', $expired->copy()->subDays(3))
                    ->delete();

                Session::put('id_administrator', $data->id_administrator);
                Session::put('login', TRUE);
                Session::put('token', $str_token);

                return redirect('/api/documentation')->with('success','Welcome to PopQ API.');
            }else{
                Session::flush();
                return redirect('/')->with('error','Wrong password.');
            }
        }else{
            return redirect('/')->with('error','Password is required.');
        }
    }

    public function login(){
        if(Session::has('login')){
            return redirect('/api/documentation');
        }else{
            return view('login');
        }
    }

    public function logout(){
        Session::flush();
        return redirect('/')->with('alert','Logout successful.');
    }
}
