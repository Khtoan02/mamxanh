<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Real User Monitoring for Core Web Vitals — one row per metric
        // report from an actual visitor's browser (web-vitals JS library),
        // not synthetic/lab data. No visitor identifier: only aggregate
        // p75 numbers are ever shown, so there's nothing to link back to
        // an individual visit.
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric', 10); // LCP | INP | CLS | FCP | TTFB
            $table->float('value');
            $table->string('rating', 20)->nullable(); // good | needs-improvement | poor (from web-vitals itself)
            $table->string('path', 255)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['metric', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
