<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('content_role', 32)->nullable()->after('password')->index();
        });

        Schema::create('content_role_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_role', 32)->nullable();
            $table->string('to_role', 32);
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_role_audits');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['content_role']);
            $table->dropColumn('content_role');
        });
    }
};
