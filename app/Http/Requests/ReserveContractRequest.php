<?php

namespace App\Http\Requests;

class ReserveContractRequest extends ContractRequest
{
    public const IDEMPOTENCY_HEADER = 'Idempotency-Key';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header(self::IDEMPOTENCY_HEADER),
        ]);
    }

    public function rules(): array
    {
        return parent::rules() + [
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'idempotency_key.required' => 'The Idempotency-Key header is required.',
        ];
    }

    public function idempotencyKey(): string
    {
        return $this->validated('idempotency_key');
    }
}
