<?php

namespace App\Models\AnswerSheet;

use App\Models\AnswerSheet\Mcq\McqResponse;
use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqAnswer;
use App\Models\Role\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isNull;

class AnswerSheet extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'exam_id',
        'submited_at'
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
        'submited_at' => 'datetime',
    ];

    public function Student(){
        return $this->belongsTo(Student::class);
    }
    public function Exam(){
        return $this->belongsTo(Exam::class);
    }
    public function McqResponses(){
        return $this->hasMany(McqResponse::class);
    }
    
    public function isSubmited(){
        return !is_null($this->submited_at);
    }
}
