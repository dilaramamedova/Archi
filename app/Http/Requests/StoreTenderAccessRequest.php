<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TenderPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTenderAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn (string $key): ?string => filled($this->input($key)) ? trim((string) $this->input($key)) : null;

        $this->merge([
            'full_name' => $clean('full_name'),
            'company_name' => $clean('company_name'),
            'phone' => $clean('phone'),
            'email' => ($email = $clean('email')) !== null ? mb_strtolower($email) : null,
            'message' => $clean('message'),
        ]);
    }

    public function rules(): array
    {
        // A package must be chosen whenever the admin has published any.
        $hasPackages = TenderPackage::query()->active()->exists();

        return [
            'tender_package_id' => [
                $hasPackages ? 'required' : 'nullable',
                'integer',
                Rule::exists('tender_packages', 'id')->where('is_active', true),
            ],
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s()\-]{7,25}$/'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
