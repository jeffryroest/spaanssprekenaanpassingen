<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

final class StartMollieCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'purchase_type' => ['required', 'in:individual,business'],
            'company_name' => ['nullable', 'required_if:purchase_type,business', 'string', 'max:180'],
            'vat_id' => ['nullable', 'required_if:purchase_type,business', 'string', 'max:32', 'regex:/^[A-Za-z0-9. -]+$/'],
            'billing_street' => ['nullable', 'required_if:purchase_type,business', 'string', 'max:180'],
            'billing_postal_code' => ['nullable', 'required_if:purchase_type,business', 'string', 'max:32'],
            'billing_city' => ['nullable', 'required_if:purchase_type,business', 'string', 'max:120'],
            'billing_country' => ['nullable', 'required_if:purchase_type,business', 'string', 'size:2', 'alpha'],
            'recurring_consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Vul je voornaam in.',
            'last_name.required' => 'Vul je achternaam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'purchase_type.required' => 'Kies of je particulier of zakelijk bestelt.',
            'company_name.required_if' => 'Vul de bedrijfsnaam in.',
            'vat_id.required_if' => 'Vul het btw-identificatienummer in.',
            'vat_id.regex' => 'Vul een geldig btw-identificatienummer in.',
            'billing_street.required_if' => 'Vul het factuuradres in.',
            'billing_postal_code.required_if' => 'Vul de postcode van het factuuradres in.',
            'billing_city.required_if' => 'Vul de plaats van het factuuradres in.',
            'billing_country.required_if' => 'Vul de tweecijferige landcode in.',
            'billing_country.size' => 'Gebruik een landcode van twee letters, bijvoorbeeld NL.',
            'recurring_consent.accepted' => 'Bevestig de maandelijkse betaling om verder te gaan.',
        ];
    }
}
