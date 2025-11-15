<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $accountId = $this->route('account');
        
        return [
            'provider' => ['required', 'string', 'in:brevo'],
            'email' => [
                'required', 
                'email', 
                Rule::unique('email_accounts', 'email')->ignore($accountId)
            ],
            'password' => ['nullable', 'string', 'min:32'],
            'access_token' => ['nullable', 'string'],
            'refresh_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Please select an email provider.',
            'provider.in' => 'Only Brevo is supported at this time.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Brevo SMTP key must be at least 32 characters.',
        ];
    }
}
