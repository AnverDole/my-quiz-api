<?php

namespace App\Traits;

use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqQuestion;
use App\Models\Exam\Question;
use DateTime;
use Exception;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait CreateEditExamHelpers
{

    #region helper functions for create new exam. 
    /**
     * Validate all the mcq questions correct answer field.
     * this will enshuers the correctanswerid is exist in the answers array keys.
     * @param Illuminate\Support\Facades\Validator $validator
     * @param Illuminate\Http\Request $request 
     * @return void
     */
    function ValidateCorrectMcqAnswers($validator, $request)
    {
        if (is_null($request->input('questions.mcq.*')) || count($request->input('questions.mcq.*')) < 1) return;

        //loop thrug each mcq
        foreach ($request->input('questions.mcq.*') ?? [] as $key => $mcq) {
            $answers_iscorrect_arr = $request->input("questions.mcq.$key.answers.*.is_correct") ?? [];
            if (count(array_keys($answers_iscorrect_arr, 1)) != 1) {
                $validator->errors()->add("questions.mcq.$key.correctanswerid",  'The question shuld have one correct answer.');
            }
        }
    }



    /**
     * Insert mcq questions to the given exam. 
     * @param Object $data 
     * @param App\Models\Exam\Exam $exam
     * @return Illuminate\Database\Eloquent\Collection
     */
    private function McqQuestions(Exam $exam, Object $data)
    {
        $mcqs = [];
        foreach ($data->questions["mcq"] as $question) {
            $createdquestion = $exam->Questions()->create([
                "question_type" => Question::MCQ_QUESTION,
            ]);
            $mcqs[] = $createdquestion->McqQuestion()->create([
                "question" => $question["question"],
            ]);
        }
        return $mcqs;
    }

    /**
     * Insert mcq answers to the given questions. 
     * @param \App\models\Exam\Question $mcqquestions mcqs that wants to update there answers.
     * @param object $data new mcq answers data.
     * @return void
     */
    private function McqsAnswers($mcqquestions, $data)
    {
        $mcqs = $data->questions["mcq"];
        foreach ($mcqquestions as $key => $question) {
            $mcqquestion = $mcqs[$key];
            $answers = array_map(function ($answer) use ($question) {
                return [
                    'mcq_question_id' => $question->id,
                    'answer' => $answer["answer"],
                    'is_correct' => $answer["is_correct"],
                ];
            }, $mcqquestion['answers']);
            $question->McqAnswers()->createMany($answers);
        }
    }
    #endregion
    #region helper functions for validation errors

    /**
     * Get Validation error response. 
     * @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @return \Illuminate\Http\Response
     */
    private function ValidationErrorResponse(MessageBag $messagebag)
    {
        $errors = (object)[];
        $this->ExamInfoErrors($messagebag, $errors);

        $this->QuestionError($messagebag, $errors);
        $this->McqQuestionErrors($messagebag, $errors);

        $this->McqAnswerError($messagebag, $errors);
        $this->McqAnswerErrors($messagebag, $errors);
        $this->McqCorrectAnswersErrors($messagebag, $errors);


        // $this->checkMcqErrors($messagebag, $errors);
        return response()->json(["errors" => (array)$errors], 422);
    }

    /**
     * Set exam info(subject, duration, title) errors into the given error object. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function ExamInfoErrors($messagebag, $errors)
    {
        // exam id errors
        if ($messagebag->has("id")) {
            $errors->id = $messagebag->get('id');
        }
        // subject errors
        if ($messagebag->has("subject")) {
            $errors->subjecterror = $messagebag->get('subject');
        }
        //duration errors
        if ($messagebag->has("duration")) {
            $errors->durationerror = $messagebag->get('duration');
        }
        //title errors
        if ($messagebag->has("title")) {
            $errors->titleerror = $messagebag->get('title');
        }
    }
    /**
     * Set mcq question errors into the given error object. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function McqQuestionErrors($messagebag, $errors)
    {

        if ($messagebag->has('questions.mcq.*.question')) {
            foreach ($messagebag->get('questions.mcq.*.question') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                if (!isset($errors->questionerrors)) $errors->questionerrors = (object)[];
                if (!isset($errors->questionerrors->questions)) $errors->questionerrors->questions = [];
                if (!isset($errors->questionerrors->questions["mcq"])) $errors->questionerrors->questions["mcq"] = [];

                $errors->questionerrors->questions["mcq"][$questionid] = (object)[
                    'id' => $questionid,
                    'message' => $message,
                ];
            }
        }
        if ($messagebag->has('questions.mcq.*.correctanswer')) {
            foreach ($messagebag->get('questions.mcq.*.correctanswer') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                if (!isset($errors->questionerrors)) $errors->questionerrors = (object)[];
                if (!isset($errors->questionerrors->questions)) $errors->questionerrors->questions = [];
                if (!isset($errors->questionerrors->questions["mcq"])) $errors->questionerrors->questions["mcq"] = [];

                $errors->questionerrors->questions["mcq"][$questionid] = (object)[
                    'id' => $questionid,
                    'correctanswer' => $message,
                ];
            }
        }
    }
    /**
     * Set mcq answer error into the given error object.
     * This will set the error if the mcq question has no answers. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function McqAnswerError($messagebag, $errors)
    {

        foreach ($messagebag->get('questions.mcq.*.answers') as $key => $message) {
            $questionid = $this->extractId($key, 0);

            if (!isset($errors->questionerrors)) $errors->questionerrors = (object)[];
            if (!isset($errors->questionerrors->questions)) $errors->questionerrors->questions = [];
            if (!isset($errors->questionerrors->questions['mcq'])) $errors->questionerrors->questions['mcq'] = (object)[];
            if (!isset($errors->questionerrors->questions["mcq"][$questionid])) $errors->questionerrors->questions["mcq"][$questionid] = (object)[];

            $errors->questionerrors->questions["mcq"][$questionid]->answererrors = (object)["message" => $message];
        }
    }
    /**
     * Set mcq answer errors into the given error object.
     * This will set the each answers errors(missing field errors). 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function McqAnswerErrors($messagebag, $errors)
    {
        if ($messagebag->has('questions.mcq.*.answers.*.answer')) {


            // dd($key);
            foreach ($messagebag->get('questions.mcq.*.answers.*.answer') as $key => $message) {
                $questionid = $this->extractId($key, 0);
                $answerkey = $this->extractId($key, 1);

                if (!isset($errors->questionerrors)) $errors->questionerrors = (object)[];
                if (!isset($errors->questionerrors->questions)) $errors->questionerrors->questions = [[$questionid] => (object)["id" => $questionid]];
                if (!isset($errors->questionerrors->questions["mcq"])) $errors->questionerrors->questions["mcq"] = [];
                if (!isset($errors->questionerrors->questions["mcq"][$questionid]->answererrors)) $errors->questionerrors->questions["mcq"][$questionid]->answererrors = (object)[];

                $errors->questionerrors->questions["mcq"][$questionid]->answererrors->answers = [];
                $errors->questionerrors->questions["mcq"][$questionid]->answererrors->answers[] = (object)[
                    'id' => $answerkey,
                    'message' => $message,
                ];
            }
        }
    }
    /**
     * Set mcq questions correct answer errors. 
     * This will set the each answers errors(missing field errors). 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function McqCorrectAnswersErrors($messagebag, $errors)
    {
        if ($messagebag->has('questions.mcq.*.correctanswerid')) {
            foreach ($messagebag->get('questions.mcq.*.correctanswerid') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                if (!isset($errors->questionerrors)) $errors->questionerrors = (object)[];
                if (!isset($errors->questionerrors->questions)) $errors->questionerrors->questions = (object)[];
                if (!isset($errors->questionerrors->questions["mcq"])) $errors->questionerrors->questions["mcq"] = [];
                if (!isset($errors->questionerrors->questions["mcq"][$questionid])) $errors->questionerrors->questions["mcq"][$questionid] = (object)["id" => $questionid];

                $errors->questionerrors->questions["mcq"][$questionid]->correctanswerid = $message;
            }
        }
    }
    #endregion


    #region helper functions
    private function extractId($string, $index)
    {
        $match = [];
        preg_match_all("/\.(\d+)\.*/", $string, $match, PREG_PATTERN_ORDER);
        return $match[1][$index];
    }
    private function removePreviousData($exam)
    {
        foreach ($exam->Questions as $question) {
            $question->McqQuestion->McqAnswers()->delete();
            $question->McqQuestion->delete();
        }
        $exam->Questions()->delete();
    }
    #endregion
    /**
     * Set question error into the given error object. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function QuestionError($messagebag, $errors)
    {
        if ($messagebag->has('questions.mcq')) {
            $errors->questionerrors = (object)[
                'message' => $messagebag->get('questions.mcq'),
            ];
        }
    }
}
