<?php

namespace App\Models\Exam;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class ExamValidator{
    function ShuldHaveOneCorrectAnswer($attribute, $value, $parameters, $validator){
        // var_dump($value);
        return false;
    }
}