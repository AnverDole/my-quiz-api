<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMcqResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mcq_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('answer_sheet_id');
            $table->unsignedBigInteger('mcq_question_id');
            $table->unsignedBigInteger('mcq_answer_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mcq_responses');
    }
}
