<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = DB::getTablePrefix().'media';

        // `path`/`mime_type`/`size` no longer apply to an `embed` row (a
        // YouTube/Vimeo link — there's no file on our disk at all). Raw SQL
        // rather than Schema::table(...)->change() to avoid pulling in
        // doctrine/dbal just for 3 nullability flips.
        DB::statement("ALTER TABLE {$table} MODIFY path VARCHAR(255) NULL");
        DB::statement("ALTER TABLE {$table} MODIFY mime_type VARCHAR(255) NULL");
        DB::statement("ALTER TABLE {$table} MODIFY size BIGINT UNSIGNED NULL");

        Schema::table('media', function (Blueprint $table) {
            $table->string('kind', 20)->default('file')->after('mime_type'); // image|video|pdf|file|embed
            $table->unsignedInteger('width')->nullable()->after('kind');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->string('embed_url', 500)->nullable()->after('height');
            $table->string('embed_provider', 30)->nullable()->after('embed_url');
            $table->string('embed_thumbnail_url', 500)->nullable()->after('embed_provider');
        });

        // Backfill `kind` for rows uploaded before this column existed —
        // real derivation from the already-stored mime_type, not a guess.
        DB::table('media')->where('mime_type', 'like', 'image/%')->update(['kind' => 'image']);
        DB::table('media')->where('mime_type', 'like', 'video/%')->update(['kind' => 'video']);
        DB::table('media')->where('mime_type', 'application/pdf')->update(['kind' => 'pdf']);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['kind', 'width', 'height', 'embed_url', 'embed_provider', 'embed_thumbnail_url']);
        });

        $table = DB::getTablePrefix().'media';

        DB::statement("DELETE FROM {$table} WHERE path IS NULL");
        DB::statement("ALTER TABLE {$table} MODIFY path VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE {$table} MODIFY mime_type VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE {$table} MODIFY size BIGINT UNSIGNED NOT NULL");
    }
};
