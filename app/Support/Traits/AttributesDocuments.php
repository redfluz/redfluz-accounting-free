<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records who decided the fate of a document.
 *
 * Every document family records the same decisions: who approved it, who
 * cancelled it, and who undid a cancellation. Writing those columns by hand at
 * each call site would spread the naming across twenty methods and let it drift,
 * so they are set here instead.
 *
 * Each decision is stored as a pair, an id and a name, plus when it happened. The
 * name is a snapshot taken at the moment of the decision and is never revised.
 * Users are soft deleted rather than removed, so a join would still resolve
 * today, but a record of a decision that depends on a user row continuing to
 * exist is not a record of a decision: if a user is ever hard removed, or their
 * name is corrected, the document should still say who acted on it at the time.
 *
 * Every method here saves the document it was handed, and saves it once. The
 * alternative is to force fill and leave persistence to the caller, but then
 * every call site has to write the columns back out by name, which is exactly
 * the drift this trait exists to prevent.
 *
 * Authorship is not in this trait. It is stamped by {@see RecordsAuthor} as the
 * row is created, so that it cannot be forgotten on a path nobody thought of.
 */
trait AttributesDocuments
{
    /**
     * Record who approved a document, and when.
     *
     * @param  Model  $document  The document being approved.
     */
    protected function attributeApproved(Model $document): void
    {
        $this->attribute($document, 'approved_by', 'approved_name', 'approved_at');
    }

    /**
     * Record who cancelled a document, and when.
     *
     * @param  Model  $document  The document being cancelled.
     */
    protected function attributeCancelled(Model $document): void
    {
        $this->attribute($document, 'cancelled_by', 'cancelled_name', 'cancelled_at');
    }

    /**
     * Record who restored a cancelled document, and when.
     *
     * @param  Model  $document  The document being restored.
     */
    protected function attributeUndone(Model $document): void
    {
        $this->attribute($document, 'undone_by', 'undone_name', 'undone_at');
    }

    /**
     * Write the acting user into an id and name pair, stamp the event time, and
     * save once.
     *
     * Does nothing when the acting user is unknown, so a queued or console
     * context leaves whatever was already recorded alone rather than stamping a
     * null actor over a real one.
     *
     * @param  Model  $document  The document being acted on.
     * @param  string  $idColumn  The column holding the user id.
     * @param  string  $nameColumn  The column holding the name snapshot.
     * @param  string|null  $atColumn  The column holding when it happened.
     */
    private function attribute(
        Model $document,
        string $idColumn,
        string $nameColumn,
        ?string $atColumn = null,
    ): void {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $columns = [
            $idColumn => $user->getAuthIdentifier(),
            $nameColumn => (string) $user->name,
        ];

        if ($atColumn !== null) {
            $columns[$atColumn] = now();
        }

        $document->forceFill($columns);

        $document->save();
    }
}
