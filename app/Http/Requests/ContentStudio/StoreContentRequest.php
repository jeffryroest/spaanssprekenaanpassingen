<?php

namespace App\Http\Requests\ContentStudio;

use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Rules\PlayableDomainData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ContentNode::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $contentType = ContentType::tryFrom((string) $this->input('content_type'));

        return [
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'slug' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('content_nodes', 'slug')->where(
                    fn ($query) => $query->where('content_type', $this->input('content_type')),
                ),
            ],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'domain_data' => ['nullable', 'string', new PlayableDomainData($contentType)],
            'media' => ['nullable', 'array'],
            'media.*' => ['nullable', 'integer', 'exists:media_assets,id'],
        ];
    }
}
