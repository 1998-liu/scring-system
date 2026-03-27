<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoringRulesTable extends Migration
{
    public function up()
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('target_role');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scoring_rule_weights', function (Blueprint $table) {
            $table->id();
            $table->integer('scoring_rule_id');
            $table->string('evaluator_role');
            $table->decimal('weight', 5, 2);
            $table->timestamps();

            $table->foreign('scoring_rule_id')->references('id')->on('scoring_rules')->onDelete('cascade');
        });

        Schema::create('scoring_rule_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('scoring_rule_id');
            $table->string('name');
            $table->string('target_role');
            $table->text('description')->nullable();
            $table->text('weights');
            $table->integer('changed_by');
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->foreign('scoring_rule_id')->references('id')->on('scoring_rules')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('scoring_rule_histories');
        Schema::dropIfExists('scoring_rule_weights');
        Schema::dropIfExists('scoring_rules');
    }
}
