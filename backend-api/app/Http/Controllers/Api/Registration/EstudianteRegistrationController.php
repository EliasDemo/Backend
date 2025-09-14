<?php

namespace App\Http\Controllers\Api\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudiante\EstudianteStoreRequest;
use App\Services\Estudiantes\EstudianteService;

class EstudianteRegistrationController extends Controller
{
    public function __construct(private readonly EstudianteService $service) {}

    public function store(EstudianteStoreRequest $request)
    {
        // Solo campos validados de persona
        $personaData = $request->safe()->only([
            'dni','apellidos','nombres','email_institucional','email_personal','celular','sexo','fecha_nacimiento'
        ]);

        // Campos académicos validados
        $academic = $request->safe()->only([
            'ep_sede_id','ingreso_periodo_id','codigo','ciclo_actual','cohorte_codigo'
        ]);

        // Casts explícitos a entero cuando aplica
        $academic['ep_sede_id'] = $request->integer('ep_sede_id');
        if ($request->filled('ingreso_periodo_id')) {
            $academic['ingreso_periodo_id'] = $request->integer('ingreso_periodo_id');
        }
        if ($request->filled('ciclo_actual')) {
            $academic['ciclo_actual'] = (int) $request->input('ciclo_actual');
        }

        $result = $this->service->register($personaData, $academic);

        return response()->json([
            'message'           => 'Estudiante registrado correctamente',
            'persona'           => $result['persona'],
            'user'              => [
                'id'       => $result['user']->id,
                'username' => $result['user']->username,
                'roles'    => $result['user']->getRoleNames()->values()->all(),
            ],
            'password_temporal' => $result['passwordTemporal'], // solo en esta respuesta
            'estudiante'        => $result['row'],
        ], 201);
    }
}
