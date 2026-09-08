<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // 'error' | 'not_found'
            $table->string('message', 500);
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
