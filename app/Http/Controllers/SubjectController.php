<?php

namespace App\Http\Controllers;

use App\Models\Exam\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request){
        $formatedsubjects = [];
        $subjects = Subject::all();
        foreach($subjects as $subject){
            $formatedsubjects[] = [
                "id" => $subject->id,
                "name" => $subject->name
            ];
        }
        return response()->json(['subjects' => $formatedsubjects]);
    }
}
