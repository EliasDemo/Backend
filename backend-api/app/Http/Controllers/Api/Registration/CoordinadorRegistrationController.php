<?php

namespace App\Http\Controllers\Api\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinador\CoordinadorStoreRequest;
use App\Services\Coordinacion\CoordinadorService;

class CoordinadorRegistrationController extends Controller
{
    public function __construct(private readonly CoordinadorService $service) {}

    public function store(CoordinadorStoreRequest $request)
    {
        // Solo campos validados de persona
        $personaData = $request->safe()->only([
            'dni','apellidos','nombres','email_institucional','email_personal','celular','sexo','fecha_nacimiento'
        ]);

        // ep_sede_id validado; lo obtenemos como entero
        $epSedeId = $request->integer('ep_sede_id');

        $result = $this->service->register($personaData, $epSedeId);

        return response()->json([
            'message'           => 'Coordinador registrado correctamente',
            'persona'           => $result['persona'],
            'user'              => [
                'id'       => $result['user']->id,
                'username' => $result['user']->username,
                'roles'    => $result['user']->getRoleNames()->values()->all(),
            ],
            'password_temporal' => $result['passwordTemporal'], // muéstralo solo aquí
            'asignacion'        => $result['row'],
        ], 201);
    }
}
