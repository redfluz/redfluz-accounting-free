<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the e-wallet provider feature from existing databases.
     *
     * Fresh installs no longer create the table or the column, but databases
     * that already ran the old migrations still carry both. The provider link
     * is dropped before the table so the foreign key can be removed cleanly.
     */
    public function up(): void
    {
        if (Schema::hasColumn('e_wallet_accounts', 'e_wallet_provider_id')) {
            // MySQL refuses to drop a column that still backs a foreign key, so
            // remove the constraint first. SQLite drops the column and its
            // constraint together.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                try {
                    Schema::table('e_wallet_accounts', function (Blueprint $table): void {
                        $table->dropForeign(['e_wallet_provider_id']);
                    });
                } catch (Throwable) {
                    // The constraint was already absent; continue with the drop.
                }
            }

            Schema::table('e_wallet_accounts', function (Blueprint $table): void {
                $table->dropColumn('e_wallet_provider_id');
            });
        }

        Schema::dropIfExists('e_wallet_providers');

        $pageIds = DB::table('pages')
            ->where('route_name', 'bank.e-wallet-providers.index')
            ->pluck('id');

        if ($pageIds->isNotEmpty()) {
            DB::table('page_permissions')->whereIn('page_id', $pageIds)->delete();
            DB::table('pages')->whereIn('id', $pageIds)->delete();
        }
    }

    /**
     * The provider feature was removed, so this migration is irreversible.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
