<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->input('id');
        return [
            'name'           => 'required|string|max:255|unique:warehouses,name,' . ($id ?? 'NULL') . ',id',
            'polygon_coords' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'اسم المستودع',
            'location' => 'الموقع',
        ];
    }
}
