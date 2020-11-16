<?php

namespace App\Http\Controllers;

use App\Models\AnswerSheet\AnswerSheet;
use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqQuestion;
use App\Models\Role\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentExamController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'authrole:student']);
    }
    public function index(Request $request)
    {

        $current_page = $request->input("current_page") ?? 1;
        if ($current_page < 1 || !is_int($current_page)) {
            $current_page = 1;
        }

        $perpage = $request->input("per_page") ?? 3;
        $student = Student::with("AnswerSheets.Exam:id")->where("user_id", "=", $request->user()->id)->first();

        $finishedexamids = [];
        foreach ($student->AnswerSheets as $answersheet) {
            $finishedexamids[] = $answersheet->Exam->id;
        }

        $exams = Exam::whereNotIn("exams.id", $finishedexamids);


        $totalexamcount = $exams->count();
        $paginatedexams = $exams->skip(($current_page - 1) * $perpage)->limit($perpage)->orderBy("created_at", "DESC")->get();
        $formatedexams = $this->getFormatedExams($paginatedexams, $request->user()->Student);

        $paginate = new LengthAwarePaginator(
            $formatedexams,
            $totalexamcount,
            $perpage,
            $current_page,
        );

        $data = (object)[
            "exams" => $paginate->items(),
            "paginator" => (object)[
                'current_page' => $current_page,
                'total_pages' => ceil($totalexamcount / $perpage),
                'is_next_page_exists' => $totalexamcount > $current_page  * $perpage,
                'is_prev_page_exists' => $current_page > 1,
            ],
        ];
        return response()->json($data);
    }

    /**
     * Format and get the given exam.
     * @param App\Models\Exam\Exam $exam
     * @return array
     */
    function getFormatedExam(Exam $exam, Student $student)
    {
        $instructor =  $exam->Instructor->User;
        $instructorname = $instructor->firstname . " " . $instructor->lastname;
        $enrolledstudents = str_pad($exam->AnswerSheets()->count(), 2, "0", STR_PAD_LEFT);

        return [
            "id" => $exam->id,
            "title" => $exam->title,
            "duration" => $exam->duration->format("H\\h:i\\m"),
            "question_count" => $exam->Questions->count(),
            "student_enrolled" => $enrolledstudents,
            "instructor_name" => $instructorname,
            "status" => $this->getExamStatus($exam, $student),
        ];
    }

    /**
     * get the given attempt's(exam) status.
     * @param App\Models\Exam\Exam $exam
     * @param App\Models\Role\Student $student
     * @return array
     */
    private function getExamStatus(Exam $exam, Student $student)
    {
        //this will be true if the student is currently doing the exam and not finished yet.
        $in_proggress = $student->AnswerSheets()->where("exam_id", "=", $exam->id)->whereNull("submited_at")->exists();
        //this will be true if the student was finish the attempt(exam).
        $is_finished = $student->AnswerSheets()->where("exam_id", "=", $exam->id)->whereNotNull("submited_at")->exists();

        $data = [
            "in_proggress" => $in_proggress,
            "is_finished" => $is_finished,
        ];

        if ($is_finished) {
            $data["status_string"] = "Finished";
        } else if ($in_proggress) {
            $data["status_string"] = "Inproggress";
        }

        //assign the available time information if the student is was taked the exam attempt and not finished.
        if (!$is_finished && $in_proggress) {
            $answersheet = $student->AnswerSheets()->where("exam_id", "=", $exam->id)->first();
            $data["time_info"] = $this->getAvailableTimeInfomation($answersheet->Exam, $answersheet);
        }

        //attempt the attempt data if the student taked the exam and not finished. 
        if ($is_finished || $in_proggress) {
            $answersheet = $student->AnswerSheets()->where("exam_id", "=", $exam->id)->first();
            $data["answer_sheet_id"] = $answersheet->id;
            $data["attempt_info"] = [
                "attempted_at" => $answersheet->created_at->format("Y-m-d"),
                "marks" => $this->getMarks($answersheet)["value"],
            ];
        }
        return $data;
    }
    /**
     * get the given attempt's(exam) available(ETA) time infomation.
     * @param App\Models\Exam\Exam $exam
     * @param App\Models\AnswerSheet\AnswerSheet $answersheet
     * @return array
     */
    private function getAvailableTimeInfomation(Exam $exam, AnswerSheet $answersheet)
    {
        $examDuratationH = (int)$exam->duration->format("H");
        $examDuratationM = (int)$exam->duration->format("i");

        $startedtime = $answersheet->created_at->Settimezone("Asia/colombo");

        $shuldbesubmit = $startedtime->copy()->addHours($examDuratationH)->addMinutes($examDuratationM);
        if (Carbon::now() > $shuldbesubmit) {
            $availabletimeM = 0;
        } else {
            $availabletimeM = $shuldbesubmit->copy()->diffInMinutes(Carbon::now());
        }

        return [
            "available_time" => [
                "minitues" => $availabletimeM,
            ],
            "start_time" => $startedtime->format("Y-m-d H:i"),
            "shuld_be_submit" => $shuldbesubmit->format("Y-m-d H:i"),
        ];
    }

    /**
     * get the formated exams
     * @param Illuminate\Database\Eloquent\Collection $exams
     * @param App\Models\Role\Student $student
     * @return array
     */
    private function getFormatedExams($exams, Student $student)
    {

        $fexams_ = [];
        foreach ($exams as $exam) {
            $fexams_[] = $this->getFormatedExam($exam, $student);
        }
        return $fexams_;
    }
    /**
     * get the formated exams assosiated with given student.
     * @param Illuminate\Database\Eloquent\Collection $exams
     * @param App\Models\Role\Student $student
     * @return array
     */
    private function getFormatedStudentsMyExams(Collection $answersheets)
    {

        $fexams_ = [];
        foreach ($answersheets as $answersheet) {
            $fexams_[] = $this->getFormatedExam($answersheet->Exam, $answersheet->Student);
        }
        return $fexams_;
    }

    /**
     * get the given exams info.
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function examinfo(Request $request)
    {
        //validate the given data. 
        $valiadateddata = (object)$request->validate([
            'examid' => "required|integer|exists:exams,id"
        ], [
            'examid.exists' => "Requested exam was not found!"
        ]);

        $exam = Exam::find($valiadateddata->examid);
        $instructor = $exam->Instructor->User;

        $data = (object)[];
        $data->id = $exam->id;
        $data->subject = $exam->Subject->name;
        $data->instructor = $instructor->firstname . " " . $instructor->lastname;
        $data->questions = [
            "mcq" => $exam->McqCount(),
        ];

        //this property will store the exam status.
        $data->status = $this->getExamStatus($exam, $request->user()->student);
        $data->duration = $exam->duration->format("H\\h:i\\m");
        return response()->json(['examinfo' => (array)$data]);
    }


    /**
     * get all finished/currently taked exams assosiate with authed student. 
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function finished(Request $request)
    {
        //check that the user has role student. 
        if (!$request->user()->Student) return response()->json([], 401);
        $current_page = $request->input("current_page") ?? 1;
        if ($current_page < 1 || !is_int($current_page)) {
            $current_page = 1;
        }
        $perpage = $request->input("per_page") ?? 3;
        $student = $request->user()->Student;

        //get all the finished/non finishe and pending exams(answer sheets). 
        $answersheets = $student->AnswerSheets();

        // $exams = Exam::where("student_id", "=", $request->user()->Student->id)->has("AnswerSheets");

        $totalanswersheetscount = $answersheets->count();
        $paginatedanswersheets = $answersheets->skip(($current_page - 1) * $perpage)->limit($perpage)->orderBy("created_at", "DESC")->get();
        $formatedanswersheets = $this->getFormatedStudentsMyExams($paginatedanswersheets, $student);

        $paginate = new LengthAwarePaginator(
            $formatedanswersheets,
            $totalanswersheetscount,
            $perpage,
            $current_page,
        );

        $data = (object)[
            "exams" => $paginate->items(),
            "paginator" => (object)[
                'current_page' => $current_page,
                'total_pages' => ceil($totalanswersheetscount / $perpage),
                'is_next_page_exists' => $totalanswersheetscount > $current_page  * $perpage,
                'is_prev_page_exists' => $current_page > 1,
            ],
        ];
        return response()->json($data);
    }
    private function getMarks(AnswerSheet $answersheet)
    {
        $totalmcqcount = $answersheet->Exam->Questions()->count();
        $correctmcqcount = $this->getCorrectMcqCount($answersheet);

        return [
            'totalmcqcount' => $totalmcqcount,
            'correctmcqcount' => $correctmcqcount,
            'totalmarks' => 100,
            'value' => round(($correctmcqcount / $totalmcqcount) * 100, 2),
        ];
    }

    private function getCorrectMcqCount($answersheet)
    {
        $responses = $answersheet->McqResponses;
        $count = 0;

        foreach ($responses as $response) {
            if ($this->isMcqResponseCorrect($response->McqQuestion, $response)) $count++;
        }
        return $count;
    }
    private function isMcqResponseCorrect(McqQuestion $mcqquestion, $mcqresponse)
    {
        if (!$mcqresponse) return false;
        return $mcqquestion->getCorrectMcqAnswer()->id == $mcqresponse->mcq_answer_id;
    }
}
