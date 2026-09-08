<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Real E-E-A-T support (Google: a quality framework, not a
            // literal ranking checkbox — but genuine author identity is
            // what it actually asks for) — optional, admin-fillable,
            // never fabricated when left blank.
            $table->string('job_title')->nullable()->after('name');
            $table->text('bio')->nullable()->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_title', 'bio']);
        });
    }
};
