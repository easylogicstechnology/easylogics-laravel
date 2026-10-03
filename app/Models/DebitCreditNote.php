<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Debit Note / Credit Note (CakePHP model DebitCreditNote).
 *
 * Stored in journal_vouchers alongside the ordinary Journal Vouchers, so every existing ledger / dues /
 * trial-balance report and the bill engine see the rows without any change to them. The two models split the
 * table by entry_type: JournalVoucher -> 'JV' (the Journal Voucher screen), DebitCreditNote -> 'DN' / 'CN'.
 * The split is enforced here, in the model, not left to each caller to remember.
 */
class DebitCreditNote extends Model
{
    public const ENTRY_TYPES = ['DN', 'CN'];

    protected $table = 'journal_vouchers';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $guarded = [];

    protected static function booted(): void
    {
        // Every read / update / delete through this model is limited to DN / CN rows, so a Journal Voucher can
        // never be listed, counted or picked up for deletion from the note screen.
        static::addGlobalScope('notes', fn (Builder $q) => $q->whereIn($q->getModel()->getTable() . '.entry_type', self::ENTRY_TYPES));

        // Only DN / CN rows are ever written through this model.
        static::saving(function (self $note) {
            $type = $note->getAttribute('entry_type');

            return $type === null || in_array($type, self::ENTRY_TYPES, true);
        });
    }
}
