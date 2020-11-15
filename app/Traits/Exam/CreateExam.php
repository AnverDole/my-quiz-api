<?php

namespace App\Traits;

use App\Models\Exam\Exam;
use App\Models\Exam\Question;
use DateTime;
use Exception;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait CreateExam
{
    use CreateEditExamHelpers;



    /**
     * Validate the exam data.
     * @param Illuminate\Http\Request $request 
     * @return void
     */
    function new(Request $request)
    {
        /**
         * validation rules
         */
        $rules = [
            'title' => "required|string",
            'subject' =>  'required|integer|exists:subjects,id',
            'duration' => "required|date_format:H:i",

            'questions' => "required|array|min:1",
            
            'questions.mcq' => "sometimes|array|min:1",
            'questions.mcq.*' => "required_with:questions.mcq",

            'questions.mcq.*.question' => "required_with:questions.mcq|string",
            'questions.mcq.*.answers' => "required_with:questions.mcq|array|min:1",
            'questions.mcq.*.answers.*.answer' => "required_with:questions.mcq|string",
            'questions.mcq.*.answers.*.is_correct' => "required_with:questions.mcq|in:0,1",
        ];
        /**
         * custom validation massages
         */
        $massages = [
            'duration.date_format' => "The duration does not match the format HH:MM.",

            'questions.mcq.required' => "The questions field is required.",
            'questions.mcq.*.question.required' => 'The question field is required.',

            'questions.mcq.*.answers.required_with' => 'This question shuld have at least 1 answer.',
            'questions.mcq.*.answers.array' => 'The answers field must be a array.',
            'questions.mcq.*.answers.min' => 'This question shuld have at least :min answer.',

            'questions.mcq.*.answers.*.answer.required_with' => 'The answer field is required.',
            'questions.mcq.*.answers.*.answer.string' => 'The answer field must be a string.',
        ];
        $validator = Validator::make(
            $request->all(),
            $rules,
            $massages
        );
        //    dd($request->input("questions.mcq.*"));

        $validator->after(function ($validator) use ($request) {
            $this->ValidateCorrectMcqAnswers($validator, $request);
        });

        if ($validator->fails()) {
            return $this->ValidationErrorResponse($validator->errors());
        }




        //get validated data object.
        $data = (object)$validator->getData();

        //insert the exam into the database.
        try {
            DB::beginTransaction();


            $exam = $this->createexam($data, $request->user());

            //insert mcq type questions. 
            $mcqquestions = $this->McqQuestions($exam, $data);
            $this->McqsAnswers($mcqquestions, $data);


            DB::commit();
            return response()->json(["exam" => $exam]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json($e, 500);
        }
    }

    /**
     * Create exam. 
     * @param Object $data 
     * @param \App\Models\User $user
     * @return \App\Models\Exam\Exam
     */
    function createexam(Object $data, $user)
    {
        $exam = $user->instructor->Exams()->create([

            "subject_id" => $data->subject,
            "duration" => DateTime::createFromFormat("H:i", $data->duration),
            "title" => $data->title,

        ]);

        return $exam;
    }
}
