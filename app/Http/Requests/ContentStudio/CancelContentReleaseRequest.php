<?php

namespace App\Http\Requests\ContentStudio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CancelContentReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('content-studio.publish');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
