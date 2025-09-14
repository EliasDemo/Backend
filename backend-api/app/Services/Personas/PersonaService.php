<?php

namespace App\Services\Personas;

use App\Models\Persona;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PersonaService
{
    /** Crea/actualiza Persona buscando por DNI o email institucional (idempotente) */
    public function upsertFromDTO(array $data): Persona
    {
        return DB::transaction(function () use ($data) {
            $dni  = isset($data['dni']) ? trim($data['dni']) : null;
            $mail = isset($data['email_institucional']) ? Str::lower(trim($data['email_institucional'])) : null;

            $q = Persona::query();
            if ($dni)  $q->orWhere('dni', $dni);
            if ($mail) $q->orWhere('email_institucional', $mail);

            /** @var Persona|null $p */
            $p = $q->first();

            $payload = [
                'apellidos'            => Str::upper(trim($data['apellidos'] ?? '')),
                'nombres'              => Str::title(Str::lower(trim($data['nombres'] ?? ''))),
                'email_institucional'  => $mail,
                'email_personal'       => isset($data['email_personal']) ? Str::lower(trim($data['email_personal'])) : null,
                'celular'              => isset($data['celular']) ? preg_replace('/\D+/', '', $data['celular']) : null,
                'sexo'                 => $data['sexo'] ?? null,
                'fecha_nacimiento'     => $data['fecha_nacimiento'] ?? null,
            ];
            if ($dni) $payload['dni'] = $dni;

            return $p ? tap($p)->update($payload) : Persona::create($payload);
        });
    }
}
