<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class QuizListChoice extends Model
{
    protected $table = 'quiz_list_choice';
    protected $primaryKey = 'id_quiz_list_choice';
    protected $fillable = ['id_quiz_list_choice','id_quiz','id_quiz_questions','answer','correct','soft_delete'];
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('soft_delete', function (Builder $builder) {
            $builder->where('soft_delete', 0);
        });
    }
    public function Quiz()
    {
        return $this->hasOne('App\Models\Quiz','id_quiz','id_quiz');
    }

    public function QuizQuestions()
    {
        return $this->hasOne('App\Models\QuizQuestions','id_quiz_questions','id_quiz_questions');
    }

    public function UserQuizAnswer()
    {
        return $this->hasOne('App\Models\UserQuizAnswer','id_quiz_list_choice','id_quiz_list_choice');
    }
}
