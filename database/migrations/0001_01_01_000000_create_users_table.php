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
            $table->string('name');
            $table->string('phone');
            $table->string('company_name')->nullable();
            $table->string('company_location')->nullable();
            $table->string('proof_of_registration')->nullable();
            $table->string('certificate_and_compliance')->nullable();
            $table->string('license')->nullable();
            $table->string('job_title')->nullable();
            $table->string('proof_of_location')->nullable();
            $table->string('bank_name')->nullable();
            $table->string("account_number")->nullable();
            $table->integer('balance')->default(0);
            $table->string('email')->unique();
            $table->string('registration_document')->nullable();
            $table->integer('yearly_revenue')->nullable();
            $table->integer('number_of_staff')->nullable();
            $table->integer('number_of_patients')->nullable();
            $table->string('company_logo')->nullable();
            $table->json('company_certification_documents')->nullable();
            $table->boolean('isAdmin')->nullable();
            $table->boolean('kycCompleted')->default(false);
            $table->integer('otp_code')->nullable();
            $table->integer('password_otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
           
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
