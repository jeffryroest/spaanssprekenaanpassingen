<?php

namespace App\Http\Requests\ContentStudio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ArchiveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('contentNode'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:480'],
        ];
    }
}
