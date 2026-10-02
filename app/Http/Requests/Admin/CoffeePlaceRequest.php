<?php

namespace App\Http\Requests\Admin;

use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use App\Rules\MediaPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CoffeePlaceRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'string', new MediaPath($this->route('coffeePlace')?->image)],
            'wifi_provider' => ['nullable', 'string', 'max:255'],
            'wifi_speed' => ['required', new Enum(WifiSpeed::class)],
            'price_tier' => ['required', new Enum(CafePriceTier::class)],
            'park_fee' => ['nullable', 'integer', 'min:0'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'region' => ['nullable', 'string', 'max:255'],
            'is_recommended' => ['boolean'],
        ];
    }
}
