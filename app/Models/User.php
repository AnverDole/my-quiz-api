<?php

namespace App\Models;

use App\Models\Role\Instructor;
use App\Models\Role\Student;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ACCOUNT_INSTRUCTOR = "INSTRUCTOR";
    public const ACCOUNT_STUDENT = "STUDENT";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'account_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isInstructor(){
        return $this->account_type == self::ACCOUNT_INSTRUCTOR;
    }
    public function isStudent(){
        return $this->account_type == self::ACCOUNT_STUDENT;
    }

    public function Instructor(){
        return $this->hasOne(Instructor::class);
    }
    public function Student(){
        return $this->hasOne(Student::class);
    }
}
