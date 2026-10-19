<?php

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the deposit receipt attachment to transfers.
     *
     * A bank transfer is the point at which someone says a bank has credited a
     * deposit, and the proof of that is the slip, the credit advice or the
     * statement line. Attaching it to the transfer keeps the evidence beside the
     * claim it supports rather than in a separate filing tray.
     *
     * Two columns rather than one: the stored file is renamed to a random string
     * on the way in, so a path alone would offer the user a download called
     * something like "k3Jd8x....pdf" instead of the name they recognise. The
     * path says where the file is, the name says what it was.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
