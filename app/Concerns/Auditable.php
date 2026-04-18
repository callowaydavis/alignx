<?php

namespace App\Concerns;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAudit('created', [], $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $excluded = ['updated_at', 'created_at'];

            $changed = array_filter(
                $dirty,
                fn ($key) => ! in_array($key, $excluded),
                ARRAY_FILTER_USE_KEY
            );

            if (empty($changed)) {
                return;
            }

            $oldValues = array_intersect_key($model->getOriginal(), $changed);
            $newValues = array_intersect_key($model->getAttributes(), $changed);

            $model->recordAudit('updated', $oldValues, $newValues);
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->getAuditableAttributes(), []);
        });
    }

    public function recordAudit(string $event, array $oldValues, array $newValues): void
    {
        Audit::create([
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'user_id' => Auth::id(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]);
    }

    protected function getAuditableAttributes(): array
    {
        $excluded = ['updated_at', 'created_at'];

        return array_filter(
            $this->getAttributes(),
            fn ($key) => ! in_array($key, $excluded),
            ARRAY_FILTER_USE_KEY
        );
    }
}
