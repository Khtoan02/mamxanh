<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('visitor_id', 40)->nullable()->after('message');
            $table->string('status', 20)->default('new')->after('visitor_id');
            $table->text('notes')->nullable()->after('status');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['visitor_id', 'status', 'notes']);
        });
    }
};
