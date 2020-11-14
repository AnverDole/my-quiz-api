<?php

namespace App\Models\Role;

use App\Models\AnswerSheet\AnswerSheet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
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
        'email_verified_at' => 'datetime',
    ];

    public function User(){
        return $this->belongsTo(User::class);
    }

    public function Exams(){
        return $this->hasMany(Exam::class);
    }

    public function AnswerSheets(){
        return $this->hasMany(AnswerSheet::class);
    }
}
