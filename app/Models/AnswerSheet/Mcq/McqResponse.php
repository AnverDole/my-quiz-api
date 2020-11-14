<?php

namespace App\Models\AnswerSheet\Mcq;

use App\Models\AnswerSheet\AnswerSheet;
use App\Models\Exam\Mcq\McqAnswer;
use App\Models\Exam\Mcq\McqQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class McqResponse extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'answer_sheet_id',
        'mcq_question_id',
        'mcq_answer_id'
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

    public function AnswerSheet(){
        return $this->belongsTo(AnswerSheet::class);
    }
    public function McqQuestion(){
        return $this->belongsTo(McqQuestion::class);
    }
    public function McqAnswer(){
        return $this->belongsTo(McqAnswer::class);
    }
}
