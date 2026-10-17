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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_institutions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('short_name')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->char('country_code', 2)->default('MY');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('banks', function (Blueprint $table): void {
            $table->foreignUuid('bank_institution_id')->nullable()->after('chart_id')
                ->constrained('bank_institutions')->nullOnDelete();
        });

        $now = now();

        DB::table('banks')->whereNull('deleted_at')->orderBy('name')->each(function (object $bank) use ($now): void {
            $institutionId = (string) Str::uuid();
            DB::table('bank_institutions')->insert([
                'id' => $institutionId,
                'name' => $bank->name,
                'short_name' => $this->shortName($bank->name),
                'bank_code' => $bank->bank_code,
                'swift_code' => $bank->swift_code,
                'country_code' => $bank->country_code ?: 'MY',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('banks')->where('id', $bank->id)->update(['bank_institution_id' => $institutionId]);
        });
    }

    private function shortName(string $name): string
    {
        return match (true) {
            str_contains(strtolower($name), 'malayan banking') => 'Maybank',
            str_contains(strtolower($name), 'cimb') => 'CIMB Bank',
            str_contains(strtolower($name), 'public bank') => 'Public Bank',
            default => $name,
        };
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bank_institution_id');
        });

        Schema::dropIfExists('bank_institutions');
    }
};
