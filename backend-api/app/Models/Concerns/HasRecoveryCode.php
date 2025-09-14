<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;

trait HasRecoveryCode
{
    public function generateRecoveryCode(int $length = 6, int $minutes = 30): string
    {
        // Código numérico: Str::random() si prefieres alfanumérico
        $code = str_pad((string)random_int(0, 10**$length - 1), $length, '0', STR_PAD_LEFT);

        $this->recovery_code = $code;
        $this->recovery_expires_at = Carbon::now()->addMinutes($minutes);
        $this->save();

        return $code;
    }

    public function clearRecoveryCode(): void
    {
        $this->recovery_code = null;
        $this->recovery_expires_at = null;
        $this->save();
    }

    public function recoveryCodeIsValid(?string $code): bool
    {
        if (!$code || !$this->recovery_code) {
            return false;
        }

        return $this->recovery_code === $code
            && $this->recovery_expires_at
            && now()->lessThanOrEqualTo($this->recovery_expires_at);
    }
}
