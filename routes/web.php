<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/




Route::get('/login', 'App\\Http\\Controllers\\LoginController@Login');
Route::get('/register', 'App\\Http\\Controllers\\RegisterController@Register')->middleware('guest');
Route::get('/logout', 'App\\Http\\Controllers\\LoginController@Logout')->middleware('auth');
Route::get("/subjects" ,'App\\Http\\Controllers\\SubjectController@index')->middleware('auth');

Route::get("/instructor/exams" ,'App\\Http\\Controllers\\InstructorExamController@index')->middleware(['auth' ,'authrole:instructor']);
Route::get("/instructor/exams/new-exam" ,'App\\Http\\Controllers\\InstructorExamController@new')->middleware(['auth' ,'authrole:instructor']);
Route::get("/instructor/exams/edit-exam" ,'App\\Http\\Controllers\\InstructorExamController@edit')->middleware(['auth' ,'authrole:instructor']);
Route::get("/instructor/exams/exam" ,'App\\Http\\Controllers\\InstructorExamController@getexam')->middleware(['auth' ,'authrole:instructor']);
Route::get("/instructor/exam/delete" ,'App\\Http\\Controllers\\InstructorExamController@deleteexam')->middleware(['auth' ,'authrole:instructor']);


Route::get("/student/exams" ,'App\\Http\\Controllers\\StudentExamController@index')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/finished" ,'App\\Http\\Controllers\\StudentExamController@finished')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/exam/info" ,'App\\Http\\Controllers\\StudentExamController@examinfo')->middleware(['auth' ,'authrole:student']);

Route::get("/student/exams/exam/take" ,'App\\Http\\Controllers\\ExamController@take')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/exam/take/save" ,'App\\Http\\Controllers\\ExamController@save')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/exam/take/info" ,'App\\Http\\Controllers\\ExamController@info')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/exam/take/submit" ,'App\\Http\\Controllers\\ExamController@submit')->middleware(['auth' ,'authrole:student']);
Route::get("/student/exams/exam/take/summery" ,'App\\Http\\Controllers\\ExamController@summery')->middleware(['auth' ,'authrole:student']);

Route::get("/" , function(){
    dd(User::all());
});