<?php

namespace App\Support\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Audit log (specification B-03): who changed what and when. Needed to trace
 * transfers, payments and result corrections later.
 *
 * Every model names the attributes it wants recorded. The list is explicit on
 * purpose - a wildcard would end up writing password hashes, tokens and
 * magic links into the log.
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logOnly($this->auditedAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => __('audit.events.'.$eventName));
    }

    /**
     * Attributes whose changes are recorded.
     *
     * @return array<int, string>
     */
    abstract protected function auditedAttributes(): array;
}
