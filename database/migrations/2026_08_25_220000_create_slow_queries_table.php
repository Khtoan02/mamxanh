<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Real query-timing capture via DB::listen (app/Providers/AppServiceProvider.php)
        // — only the SQL template is stored, never bound parameter values,
        // so no user-submitted data (form input, search terms...) ever
        // lands in this table.
        Schema::create('slow_queries', function (Blueprint $table) {
            $table->id();
            $table->text('sql');
            $table->float('time_ms');
            $table->string('path', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slow_queries');
    }
};
