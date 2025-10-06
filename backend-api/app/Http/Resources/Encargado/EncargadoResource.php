<?php

namespace App\Http\Resources\Encargado;

use Illuminate\Http\Resources\Json\JsonResource;

class EncargadoResource extends JsonResource
{
    private $user;
    private $plainPassword;

    public function __construct($resource, $user = null, $plainPassword = null)
    {
        parent::__construct($resource);
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }

    public function toArray($request)
    {
        /** @var \App\Models\EncargadoSede $enc */
        $enc = $this->resource;

        return [
            'id'        => $enc->id,
            'sede'      => [
                'id'   => $enc->sede->id,
                'nombre' => $enc->sede->nombre ?? null,
            ],
            'periodo'   => [
                'id'    => $enc->periodo->id,
                'codigo'=> $enc->periodo->codigo ?? null,
            ],
            'persona'   => [
                'id'       => $enc->persona->id,
                'dni'      => $enc->persona->dni,
                'nombres'  => $enc->persona->nombres,
                'apellidos'=> $enc->persona->apellidos,
                'email'    => $enc->persona->email_institucional ?? $enc->persona->email_personal,
                'celular'  => $enc->persona->celular,
            ],
            'cargo'     => $enc->cargo,
            'activo'    => (bool)$enc->activo,
            'user'      => $this->user ? [
                'id'       => $this->user->id,
                'username' => $this->user->username,
                'email'    => $this->user->email,
            ] : null,
            // Sólo la primera vez si se generó
            'plain_password' => $this->plainPassword,
        ];
    }
}
