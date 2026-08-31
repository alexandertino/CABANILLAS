<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profesionales\StoreProfesionalRequest;
use App\Http\Requests\Profesionales\UpdateProfesionalRequest;
use App\Models\Profesional;
use Illuminate\Http\RedirectResponse;

class ProfesionalController extends Controller
{
    public function store(
        StoreProfesionalRequest $request
    ): RedirectResponse {
        Profesional::create($request->validated());

        return back()->with(
            'success',
            'Profesional registrado correctamente.'
        );
    }

    public function update(
        UpdateProfesionalRequest $request,
        Profesional $profesional
    ): RedirectResponse {
        $profesional->update($request->validated());

        return back()->with(
            'success',
            'Profesional actualizado correctamente.'
        );
    }

    public function destroy(
        Profesional $profesional
    ): RedirectResponse {
        $profesional->update([
            'activo' => false,
        ]);

        return back()->with(
            'success',
            'Profesional desactivado correctamente.'
        );
    }
}