<?php

namespace App\Http\Controllers;

use App\Models\AnswerSheet\AnswerSheet;
use App\Models\AnswerSheet\Mcq\McqResponse;
use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqAnswer;
use App\Models\Exam\Mcq\McqQuestion;
use App\Models\Exam\Question;
use DateTime;
use Faker\Factory;
use App\Models\Role\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function take(Request $request)
    {
        $data = (object)$request->validate([
            "examid" => "required|integer|exists:exams,id"
        ]);
        $exam = Exam::find($data->examid);
        $student = $request->user()->Student;
        if (!$student) abort(500);

        $answersheet = $student->AnswerSheets()->where('exam_id', '=', $exam->id)->first();
        
        if ($answersheet) { //student is alredy taked the exam. 
            if ($answersheet->isSubmited()) abort(401);
            return $this->continueAnswerSheet($exam, $answersheet);
        } else { // student was not take the exam.
            return $this->newAnswerSheet($exam, $student);
        }
    }

    private function continueAnswerSheet(Exam $exam, AnswerSheet $answersheet)
    {
        $data = (object)$this->responseData($exam, $answersheet);
        $data->time_infomation = $this->getAvailableTimeInfomation($exam, $answersheet);
       
        
        if($data->time_infomation["available_time"]["minitues"] < 1){
            return response()->json(["error" => 'Time is over!'], 401);
        }
        $data->responses = [
            'mcqs' => $this->getMcqResponses($answersheet),
        ];
        return  response()->json((array)$data);
    }
    private function newAnswerSheet(Exam $exam, Student $student)
    {
        $answersheet = $student->AnswerSheets()->create([
            'exam_id' => $exam->id,
            'is_submited' => false,
            'submited_on' => null,
        ]);
        $data = (object)$this->responseData($exam, $answersheet);
        $data->answersheet_id =  $answersheet->id;
        $data->time_infomation = $this->getTotalTimeInfomation($exam);
        return  response()->json((array)$data);
    }
    private function getMcqQuestions(Exam $exam)
    {
        $questions = $exam->filterMcqQuestions();

        $data = [];
        foreach ($questions as $question) {
            $mcq = $question->McqQuestion;
            $data[] = [
                'question_id' => $question->id,
                'question' => $mcq->question,
                'answers' => $this->getMcqAnswers($mcq),
            ];
        }
        return $data;
    }
    private function getMcqAnswers(McqQuestion $mcqquestion)
    {
        $mcqanswers = $mcqquestion->McqAnswers;

        $data = [];
        foreach ($mcqanswers as $mcqanswer) {
            $data[] = [
                'answer_id' => $mcqanswer->id,
                'answer' => $mcqanswer->answer,
            ];
        }
        return $data;
    }
    private function getMcqResponses(AnswerSheet $answersheet)
    {
        $responses = $answersheet->McqResponses->toArray();

        $formatedresponses = [];
        foreach ($responses as $response) {
            $formatedresponses[$response["mcq_question_id"]] = [
                "question_id" => $response["mcq_question_id"],
                "answer_id" => $response["mcq_answer_id"]
            ];
        }
        return (object)$formatedresponses;
    }
    private function responseData(Exam $exam, AnswerSheet $answersheet)
    {
        return [
            "exam_id" => $exam->id,
            "answer_sheet_id" => $answersheet->id,
            "questions" => [
                "mcqs" => $this->getMcqQuestions($exam),
            ],
        ];
    }
    private function getTotalTimeInfomation(Exam $exam)
    {
        $examDuratationH = (int)$exam->duration->format("H");
        $examDuratationM = (int)$exam->duration->format("i");
        $starttime = $exam->created_at;
        $shuldbesubmit = $starttime->copy()->addHours($examDuratationH)->addMinutes($examDuratationM);

        return [
            "available_time" => [
                "minitues" => $examDuratationM + $examDuratationH * 60,
            ],
            "start_time" => $starttime->format("Y-m-d H:i"),
            "shuld_be_submit" => $shuldbesubmit->format("Y-m-d H:i"),
        ];
    }
    private function getAvailableTimeInfomation(Exam $exam, AnswerSheet $answersheet)
    {
        $examDuratationH = (int)$exam->duration->format("H");
        $examDuratationM = (int)$exam->duration->format("i");

        $startedtime = $answersheet->created_at->Settimezone("Asia/colombo");

        $shuldbesubmit = $startedtime->copy()->addHours($examDuratationH)->addMinutes($examDuratationM);
        if(Carbon::now() > $shuldbesubmit){
            $availabletimeM = 0;
        }else{
        }
        $availabletimeM = $shuldbesubmit->copy()->diffInMinutes(Carbon::now());

        return [
            "available_time" => [
                "minitues" => $availabletimeM,
            ],
            "start_time" => $startedtime->format("Y-m-d H:i"),
            "shuld_be_submit" => $shuldbesubmit->format("Y-m-d H:i"),
        ];
    }

    public function save(Request $request)
    {
        $data = (object)$request->validate([
            "exam_id" => "required|integer|exists:exams,id",

            "responses.mcqs" => "required|array",
            "responses.mcqs.*.question_id" => "required|integer|exists:questions,id",
            "responses.mcqs.*.answer_id" => "required|integer|exists:mcq_answers,id"
        ]);
        $student = $request->user()->Student;
        $answersheet = $student->AnswerSheets()->where("exam_id", "=", $data->exam_id)->first();
        
        if ($answersheet->isSubmited()) abort(401);
        $mcqresponses = array_map(function ($response) {
            return [
                'mcq_question_id' => $response['question_id'],
                'mcq_answer_id' => $response['answer_id']
            ];
        }, $data->responses['mcqs']);

        $answersheet->McqResponses()->delete();
        $answersheet->McqResponses()->createMany($mcqresponses);

        return response()->json(['issaved' => true]);
    }

    public function submit(Request $request)
    {
        $data = (object)$request->validate([
            "answer_sheet_id" => "required|integer|exists:answer_sheets,id",
            "exam_id" => "required|integer|exists:exams,id",
        ]);
        $answersheet = AnswerSheet::find($data->answer_sheet_id);
        if ($answersheet->isSubmited()) {abort(401);}
        $answersheet->update([
            "submited_at" => Carbon::now(),
        ]);

        return response()->json(['issaved' => true]);
    }

    public function info(Request $request)
    {
        $data = (object)$request->validate([
            "answer_sheet_id" => "required|integer|exists:answer_sheets,id",
        ]);
        $answersheet = AnswerSheet::find($data->answer_sheet_id);
        if ($answersheet->isSubmited()) abort(401);
        return response()->json([
            'exam_id' => $answersheet->Exam->id,
            'answer_sheet_id' => $answersheet->id,
            'time_info' => $this->getAvailableTimeInfomation($answersheet->Exam, $answersheet),

        ]);
    }

    public function summery(Request $request)
    {
        $data = (object)$request->validate([
            "examid" => "required|integer|exists:exams,id",
        ]);

        $exam = Exam::find($data->examid);
        $answersheet = $exam->AnswerSheets()->where('student_id', '=', $request->user()->student->id)->first();
        $subject = $exam->Subject;


        return [
            "exam_name" => $exam->title,
            "attempt_date" => $answersheet->created_at->format("Y-m-d"),
            "subject" =>  [
                "id" => $subject->id,
                "name" => $subject->name
            ],
            "marks" => $this->getMarks($answersheet),
            "questions" => [
                "mcqs" =>  $this->getMcqQuestionsSummery($exam,$answersheet),
            ]
        ];
    }
    private function getMcqQuestionsSummery(Exam $exam, AnswerSheet $answersheet)
    {
        $questions = $exam->Questions()->where("question_type", "=", Question::MCQ_QUESTION)->get();
        $formatedmcqs = [];
        foreach ($questions as $question) {
            $mcq = $question->McqQuestion;
            
           
            $formatedmcqs[] = [
                "question" => $mcq->question,
                "answers" =>  $this->getMcqAnswersSummery($answersheet, $mcq),
                "correctanswer" => $this->getCorrectMcqAnswer($mcq),
                "status" => $this->getMcqQuestionStatus($answersheet,$mcq),
            ];
        }
        return $formatedmcqs;
    }
    private function getMcqAnswersSummery(AnswerSheet $answersheet, McqQuestion $mcq)
    {
        $mcqanswers = $mcq->McqAnswers;
        
        $formatedmcqanswers = [];
        foreach ($mcqanswers as $mcqanswer) {
            $formatedmcqanswers[] = [
                "id" => $mcqanswer->id,
                "answer" => $mcqanswer->answer,
                "is_choosed" =>  $this->isResponseMcq($answersheet, $mcq, $mcqanswer),

            ];
        }
        return $formatedmcqanswers;
    }
    private function getMcqQuestionStatus(AnswerSheet $answersheet, McqQuestion $mcqquestion){
        $mcqresponse = $this->getMcqResponse($answersheet, $mcqquestion);
        return [
            "is_correct" => $this->isMcqResponseCorrect($mcqquestion, $mcqresponse),
            "is_not_responded" => !$mcqresponse,
        ];
    }
    private function isMcqResponseCorrect(McqQuestion $mcqquestion, $mcqresponse){
        if(!$mcqresponse) return false;
        return $mcqquestion->getCorrectMcqAnswer()->id == $mcqresponse->mcq_answer_id;
    }
   
    private function isResponseMcq(AnswerSheet $answersheet, McqQuestion $mcqquestion, McqAnswer $mcqanswer){
        $mcqresponse = $this->getMcqResponse($answersheet, $mcqquestion);

        if(!$mcqresponse) return false;
        return $mcqresponse->mcq_answer_id == $mcqanswer->id;
    }
    private function getMcqResponse(AnswerSheet $answersheet,McqQuestion $mcqquestion){
        $mcqresponse = $answersheet->McqResponses()->where('mcq_question_id', '=', $mcqquestion->id)->first();
        return $mcqresponse;
    }
    private function getCorrectMcqAnswer(McqQuestion $mcqquestion){
        $correctanswer = $mcqquestion->getCorrectMcqAnswer();
        return [
            "id" => $correctanswer->id,
            "answer"=> $correctanswer->answer,
        ];
    }
    private function getMarks(AnswerSheet $answersheet){
        $totalmcqcount = $answersheet->Exam->Questions()->count();
        $correctmcqcount = $this->getCorrectMcqCount($answersheet);

        return [
            'totalmcqcount' => $totalmcqcount,
            'correctmcqcount' => $correctmcqcount,
            'totalmarks' => 100,
            'value' => round(($correctmcqcount/$totalmcqcount)*100, 2),
        ];
    }

    private function getCorrectMcqCount($answersheet){
        $responses = $answersheet->McqResponses;
        $count = 0;
        foreach($responses as $response){
            if($this->isMcqResponseCorrect($response->McqQuestion, $response)) $count++;
        }   
        return $count;
    }
}
