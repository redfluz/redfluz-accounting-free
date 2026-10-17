<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Raised when a posting cannot be completed.
 *
 * These are programming errors rather than user mistakes. By the time a document
 * reaches the posting stage its permissions, its balance and its approval have
 * already been checked, so something failing here means the posting code was
 * told to do something it does not support, such as routing a movement to a
 * ledger that does not exist for that account kind.
 *
 * They are separate from the validation exceptions the controllers raise on
 * purpose: a user who fills in a form badly should be told which field is wrong
 * and be able to correct it, and a broken posting should surface as a fault in
 * the system with a stack trace rather than as a form validation message.
 */
final class PostingException extends RuntimeException
{
    /**
     * The company has no default currency, so no figure can be posted.
     */
    public static function noDefaultCurrency(): self
    {
        return new self(
            'No default currency is set, so no figure can be posted. '
            .'Mark a currency as the default before posting any document.'
        );
    }

    /**
     * An account kind has no money ledger to post into.
     *
     * @param  string  $kind  The account kind that was given.
     */
    public static function unknownLedgerKind(string $kind): self
    {
        return new self(
            "No money ledger exists for account kind [{$kind}]. "
            .'A money ledger is defined for bank, cash and e_wallet accounts only.'
        );
    }

    /**
     * A document kind with no provenance recorded against its ledger rows.
     *
     * @param  string  $subtype  The document sub-type that was given.
     */
    public static function unknownLedgerSourceType(string $subtype): self
    {
        return new self(
            "No ledger source type is defined for document sub-type [{$subtype}]. "
            .'A general ledger row must name the document that raised it, so the '
            .'sub-type has to be mapped to a source before it can be posted.'
        );
    }

    /**
     * A posting entry carries amounts on both sides, or names no chart.
     *
     * @param  string  $identifier  The entry's row id or document number, whichever
     *                              identifies it to the caller.
     */
    public static function oneSidedEntryRequired(string $identifier): self
    {
        return new self(
            "Entry [{$identifier}] does not name one side of a posting. An entry "
            .'is a debit or a credit against a single chart of account; an amount '
            .'on both sides, or a chart on neither side, cannot be filed.'
        );
    }

    /**
     * A posting was handed nothing to write, or two competing inputs.
     *
     * @param  string  $documentNumber  The document number of the posting.
     */
    public static function postingInputInvalid(string $documentNumber): self
    {
        return new self(
            "The posting for [{$documentNumber}] was given nothing to write, or "
            .'was given both its legs and its general ledger rows. A posting takes '
            .'exactly one input: the document\'s legs, or the general ledger rows '
            .'it was approved into, with the legs left empty for the poster to '
            .'derive from them.'
        );
    }

    /**
     * An invoice posting touched no receivable or payable chart.
     *
     * @param  string  $documentNumber  The document number of the posting.
     * @param  string  $sourceType  The source type the posting was filed under.
     */
    public static function noCounterpartyRow(string $documentNumber, string $sourceType): self
    {
        return new self(
            "Posting [{$documentNumber}] as [{$sourceType}] wrote no receivable "
            .'or payable row. An invoice or adjustment moves a customer or supplier '
            .'balance, which only happens when one of its charts belongs to the '
            .'receivable or payable report group, so if none did the counterparty '
            .'chart is misconfigured.'
        );
    }

    /**
     * A counterparty movement named no company to file it against.
     *
     * @param  string  $documentNumber  The document number of the posting.
     */
    public static function noCounterpartyCompany(string $documentNumber): self
    {
        return new self(
            "The posting for [{$documentNumber}] moved a customer or supplier "
            .'balance without naming the company it belongs to. A receivable or '
            .'payable row answers what one company owes or is owed, so a movement '
            .'against the receivable or payable chart on a document that names no '
            .'company cannot be filed.'
        );
    }
}
