<?php

namespace App\Http\Controllers;

use App\Models\Exam\Exam;
use App\Models\Exam\Mcq\McqQuestion;
use App\Models\Exam\Question;
use App\Traits\CreateExam;
use App\Traits\DeleteExam;
use App\Traits\EditExam;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class InstructorExamController extends Controller
{
    use CreateExam;
    use DeleteExam;
    use EditExam;

    public function __construct()
    {
        $this->middleware(["auth:api", "authrole:instructor"]);
    }

    /**
     * Get all exams assosiated with the auth user(instructor).
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $current_page = $request->input("current_page", 1);
        if ($current_page < 1 || !is_int($current_page)) {
            $current_page = 1;
        }
        $perpage = $request->input("per_page") ?? 3;

        $exams = $request->user()->instructor->Exams();

        $totalexamcount = $exams->count();
        $paginatedexams = $exams->skip(($current_page - 1) * $perpage)->limit($perpage)->orderBy("created_at", "DESC")->get();
        $formatedexams = $this->getFormatedExams($paginatedexams);

        $paginate = new LengthAwarePaginator(
            $formatedexams,
            $totalexamcount,
            $perpage,
            $current_page,
        );

        $data = (object)[
            "exams" => $paginate->items(),
            "paginator" => (object)[
                "total_exams" => $totalexamcount,
                'current_page' => $current_page,
                'total_pages' => ceil($totalexamcount / $perpage),
                'is_next_page_exists' => $totalexamcount > $current_page  * $perpage,
                'is_prev_page_exists' => $current_page > 1,
            ],
        ];
        return response()->json($data);
    }

    /**
     * Get the requested exam.
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function getexam(Request $request)
    {
        $data = (object)$request->validate(
            [
                'exam' => 'required|integer|exists:exams,id'
            ],
        );
       
        $exam = Exam::find($data->exam);
        return response()->json([
            'id' => $exam->id,
            'title' => $exam->title,
            'subject' => $this->getFormatedSubject($exam),
            'duration' => $exam->duration->format("H:m"),
            "questions" => [
                "mcq" => $this->getMcqQuestions($exam)
            ],
        ]);
    }

    /**
     * Format and get the given exam's subject.
     * @param App\Models\Exam\Exam $exam
     * @return array
     */
    function getFormatedSubject(Exam $exam)
    {
        $subject = $exam->Subject;
        return [
            'id' => $subject->id,
            'name' => $subject->name,
        ];
    }
    #region helper functions

    /**
     * Format and get the given exam.
     * @param App\Models\Exam\Exam $exam
     * @return array
     */
    function getFormatedExam(Exam $exam)
    {
        return [
            "id" => $exam->id,
            "title" => $exam->title,
            "duration" => $exam->duration->format("H:i"),
            "question_count" => $exam->Questions->count(),
            "student_enrolled" =>  $exam->AnswerSheets()->count(),
        ];
    }
    /**
     * Get the given exam's mcq questions.
     * @param App\Models\Exam\Exam $exam
     * @return array
     */
    private function getMcqQuestions(Exam $exam)
    {
        $mcqquestions = $exam->Questions()->where("question_type", '=', Question::MCQ_QUESTION)->get();
        $mcqs = [];
        foreach ($mcqquestions as $question) {
            $mcqquestion = $question->McqQuestion;
            $mcqs[] = [
                "id" => $question->id,
                "question" => $mcqquestion->question,
                "answers" => $this->getMcqAnswers($mcqquestion),
                "correctanswerid" => $this->getCorrectMcqAnswer($mcqquestion)["id"],
            ];
        }
        return $mcqs;
    }
    /**
     * Get the given mcq question's mcq answers.
     * @param App\Models\Exam\Mcq\McqQuestion $mcqquestion
     * @return array
     */
    private function getMcqAnswers(McqQuestion $mcqquestion)
    {
        $mcqanswers = [];
        foreach ($mcqquestion->McqAnswers as $mcqanswer) {
            $mcqanswers[] = [
                "id" => $mcqanswer->id,
                "answer" => $mcqanswer->answer,
                "is_correct" => $mcqanswer->is_correct,
            ];
        }
        return $mcqanswers;
    }
    /**
     * Get the given mcq question's correct answer.
     * @param App\Models\Exam\Mcq\McqQuestion $mcqquestion
     * @return array
     */
    private function getCorrectMcqAnswer(McqQuestion $mcqquestion)
    {
        $correctanswer = $mcqquestion->McqAnswers()->where("is_correct", "=", true)->first();
        return [
            "id" => $correctanswer->id,
            "answer" => $correctanswer->answer,
        ];
    }
    #endregion

    /**
     * get the formated exams.
     * @param Illuminate\Database\Eloquent\Collection $exams
     * @return array
     */
    private function getFormatedExams($exams)
    {
        $fexams_ = [];
        foreach ($exams as $exam) {
            $fexams_[] = $this->getFormatedExam($exam);
        }
        return $fexams_;
    }
}
