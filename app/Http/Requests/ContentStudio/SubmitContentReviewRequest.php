<?php

namespace App\Http\Requests\ContentStudio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitContentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('contentNode'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
