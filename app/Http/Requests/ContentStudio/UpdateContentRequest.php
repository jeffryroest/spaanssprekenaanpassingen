<?php

namespace App\Http\Requests\ContentStudio;

use App\Models\ContentNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('contentNode'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->route('contentNode');

        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('content_nodes', 'slug')
                    ->where(fn ($query) => $query->where('content_type', $contentNode->content_type->value))
                    ->ignore($contentNode->getKey()),
            ],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
        ];
    }
}
