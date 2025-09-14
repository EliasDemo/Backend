<?php

namespace App\Rules\EpSede;

use App\Models\EpSede;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlapEpSede implements ValidationRule
{
    public function __construct(
        private readonly ?int $epId,
        private readonly ?int $sedeId,
        private readonly ?string $desde,
        private readonly ?string $hasta,
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si faltan datos clave, no validamos esta regla
        if (!$this->epId || !$this->sedeId) {
            return;
        }

        $q = EpSede::query()
            ->where('escuela_profesional_id', $this->epId)
            ->where('sede_id', $this->sedeId)
            // Solapamiento: [a,b] con [c,d]  =>  a<=d AND c<=b (NULL = infinito)
            ->where(function ($qq) {
                $qq->where(function ($q1) {
                    $q1->whereNull('vigente_desde')
                       ->orWhere('vigente_desde', '<=', $this->hasta ?? '9999-12-31');
                })->where(function ($q2) {
                    $q2->whereNull('vigente_hasta')
                       ->orWhere('vigente_hasta', '>=', $this->desde ?? '0001-01-01');
                });
            });

        if ($this->ignoreId) {
            $q->where('id', '!=', $this->ignoreId);
        }

        if ($q->exists()) {
            $fail('El rango de vigencia se solapa con otro registro existente para la misma EP y sede.');
        }
    }
}
