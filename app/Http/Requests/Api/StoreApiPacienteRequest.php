<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Pacientes\StorePacienteRequest;

class StoreApiPacienteRequest extends StorePacienteRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['activo']);

        return $rules;
    }
}
