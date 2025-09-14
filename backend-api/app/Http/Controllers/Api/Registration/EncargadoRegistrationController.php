<?php

namespace App\Http\Controllers\Api\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Encargado\EncargadoStoreRequest;
use App\Services\Encargados\EncargadoService;

class EncargadoRegistrationController extends Controller
{
    public function __construct(private readonly EncargadoService $service) {}

    public function store(EncargadoStoreRequest $request)
    {
        // Solo campos validados de persona
        $personaData = $request->safe()->only([
            'dni','apellidos','nombres','email_institucional','email_personal','celular','sexo','fecha_nacimiento'
        ]);

        // sede_id ya validado; lo obtienes como entero
        $sedeId = $request->integer('sede_id');

        $result = $this->service->register($personaData, $sedeId);

        return response()->json([
            'message'           => 'Encargado registrado correctamente',
            'persona'           => $result['persona'],
            'user'              => [
                'id'       => $result['user']->id,
                'username' => $result['user']->username,
                'roles'    => $result['user']->getRoleNames()->values()->all(),
            ],
            'password_temporal' => $result['passwordTemporal'],
            'asignacion'        => $result['row'],
        ], 201);
    }
}
