<?php

namespace App\Models\Exam;

use App\Models\Exam\Mcq\McqAnswer;
use App\Models\Exam\Mcq\McqQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    public const MCQ_QUESTION = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_type',
        'exam_id',
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

    public function Exam(){
        return $this->belongsTo(Exam::class);
    }
    
    public function McqQuestion(){
        return $this->hasOne(McqQuestion::class);
    }
    

    public function isMcqQuestion(){
        return $this->question_type == self::MCQ_QUESTION;
    }
}
