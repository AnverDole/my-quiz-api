<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/login', 'App\\Http\\Controllers\\LoginController@Login')->middleware('guest:api');
Route::post('/register', 'App\\Http\\Controllers\\RegisterController@Register')->middleware('guest:api');
Route::post('/logout', 'App\\Http\\Controllers\\LoginController@Logout')->middleware('auth:api');
Route::post("/subjects" ,'App\\Http\\Controllers\\SubjectController@index')->middleware('auth:api');

Route::post("/instructor/exams" ,'App\\Http\\Controllers\\InstructorExamController@index')->middleware(['auth:api' ,'authrole:instructor']);
Route::post("/instructor/exams/new-exam" ,'App\\Http\\Controllers\\InstructorExamController@new')->middleware(['auth:api' ,'authrole:instructor']);
Route::post("/instructor/exams/edit-exam" ,'App\\Http\\Controllers\\InstructorExamController@edit')->middleware(['auth:api' ,'authrole:instructor']);
Route::post("/instructor/exams/exam" ,'App\\Http\\Controllers\\InstructorExamController@getexam')->middleware(['auth:api' ,'authrole:instructor']);
Route::post("/instructor/exam/delete" ,'App\\Http\\Controllers\\InstructorExamController@deleteexam')->middleware(['auth:api' ,'authrole:instructor']);


Route::post("/student/exams" ,'App\\Http\\Controllers\\StudentExamController@index')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/finished" ,'App\\Http\\Controllers\\StudentExamController@finished')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/exam/info" ,'App\\Http\\Controllers\\StudentExamController@examinfo')->middleware(['auth:api' ,'authrole:student']);

Route::post("/student/exams/exam/take" ,'App\\Http\\Controllers\\ExamController@take')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/exam/take/save" ,'App\\Http\\Controllers\\ExamController@save')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/exam/take/info" ,'App\\Http\\Controllers\\ExamController@info')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/exam/take/submit" ,'App\\Http\\Controllers\\ExamController@submit')->middleware(['auth:api' ,'authrole:student']);
Route::post("/student/exams/exam/take/summery" ,'App\\Http\\Controllers\\ExamController@summery')->middleware(['auth:api' ,'authrole:student']);