<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            // Only ever populated when the URL that was clicked actually
            // carries these query params (ad/campaign links) — most rows
            // stay null, same as referrer_host.
            $table->string('utm_source', 100)->nullable()->after('language');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
            $table->string('utm_content', 100)->nullable()->after('utm_campaign');
            $table->string('utm_term', 100)->nullable()->after('utm_content');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']);
        });
    }
};
