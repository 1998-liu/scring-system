<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoringLevelsTable extends Migration
{
    public function up()
    {
        Schema::create('scoring_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('color')->default('#409EFF');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('question_scoring_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('question_id');
            $table->string('level_name');
            $table->decimal('score', 5, 2);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('evaluation_questions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_scoring_levels');
        Schema::dropIfExists('scoring_levels');
    }
}
