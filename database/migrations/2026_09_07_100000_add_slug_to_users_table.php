<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Author archive pages (/tac-gia/{slug}) need a stable, readable URL key.
 * Nullable + unique rather than required so no existing row can block the
 * migration; App\Models\User fills it automatically on save.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Backfill existing accounts with the same algorithm the model uses,
        // so URLs for accounts created before this migration look identical
        // to ones created after it.
        foreach (DB::table('users')->select('id', 'name')->get() as $user) {
            $base = Str::slug($user->name) ?: 'tac-gia';
            $slug = $base;
            $i = 2;

            while (DB::table('users')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            DB::table('users')->where('id', $user->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
