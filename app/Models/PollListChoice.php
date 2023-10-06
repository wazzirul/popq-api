<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PollListChoice extends Model
{
    protected $table = 'poll_list_choice';
    protected $primaryKey = 'id_poll_list_choice';
    protected $fillable = ['id_poll_list_choice','id_poll','poll_choice','soft_delete'];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('soft_delete', function (Builder $builder) {
            $builder->where('soft_delete', 0);
        });
    }

    public function Poll()
    {
        return $this->hasOne('App\Models\Poll','id_poll','id_poll');
    }
}
