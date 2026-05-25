<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_email_login ON users (LOWER(email))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_username_login ON users (LOWER(username))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_role_user_user_id ON role_user (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_role_user_role_id ON role_user (user_roles_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_personas_email_lookup ON personas (LOWER(email))');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') return;
        
        DB::statement('DROP INDEX IF EXISTS idx_personas_email_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_role_user_role_id');
        DB::statement('DROP INDEX IF EXISTS idx_role_user_user_id');
        DB::statement('DROP INDEX IF EXISTS idx_users_username_login');
        DB::statement('DROP INDEX IF EXISTS idx_users_email_login');
    }
};
