<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('driver')->default('mysql');
            $table->string('host');
            $table->string('database');
            $table->string('username');
            $table->text('password')->nullable();
            $table->string('port')->nullable();
            $table->string('cron')->default('0 0 * * *');
            $table->string('sql_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};
