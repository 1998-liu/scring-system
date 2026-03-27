
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationReportsTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('evaluatee_id');
            $table->integer('plan_id');
            $table->decimal('total_score', 5, 2);
            $table->text('scores')->nullable();
            $table->text('role_scores')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_reports');
    }
}