<?php

namespace App\Http\Requests;

use App\Models\Tariff;
use App\Services\Premium\Data\InsuredPersonInput;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContractRequest extends FormRequest
{
    private ?Tariff $tariff = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tariff_id' => ['required', 'integer', Rule::exists('tariffs', 'id')->where('is_active', true)],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after:date_start'],
            'insurance_sum' => ['required', 'numeric', 'gt:0'],
            'insured_persons' => ['required', 'array', 'min:1'],
            'insured_persons.*.name' => ['required', 'string', 'max:255'],
            'insured_persons.*.birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:date_start'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('tariff_id') || $validator->errors()->has('insured_persons')) {
                    return;
                }

                $tariff = $this->tariff();
                $count = count($this->input('insured_persons', []));

                if ($count > $tariff->max_insured_persons) {
                    $validator->errors()->add(
                        'insured_persons',
                        "The tariff allows at most {$tariff->max_insured_persons} insured persons."
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'insured_persons.*.birth_date.before_or_equal' => 'The birth date must not be later than the contract start date.',
            'tariff_id.exists' => 'The selected tariff does not exist or is inactive.',
        ];
    }

    public function attributes(): array
    {
        return [
            'insured_persons.*.name' => 'insured person name',
            'insured_persons.*.birth_date' => 'insured person birth date',
        ];
    }

    public function tariff(): Tariff
    {
        return $this->tariff ??= Tariff::query()->findOrFail($this->integer('tariff_id'));
    }

    public function dateStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('date_start'))->startOfDay();
    }

    public function dateEnd(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('date_end'))->startOfDay();
    }

    public function insuranceSum(): float
    {
        return (float) $this->input('insurance_sum');
    }

    public function insuredPersons(): array
    {
        return array_map(
            fn (array $person) => new InsuredPersonInput(
                $person['name'],
                CarbonImmutable::createFromFormat('Y-m-d', $person['birth_date'])->startOfDay(),
            ),
            $this->validated('insured_persons'),
        );
    }
}
