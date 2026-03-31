<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country' => 'required|string|max:255',
            'product_interest' => 'required|string|in:whole,chipped,broken',
            'message' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'sender_name.required' => 'Por favor, ingresa tu nombre.',
            'company.required' => 'El nombre de la empresa es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debes ingresar un correo válido.',
            'country.required' => 'El país de destino es obligatorio.',
            'product_interest.required' => 'Por favor, selecciona un producto de interés.',
            'product_interest.in' => 'El producto seleccionado no es válido.',
            'message.required' => 'Por favor, indícanos los detalles de tu requerimiento.',
        ];
    }
}