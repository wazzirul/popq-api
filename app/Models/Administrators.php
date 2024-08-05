<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrators extends Model
{
    protected $table = 'administrators';
    protected $primaryKey = 'id_administrator';
    protected $fillable = ['id_administrator','email','username','password','soft_delete'];
}
