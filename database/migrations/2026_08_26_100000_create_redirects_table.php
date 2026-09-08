<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 500); // path or pattern to match, e.g. /bai-viet-cu
            $table->string('target', 500); // where to send the visitor
            $table->string('match_type', 20)->default('exact'); // exact|contains|start|end|regex
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
