<?php

namespace App\Models\Exam;

use App\Models\AnswerSheet\AnswerSheet;
use App\Models\Exam\Mcq\McqQuestion;
use App\Models\Role\Instructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject_id',
        'instructor_id',
        'title',
        'duration',
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
    protected $casts = [
        'duration' => 'datetime:H:i',
    ];

    public function Instructor(){
        return $this->belongsTo(Instructor::class);
    }
    public function Questions(){
        return $this->hasMany(Question::class);
    }
    public function McqQuestions(){
        return $this->hasManyThrough(McqQuestion::class, Question::class);
    }
    public function filterMcqQuestions(){
        return $this->hasMany(Question::class)->where('question_type', "=", Question::MCQ_QUESTION)->get();
    }
    public function McqCount(){
        return $this->Questions()->where('question_type', "=", Question::MCQ_QUESTION)->count();
    }
    
    public function Subject(){
        return $this->belongsTo(Subject::class);
    }

    public function AnswerSheets(){
        return $this->hasMany(AnswerSheet::class);
    }
}
