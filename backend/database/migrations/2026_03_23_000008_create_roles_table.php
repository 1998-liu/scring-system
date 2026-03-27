<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 插入默认角色
        \Illuminate\Support\Facades\DB::table('roles')->insert([
            ['name' => '正职领导', 'description' => '公司正职领导', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '中层正职', 'description' => '公司中层正职', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '中层副职及助理', 'description' => '公司中层副职及助理', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '管理人员', 'description' => '公司管理人员', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}