<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('book')->nullable();
            $table->integer('chapter')->nullable();
            $table->longText('content')->nullable();
            $table->integer('type')->nullable()->comment('0单选1多选2判断3大于四个选项（暂时不用）');
            $table->longText('selectionA')->nullable()->comment('选项');
            $table->longText('selectionB')->nullable();
            $table->longText('selectionC')->nullable();
            $table->longText('selectionD')->nullable();
            $table->longText('selectionOther')->nullable();
            $table->string('answer')->nullable()->comment('答案');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('question');
    }
}
