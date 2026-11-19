<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TenderSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** «Tender yarat» — only the essentials of a tender; the admin completes the rest. */
final class StoreTenderSubmission extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn (string $key): ?string => filled($this->input($key)) ? trim((string) $this->input($key)) : null;
        $number = fn (string $key): ?string => ($v = $clean($key)) !== null ? str_replace([' ', ','], ['', '.'], $v) : null;

        $this->merge([
            'title' => $clean('title'),
            'description' => $clean('description'),
            'city' => $clean('city'),
            'area_sqm' => $number('area_sqm'),
            'budget' => $number('budget'),
            'currency' => $clean('currency') ?? 'AZN',
            'organizer_name' => $clean('organizer_name'),
            'contact_name' => $clean('contact_name'),
            'phone' => $clean('phone'),
            'email' => ($email = $clean('email')) !== null ? mb_strtolower($email) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'tender_category_id' => ['required', 'integer', Rule::exists('tender_categories', 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'area_sqm' => ['nullable', 'numeric', 'min:1', 'max:10000000'],
            'budget' => ['nullable', 'numeric', 'min:1', 'max:1000000000000'],
            'currency' => ['required', Rule::in(TenderSubmission::CURRENCIES)],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:today', 'before:+2 years'],
            'organizer_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s()\-]{7,25}$/'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        $label = fn (string $key, string $fallback): string => is_string($v = t('tenders.create.'.$key)) && $v !== 'tenders.create.'.$key ? $v : $fallback;

        return [
            'title' => $label('title', 'Tenderin adı'),
            'tender_category_id' => $label('category', 'Kateqoriya'),
            'city' => $label('city', 'Şəhər / ünvan'),
            'description' => $label('description', 'İşin təsviri'),
            'area_sqm' => $label('area', 'Sahə'),
            'budget' => $label('budget', 'Təxmini büdcə'),
            'currency' => $label('currency', 'Valyuta'),
            'deadline_at' => $label('deadline', 'Təkliflərin son tarixi'),
            'organizer_name' => $label('organizer', 'Sifarişçi / şirkət'),
            'contact_name' => $label('contact_name', 'Əlaqə şəxsi'),
            'phone' => $label('phone', 'Telefon'),
            'email' => $label('email', 'E-poçt'),
        ];
    }
}
