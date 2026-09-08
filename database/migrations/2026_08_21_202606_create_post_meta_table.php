<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('meta_key')->index();
            $table->longText('meta_value')->nullable();

            $table->unique(['post_id', 'meta_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_meta');
    }
};
