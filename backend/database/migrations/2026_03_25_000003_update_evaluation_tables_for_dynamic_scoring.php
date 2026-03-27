<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEvaluationTablesForDynamicScoring extends Migration
{
    public function up()
    {
        Schema::table('evaluation_dimensions', function (Blueprint $table) {
            $table->string('target_role')->nullable()->after('weight');
            $table->string('category')->nullable()->after('target_role');
        });

        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(0)->after('scoring_criteria');
            $table->decimal('max_score', 5, 2)->default(100)->after('weight');
            $table->string('target_role')->nullable()->after('max_score');
        });

        Schema::table('evaluation_answers', function (Blueprint $table) {
            $table->integer('evaluator_id')->nullable()->after('task_id');
            $table->integer('evaluatee_id')->nullable()->after('evaluator_id');
            
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('evaluatee_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('evaluation_reports', function (Blueprint $table) {
            $table->text('dimension_scores')->nullable()->after('scores');
            $table->text('evaluator_scores')->nullable()->after('dimension_scores');
            $table->string('level')->nullable()->after('total_score');
            $table->text('formula_snapshot')->nullable()->after('level');
        });
    }

    public function down()
    {
        Schema::table('evaluation_reports', function (Blueprint $table) {
            $table->dropColumn(['dimension_scores', 'evaluator_scores', 'level', 'formula_snapshot']);
        });

        Schema::table('evaluation_answers', function (Blueprint $table) {
            $table->dropForeign(['evaluator_id']);
            $table->dropForeign(['evaluatee_id']);
            $table->dropColumn(['evaluator_id', 'evaluatee_id']);
        });

        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->dropColumn(['weight', 'max_score', 'target_role']);
        });

        Schema::table('evaluation_dimensions', function (Blueprint $table) {
            $table->dropColumn(['target_role', 'category']);
        });
    }
}
