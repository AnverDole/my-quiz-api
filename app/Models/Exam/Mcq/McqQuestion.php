<?php

namespace App\Models\Exam\Mcq;

use App\Models\Exam\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class McqQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id',
        'question'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function Question(){
        return $this->belongsTo(Question::class);
    }
    public function McqAnswers(){
        return $this->hasMany(McqAnswer::class);
    }
    public function getCorrectMcqAnswer(){
        return $this->hasMany(McqAnswer::class)->where('is_correct', '=', true)->first();
    }
}
