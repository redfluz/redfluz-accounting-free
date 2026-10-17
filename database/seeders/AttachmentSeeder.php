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

namespace Database\Seeders;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Purchase\PurchaseOrder;
use App\Models\System\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds the three attachment tables that nothing else populated.
 *
 * purchase_order_attachments, goods_receipt_attachments and
 * product_attachments were all empty, so the attachment controls on a purchase
 * order, a goods received note and a product had nothing to list, open or
 * delete.
 *
 * The files are real. A seeded row pointing at a path with no file behind it
 * would make every download answer with an error, which would test the missing
 * file rather than the download. Each is a genuine one pixel image or a small
 * PDF, both of which the upload allowlist accepts, so the seeded rows describe
 * files the application would actually have accepted.
 *
 * Paths are deterministic rather than random, unlike an upload. That is
 * deliberate: migrate:fresh empties the database but leaves storage/app/public
 * alone, so random names would leave an orphan on disk on every reseed. A stable
 * name means the same file is rewritten in place, and the file is only written
 * when it is actually absent.
 *
 * Re-running is safe: each row is located by its parent and original file name
 * before a new id is minted, so a repeat seed updates in place rather than
 * duplicating. Nothing is ever hard deleted.
 */
class AttachmentSeeder extends Seeder
{
    /**
     * A genuine one pixel PNG.
     *
     * @var string
     */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    /**
     * A minimal but structurally valid one page PDF.
     *
     * @var string
     */
    private const PDF_MINIMAL = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
        ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
        ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n"
        ."xref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n"
        ."trailer<</Size 4/Root 1 0 R>>\nstartxref\n203\n%%EOF\n";

    /**
     * The attachments to seed.
     *
     * Each names the parent, the directory the application would use for that
     * parent, and the file to place there.
     *
     * @var list<array<string, string>>
     */
    private const ATTACHMENTS = [
        [
            'table' => 'purchase_order_attachments',
            'parent_column' => 'purchase_order_id',
            'parent_lookup' => 'document_number',
            'parent' => 'po10001',
            'directory' => 'purchase-orders',
            'disk' => 'documents',
            'file' => 'seeded-purchase-terms.png',
            'original_name' => 'purchase-terms.png',
            'mime_type' => 'image/png',
            'contents' => 'PNG_1PX',
            'notes' => 'Signed purchase terms captured against the draft order.',
        ],
        [
            'table' => 'goods_receipt_attachments',
            'parent_column' => 'document_number',
            'parent_lookup' => 'document_number',
            'parent' => 'GRN-DEMO-10001',
            'directory' => 'goods-receipts',
            'file' => 'seeded-delivery-note.pdf',
            'original_name' => 'delivery-note.pdf',
            'mime_type' => 'application/pdf',
            'contents' => 'PDF_MINIMAL',
            'disk' => 'documents',
            'notes' => 'Supplier delivery note received with the goods.',
        ],
        [
            // product_attachments holds the product's photographs, which the
            // interface renders inline, so these stay on the public disk.
            'table' => 'product_attachments',
            'parent_column' => 'product_id',
            'parent_lookup' => 'sku',
            'parent' => 'ASU-VIVO-15',
            'directory' => 'products',
            'file' => 'seeded-product-photo.png',
            'original_name' => 'product-photo.png',
            'mime_type' => 'image/png',
            'contents' => 'PNG_1PX',
            'disk' => 'public',
            'notes' => 'Product photograph.',
        ],
    ];

    /**
     * Seed the attachments.
     */
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'admin@example.com')
            ->first();

        if ($user === null) {
            // Fall back to any user so the rows still carry an uploader.
            $user = User::query()->whereNull('deleted_at')->first();
        }

        if ($user === null) {
            return;
        }

        foreach (self::ATTACHMENTS as $attachment) {
            $this->seedAttachment($attachment, $user);
        }
    }

    /**
     * Seed one attachment: write the file, then the row that describes it.
     *
     * @param  array<string, string>  $attachment  The attachment definition.
     * @param  User  $user  The user recorded as the uploader.
     */
    private function seedAttachment(array $attachment, User $user): void
    {
        $parentId = $this->resolveParent($attachment);

        if ($parentId === null) {
            return;
        }

        $directory = $attachment['directory'].'/'.$parentId;
        $disk = $attachment['disk'];
        $path = $directory.'/'.$attachment['file'];

        // Only write when absent, so a reseed rewrites nothing and orphans
        // nothing. The bytes are identical either way.
        if (! Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->put($path, $this->contents($attachment));
        }

        $existing = $this->query($attachment)
            ->where($attachment['parent_column'], $parentId)
            ->where('original_name', $attachment['original_name'])
            ->first(['id', 'created_at', 'file_path']);

        $row = [
            $attachment['parent_column'] => $parentId,
            'file_path' => $path,
            'original_name' => $attachment['original_name'],
            'mime_type' => $attachment['mime_type'],
            'file_size' => Storage::disk($disk)->size($path),
            'uploaded_by' => $user->id,
            'uploaded_by_name' => $user->name,
            'deleted_at' => null,
            'updated_at' => now(),
        ];

        $this->query($attachment)->updateOrInsert(
            ['id' => $existing->id ?? (string) Str::uuid7()],
            $row + ['created_at' => $existing->created_at ?? now()]
        );
    }

    /**
     * Resolve the identifier of the parent an attachment hangs off.
     *
     * A purchase order is keyed by its own id inside the directory name, so its
     * id is returned rather than its document number.
     *
     * @param  array<string, string>  $attachment  The attachment definition.
     * @return string|null The parent id, or null when the parent is not seeded.
     */
    private function resolveParent(array $attachment): ?string
    {
        if ($attachment['parent_lookup'] === 'document_number') {
            return PurchaseOrder::query()
                ->where('document_number', $attachment['parent'])
                ->value('id');
        }

        if ($attachment['parent_lookup'] === 'sku') {
            return Product::query()
                ->where('sku', $attachment['parent'])
                ->value('id');
        }

        // Keyed directly by document number, so confirm the goods receipt itself
        // exists. Looking for it in the attachment table would always come back
        // empty, which is the very thing this seeder is fixing.
        $receiptExists = ProductLedger::query()
            ->where('document_number', $attachment['parent'])
            ->where('source_type', 'goods_receipt')
            ->exists();

        return $receiptExists ? $attachment['parent'] : null;
    }

    /**
     * A query builder for one of the attachment tables.
     *
     * @param  array<string, string>  $attachment  The attachment definition.
     * @return Builder The builder.
     */
    private function query(array $attachment): Builder
    {
        return DB::table($attachment['table']);
    }

    /**
     * The bytes of a seeded file.
     *
     * @param  array<string, string>  $attachment  The attachment definition.
     * @return string The decoded file contents.
     */
    private function contents(array $attachment): string
    {
        $contents = $attachment['contents'];

        return $contents === 'PNG_1PX'
            ? (string) base64_decode(self::PNG_1PX, true)
            : self::PDF_MINIMAL;
    }
}
