<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
