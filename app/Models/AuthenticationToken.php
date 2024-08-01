<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthenticationToken extends Model
{
    protected $table = 'authentication_token';
    protected $primaryKey = 'id_authentication_token';
    protected $fillable = ['id_authentication_token','user_auth','token','expired','soft_delete'];
}
