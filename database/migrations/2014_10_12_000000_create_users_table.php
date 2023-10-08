<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->text('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('password');
            $table->text('address')->nullable();
            $table->text('phone')->nullable();
            $table->text('name')->nullable();
            $table->text('university')->nullable();
            $table->text('major')->nullable();
            $table->text('date_of_birth')->nullable();
            $table->text('id_number')->nullable();
            $table->text('student_id_number')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
