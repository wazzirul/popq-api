<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id_categories';
    protected $fillable = ['id_categories','name_category','slug','icon','soft_delete'];

    public function Quiz(){
        return $this->hasMany(Quiz::class,'id_categories','id_categories');
    }

    public function Poll(){
        return $this->hasMany(Poll::class,'id_categories','id_categories');
    }
}
