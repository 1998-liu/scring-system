<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique();
            $table->string('role')->default('user'); // 系统角色：admin、user
            $table->string('company_role')->nullable(); // 公司角色：正职领导、中层正职、中层副职及助理、管理人员
            $table->integer('department_id')->nullable();
            $table->string('position')->nullable();
            $table->string('employee_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // 创建默认管理员用户
        DB::table('users')->insert([
            'name' => 'admin',
            'phone' => '18520416904',
            'password' => Hash::make('110liuhuanPGX!'),
            'role' => 'admin',
            'company_role' => '正职领导',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
