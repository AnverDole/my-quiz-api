<?php

namespace App\Traits;

use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqAnswer;
use App\Models\Exam\Question;
use Illuminate\Contracts\Support\MessageBag;

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
    private function insertMcqQuestions(Exam $exam, Object $data)
    {
        $insertedmcqids = [];
        foreach ($data->questions["mcq"] as $question) {
            $createdquestion = $exam->Questions()->create([
                "question_type" => Question::MCQ_QUESTION,
            ]);
            $insertedmcqids[] = $createdquestion->McqQuestion()->create([
                "question" => $question["question"],
            ])->id;
            
        }
        return $insertedmcqids;
    }

    /**
     * Insert and asosiate the mcq answers with previously inserted mcq questions. 
     * @param array $mcqquestionsids each previously inserted mcqquestion ids.
     * @param object $data new mcq answers data.
     * @return void
     */
    private function insertMcqAnswersIntoMcqQuestions($mcqquestionsids, $data)
    {
        $mcqs = $data->questions["mcq"];
        foreach ($mcqquestionsids as $key => $mcqquestionid) {
            $mcqquestion = $mcqs[$key];
            $answers = array_map(function ($answer) use ($mcqquestionid) {
                return [
                    'mcq_question_id' => $mcqquestionid,
                    'answer' => $answer["answer"],
                    'is_correct' => $answer["is_correct"],
                ];
            }, $mcqquestion['answers']);
            McqAnswer::insert($answers);
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

        $this->prepireNoQuestionsError($messagebag, $errors);
        
        #region prepire mcq errors.
        $this->prepireMcqQuestionErrors($messagebag, $errors);
        $this->prepireNoMcqAnswersError($messagebag, $errors);
        $this->prepireMcqAnswerBodyError($messagebag, $errors);
        $this->prepireCorrectMcqAnswerErrors($messagebag, $errors);
        #endregion

        return response()->json(["errors" => (array)$errors], 422);
    }

    /**
     * Set exam info(subject, duration, title) errors into the given error object if present. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function ExamInfoErrors($messagebag, &$errors)
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
     * Set no question error into the given error object if present.
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function prepireNoQuestionsError($messagebag, &$errors)
    {
        if ($messagebag->has('questions.mcq')) {
            $errors->questionerrors = (object)[
                'message' => $messagebag->get('questions.mcq'),
            ];
        }
    }

    /**
     * Set mcq question(question body text) errors into the given error object if present. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function prepireMcqQuestionErrors($messagebag, &$errors)
    {

        //prepire the each mcq question's question error message.
        if ($messagebag->has('questions.mcq.*.question')) {
            foreach ($messagebag->get('questions.mcq.*.question') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                //prepire the output errors object. 
                $this->defineQuestionErrorsObjectIfNotDefined($errors);
                $this->defineQuestionsErrorObjectIfNotDefined($errors);
                $this->defineMcqErrorObjectIfNotDefined($errors);
                $this->defineMcqQuestionErrorObjectIfNotDefined($errors, $questionid);

                $errors->questionerrors->questions->mcq[$questionid]->id = $questionid;
                $errors->questionerrors->questions->mcq[$questionid]->message = $message;
            }
        }
        //prepire the each mcq question's correct answer error message.
        if ($messagebag->has('questions.mcq.*.correctanswer')) {
            foreach ($messagebag->get('questions.mcq.*.correctanswer') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                //prepire the output errors object. 
                $this->defineQuestionErrorsObjectIfNotDefined($errors);
                $this->defineQuestionsErrorObjectIfNotDefined($errors);
                $this->defineMcqErrorObjectIfNotDefined($errors);

                $errors->questionerrors->questions->mcq[$questionid] = (object)[
                    'id' => $questionid,
                    'correctanswer' => $message,
                ];
            }
        }
    }

    /**
     * Set no mcq answers error into the given error object if present.
     * This will set the error if the mcq question has no answers. 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function prepireNoMcqAnswersError($messagebag, &$errors)
    {
        foreach ($messagebag->get('questions.mcq.*.answers') as $key => $message) {
            $questionid = $this->extractId($key, 0);

            //prepire the output errors object. 
            $this->defineQuestionErrorsObjectIfNotDefined($errors);
            $this->defineQuestionsErrorObjectIfNotDefined($errors);
            $this->defineMcqErrorObjectIfNotDefined($errors);
            $this->defineMcqQuestionErrorObjectIfNotDefined($errors, $questionid);

            $errors->questionerrors->questions->mcq[$questionid]->answererrors = (object)["message" => $message];
        }
    }
    /**
     * Set mcq answer's answer body errors into the given error object.
     * This will set the each answer's answer body errors(missing field, not a string.. errors). 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function prepireMcqAnswerBodyError($messagebag, &$errors)
    {
        if ($messagebag->has('questions.mcq.*.answers.*.answer')) {

            foreach ($messagebag->get('questions.mcq.*.answers.*.answer') as $key => $message) {
                $questionid = $this->extractId($key, 0);
                $answerkey = $this->extractId($key, 1);
                $errors = (object)[];

                //prepire the output errors object. 
                $this->defineQuestionErrorsObjectIfNotDefined($errors);
                $this->defineQuestionsErrorObjectIfNotDefined($errors);
                $this->defineMcqErrorObjectIfNotDefined($errors);
                $this->defineMcqQuestionErrorObjectIfNotDefined($errors, $questionid);
                $this->defineMcqQuestionAnswerErrorObjectIfNotDefined($errors, $questionid);

                $errors->questionerrors->questions->mcq[$questionid]->answererrors->answers = [];
                $errors->questionerrors->questions->mcq[$questionid]->answererrors->answers[] = (object)[
                    'id' => $answerkey,
                    'message' => $message,
                ];
            }
        }
    }

    /**
     * Set each mcq question's correct answer errors. 
     * This will set the each answers errors correct answer(is_correct field errors). 
     * @param object @param \Illuminate\Contracts\Support\MessageBag $messagebag
     * @param object $errors
     */
    private function prepireCorrectMcqAnswerErrors($messagebag, &$errors)
    {
        if ($messagebag->has('questions.mcq.*.correctanswerid')) {
            foreach ($messagebag->get('questions.mcq.*.correctanswerid') as $key => $message) {
                $questionid = $this->extractId($key, 0);

                //prepire the output errors object. 
                $this->defineQuestionErrorsObjectIfNotDefined($errors);
                $this->defineQuestionsErrorObjectIfNotDefined($errors);
                $this->defineMcqErrorObjectIfNotDefined($errors);
                $this->defineMcqQuestionErrorObjectIfNotDefined($errors, $questionid);

                $errors->questionerrors->questions->mcq[$questionid]->correctanswerid = $message;
            }
        }
    }
    #endregion





    #region error object initializer
    /**
     * define the $errors->questionerrors object if it dose not difined by previous step. 
     * @param object $errors 
     */
    private function defineQuestionErrorsObjectIfNotDefined(&$errors)
    {
        if (!isset($errors->questionerrors)) {
            $errors->questionerrors = (object)[];
        }
    }
    /**
     * define the $errors->questionerrors->questions object if it dose not difined by previous step. 
     * @param object $errors 
     */
    private function defineQuestionsErrorObjectIfNotDefined(&$errors)
    {
        if (!isset($errors->questionerrors->questions)) {
            $errors->questionerrors->questions = (object)[];
        }
    }
    /**
     * define the $errors->questionerrors->questions["mcq"] array if it dose not difined by previous step. 
     * @param object $errors 
     */
    private function defineMcqErrorObjectIfNotDefined(&$errors)
    {
        if (!isset($errors->questionerrors->questions->mcq)) {
            $errors->questionerrors->questions->mcq = [];
        }
    }
    /**
     * define the $errors->questionerrors->questions["mcq"][$questionid] object if it dose not difined by previous step. 
     * @param object $errors 
     */
    private function defineMcqQuestionErrorObjectIfNotDefined(&$errors, $questionid)
    {
        if (!isset($errors->questionerrors->questions->mcq[$questionid])) {
            $errors->questionerrors->questions->mcq[$questionid] = (object)["id" => $questionid];
        }
    }
    /**
     * define the $errors->questionerrors->questions["mcq"][$questionid]->answererrors object if it dose not difined by previous step. 
     * @param object $errors 
     * @param integer $questionid
     */
    private function defineMcqQuestionAnswerErrorObjectIfNotDefined(&$errors, $questionid)
    {
        if (!isset($errors->questionerrors->questions->mcq[$questionid]->answererrors)) {
            $errors->questionerrors->questions->mcq[$questionid]->answererrors = (object)[];
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

}
