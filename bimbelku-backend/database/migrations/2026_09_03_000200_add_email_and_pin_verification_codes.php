<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'email_verification_required_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verification_required_at')->nullable()->after('email_verified_at');
            });
        }
        if (!Schema::hasTable('verification_codes')) {
            Schema::create('verification_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('purpose', 40);
                $table->string('code_hash');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'purpose', 'used_at'], 'verification_codes_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verification_required_at');
        });
    }
};
