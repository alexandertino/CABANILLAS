<?php

namespace App\Http\Requests\Clinica;

use Illuminate\Foundation\Http\FormRequest;

class SubirSeguimientoImagenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:15360',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'imagen.required' =>
                'Selecciona una imagen.',

            'imagen.image' =>
                'El archivo debe ser una imagen válida.',

            'imagen.mimetypes' =>
                'Solo se permiten imágenes JPG, PNG o WebP.',

            'imagen.max' =>
                'La imagen no puede superar los 15 MB.',
        ];
    }
}