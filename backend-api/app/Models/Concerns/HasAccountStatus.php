<?php

namespace App\Models\Concerns;

use App\Enums\AccountStatus;

trait HasAccountStatus
{
    public function isActive(): bool
    {
        return $this->status === AccountStatus::ACTIVE;
    }

    public function isViewOnly(): bool
    {
        return $this->status === AccountStatus::VIEW_ONLY;
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::SUSPENDED;
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('status', AccountStatus::ACTIVE->value);
    }

    public function scopeViewOnly($query)
    {
        return $query->where('status', AccountStatus::VIEW_ONLY->value);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', AccountStatus::SUSPENDED->value);
    }
}
