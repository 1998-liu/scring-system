
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationTasksTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('plan_id');
            $table->integer('evaluator_id');
            $table->integer('evaluatee_id');
            $table->string('status');
            $table->dateTime('deadline');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_tasks');
    }
}