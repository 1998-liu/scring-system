<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationTaskUserPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 创建评估任务和评估者的多对多关系表
        Schema::create('evaluation_task_evaluators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 创建评估任务和被评估者的多对多关系表
        Schema::create('evaluation_task_evaluatees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('evaluation_task_evaluators');
        Schema::dropIfExists('evaluation_task_evaluatees');
    }
}
