<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsultationRequestSource;
use App\Models\ConsultationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $message = filled($this->input('message')) ? trim((string) $this->input('message')) : null;

        // «Hesablama tələb et» on a product page: the request names the product so the
        // team knows what to quote. The reference is shortened, never trusted as HTML.
        if ($this->input('source') === ConsultationRequestSource::Product->value && filled($this->input('product_ref'))) {
            $ref = str((string) $this->input('product_ref'))->squish()->limit(300)->toString();
            $message = trim("Məhsul: {$ref}\n".($message ?? ''));
        }

        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'phone' => trim((string) $this->input('phone')),
            'message' => $message,
            'source' => $this->input('source') ?: ConsultationRequestSource::Home->value,
            'b2b_plan' => $this->input('b2b_plan') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s()\-]{7,25}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', Rule::enum(ConsultationRequestSource::class)],
            // A plan only makes sense on a B2B lead.
            'b2b_plan' => ['nullable', 'prohibited_unless:source,b2b', Rule::in(array_keys(ConsultationRequest::B2B_PLANS))],
        ];
    }
}
