<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capability_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('capability_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'capability_id']);
        });

        $this->seedDefaultRolesAndCapabilities();
    }

    public function down(): void
    {
        Schema::dropIfExists('capability_role');
    }

    /**
     * Default roles/capabilities per SRS section IV.1.c — inserted here
     * (not a seeder) so both fresh installs (via the web wizard, which only
     * runs `migrate`) and already-installed sites (via a future `migrate`)
     * get them automatically.
     */
    private function seedDefaultRolesAndCapabilities(): void
    {
        $now = now();

        $capabilities = [
            'read', 'edit_posts', 'edit_others_posts', 'publish_posts',
            'delete_posts', 'delete_others_posts', 'manage_options',
            'manage_users', 'install_plugins',
        ];

        $capabilityIds = [];
        foreach ($capabilities as $slug) {
            $capabilityIds[$slug] = \DB::table('capabilities')->insertGetId([
                'slug' => $slug,
                'name' => ucwords(str_replace('_', ' ', $slug)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roles = [
            'super_admin' => ['name' => 'Super Admin', 'caps' => $capabilities],
            'administrator' => ['name' => 'Administrator', 'caps' => [
                'read', 'edit_posts', 'edit_others_posts', 'publish_posts',
                'delete_posts', 'delete_others_posts', 'manage_options', 'manage_users',
            ]],
            'editor' => ['name' => 'Editor', 'caps' => [
                'read', 'edit_posts', 'edit_others_posts', 'publish_posts', 'delete_posts', 'delete_others_posts',
            ]],
            'author' => ['name' => 'Author', 'caps' => ['read', 'edit_posts', 'publish_posts', 'delete_posts']],
            'contributor' => ['name' => 'Contributor', 'caps' => ['read', 'edit_posts']],
            'subscriber' => ['name' => 'Subscriber', 'caps' => ['read']],
        ];

        foreach ($roles as $slug => $role) {
            $roleId = \DB::table('roles')->insertGetId([
                'slug' => $slug,
                'name' => $role['name'],
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $pivot = array_map(fn ($capSlug) => [
                'role_id' => $roleId,
                'capability_id' => $capabilityIds[$capSlug],
            ], $role['caps']);

            \DB::table('capability_role')->insert($pivot);
        }
    }
};
