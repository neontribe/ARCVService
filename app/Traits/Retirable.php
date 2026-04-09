<?php

namespace App\Traits;

use DomainException;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

trait Retirable
{
    /**
     * Magically invoked
     */
    public static function bootRetirable(): void
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            throw new LogicException(
                static::class . ' must use SoftDeletes to use the Retirable trait.'
            );
        }

        // Cancel any restore attempt on a retired model.
        static::restoring(static function ($model) {
            if ($model->retired_at !== null) {
                throw new DomainException(
                    "Retired " . get_class($model) . " [{$model->id}] cannot be restored."
                );
            }
        });
    }

    /**
     * Magically invoked - Adds retired_at to the model
     */
    public function initializeRetirable(): void
    {
        $this->casts['retired_at'] = 'datetime';
    }

    /**
     * Map of field => retirement value.
     * Override in the consuming class to match its DDL constraints.
     */
    protected function retirableFields(): array
    {
        return [
            'remember_token' => null,
        ];
    }

    public function retire(): void
    {
        if ($this->retired_at !== null) {
            return;
        }

        if (!$this->trashed()) {
            throw new DomainException(
                get_class($this) . " [$this->id] must be disabled before it can be retired."
            );
        }

        $this->forceFill(
            array_merge($this->retirableFields(), ['retired_at' => now()])
        )->save();
    }

    public function isRetired(): bool
    {
        return $this->retired_at !== null;
    }

    public function scopeRetired($query)
    {
        return $query->withTrashed()->whereNotNull('retired_at');
    }

    // Not trashed or retired
    public function scopeActive($query)
    {
        return $query->whereNull('retired_at');
    }
}
