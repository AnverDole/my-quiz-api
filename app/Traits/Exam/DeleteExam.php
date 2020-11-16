<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait DeleteExam
{
    /**
     * Delete given exam. 
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function deleteexam(Request $request)
    {
        $exam = $request->user()->instructor->Exams()->where("id", '=', $request->input("exam"))->first();

        try {
            DB::beginTransaction();
            //remove all student's responses. 
            foreach ($exam->AnswerSheets as $answersheet) {
                $answersheet->McqResponses()->delete();
            }
            $answersheet->delete();

            foreach ($exam->Questions as $question) {
                $question->McqQuestion->McqAnswers()->delete();
                $question->McqQuestion->delete();
            }
            $exam->Questions()->delete();
            $exam->delete();
            DB::commit();

            return response()->json(["is_deleted" => true]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(["is_deleted" => false, "message" => "Unknown error occured!", "E" => $e->getMessage()], 500);
        }
    }
}
