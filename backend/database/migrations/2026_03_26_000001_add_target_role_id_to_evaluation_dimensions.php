<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetRoleIdToEvaluationDimensions extends Migration
{
    public function up()
    {
        Schema::table('evaluation_dimensions', function (Blueprint $table) {
            $table->unsignedBigInteger('target_role_id')->nullable()->after('target_role');
            $table->foreign('target_role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('evaluation_dimensions', function (Blueprint $table) {
            $table->dropForeign(['target_role_id']);
            $table->dropColumn('target_role_id');
        });
    }
}
