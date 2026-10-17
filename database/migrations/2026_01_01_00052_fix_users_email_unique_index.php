<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the partial unique index on users.email.
     *
     * The create_users_table migration defines the index as partial
     * (WHERE deleted_at IS NULL) so a soft-deleted user's email can be
     * reused. However, every later Schema::table() call on users that forces
     * a SQLite table rebuild (e.g. adding the role_id foreign key) makes
     * Laravel recreate the index WITHOUT its WHERE clause, leaving a plain
     * unique index that rejects email reuse with a 500. Rebuilding it here,
     * after the last users table alteration, restores the intended shape.
     *
     * WARNING: any future migration that alters the users table in a way
     * that forces a SQLite rebuild will silently flatten this index again.
     * After adding such a migration, re-run this statement or re-issue
     * migrate:fresh and confirm the WHERE clause survived via:
     * select sql from sqlite_master where name = 'users_email_unique';
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement(
            'CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement(
            'CREATE UNIQUE INDEX users_email_unique ON users (email)'
        );
    }
};
