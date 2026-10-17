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
        Schema::create('company_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_default']);
        });

        $now = now();
        DB::table('companies')
            ->whereNotNull('address')
            ->where('address', '<>', '')
            ->orderBy('id')
            ->chunk(500, function ($companies) use ($now): void {
                DB::table('company_addresses')->insert($companies->map(fn ($company): array => [
                    'id' => (string) Str::uuid7(),
                    'company_id' => $company->id,
                    'name' => 'Main Address',
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_addresses');
    }
};
