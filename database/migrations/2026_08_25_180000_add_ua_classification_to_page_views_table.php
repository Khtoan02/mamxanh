<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            // Coarse classification only, derived from the request at log
            // time — the raw User-Agent / Accept-Language header is never
            // stored, matching the no-PII posture of this table.
            $table->string('os', 20)->nullable()->after('device_type');
            $table->string('browser', 20)->nullable()->after('os');
            $table->string('language', 5)->nullable()->after('browser');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropColumn(['os', 'browser', 'language']);
        });
    }
};
