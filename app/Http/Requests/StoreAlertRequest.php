<?php

namespace App\Http\Requests;

use App\Models\Alert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi token dicek di controller
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->severity)) {
            $this->merge(['severity' => strtolower(trim($this->severity))]);
        }
    }

    public function rules(): array
    {
        return [
            'source'          => ['required', 'string', 'max:50'],
            'external_id'     => ['nullable', 'string', 'max:191'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'severity'        => ['required', Rule::in(Alert::SEVERITIES)],
            'asset_ip'        => ['nullable', 'ip'],
            'asset_name'      => ['nullable', 'string', 'max:255'],
            'source_ip'       => ['nullable', 'ip'],
            'destination_ip'  => ['nullable', 'ip'],
            'mitre_tactic'    => ['nullable', 'string', 'max:100'],
            'mitre_technique' => ['nullable', 'string', 'max:100'],
            'detected_at'     => ['nullable', 'date'],
            'payload'         => ['nullable', 'array'],
        ];
    }
}
