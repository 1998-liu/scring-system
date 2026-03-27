<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateEvaluationQuestionsForNewTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluation_questions', function (Blueprint $table) {
            // 检查并添加 min_score 字段
            if (!Schema::hasColumn('evaluation_questions', 'min_score')) {
                $table->decimal('min_score', 8, 2)->nullable()->after('type');
            }
            
            // 检查并添加 max_score 字段
            if (!Schema::hasColumn('evaluation_questions', 'max_score')) {
                $table->decimal('max_score', 8, 2)->nullable()->after('min_score');
            }
            
            // 检查并添加 options 字段
            if (!Schema::hasColumn('evaluation_questions', 'options')) {
                $table->text('options')->nullable()->after('max_score');
            }
        });

        // 更新现有数据的类型
        DB::table('evaluation_questions')->whereNull('type')->orWhereNotIn('type', ['score_range', 'score_options'])->update(['type' => 'score_range']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluation_questions', function (Blueprint $table) {
            if (Schema::hasColumn('evaluation_questions', 'min_score')) {
                $table->dropColumn('min_score');
            }
            if (Schema::hasColumn('evaluation_questions', 'max_score')) {
                $table->dropColumn('max_score');
            }
            if (Schema::hasColumn('evaluation_questions', 'options')) {
                $table->dropColumn('options');
            }
        });
    }
}
