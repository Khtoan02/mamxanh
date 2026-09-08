<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy')->index();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
