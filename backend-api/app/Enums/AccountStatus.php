<?php

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case VIEW_ONLY = 'view_only';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isViewOnly(): bool
    {
        return $this === self::VIEW_ONLY;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }
}
