<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ActionItem;

class UpdateActionItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // opsional: batasi role di sini
        // return $this->user()->hasAnyRole(['officer','manager','avp']);
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ActionItem::statuses())],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in'       => 'Status tidak valid.',
        ];
    }
}
