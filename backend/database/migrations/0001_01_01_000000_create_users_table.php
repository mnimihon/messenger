<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar_url')->nullable();

            $table->integer('verification_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();

            $table->integer('reset_password_code')->nullable();
            $table->timestamp('reset_password_code_expires_at')->nullable();
            $table->timestamp('reset_password_code_sent_at')->nullable();

            $table->integer('reset_password_attempts')->default(0);
            $table->timestamp('reset_password_locked_until')->nullable();

            $table->integer('login_attempts')->default(0);
            $table->timestamp('login_locked_until')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
