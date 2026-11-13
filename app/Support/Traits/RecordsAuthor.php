<?php

declare(strict_types=1);

namespace App\Support\Traits;

/**
 * Stamps the acting user onto a document as it is created.
 *
 * Authorship is the one attribution that can be captured mechanically. Approval,
 * posting, cancellation and undo are decisions taken later against a document
 * that already exists, so those are recorded explicitly by
 * {@see AttributesDocuments} at the call site. Creation is
 * not a decision: it is wherever the row is inserted, whether from a controller,
 * a console command or a seeder, and writing it by hand would mean every one of
 * those paths had to remember to. The ones that forgot would be invisible.
 *
 * The name is a snapshot rather than a reference, taken at the moment of
 * creation and never revised. A document that records who prepared it should
 * still say so after that user is renamed or removed, which is the same reason
 * the approval columns work this way.
 *
 * The hook does nothing when no user is authenticated. That covers seeders and
 * console commands, where attributing the document to whoever happens to be
 * running would be a lie, and leaves the columns null, which is the honest
 * answer.
 */
trait RecordsAuthor
{
    /**
     * Register the creation hook.
     */
    protected static function bootRecordsAuthor(): void
    {
        static::creating(function (self $document): void {
            $user = auth()->user();

            if ($user === null) {
                return;
            }

            $document->forceFill([
                'created_by' => $user->getAuthIdentifier(),
                'created_name' => (string) $user->name,
            ]);
        });
    }
}
