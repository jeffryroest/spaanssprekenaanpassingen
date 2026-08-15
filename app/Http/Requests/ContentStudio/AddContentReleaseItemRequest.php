<?php

namespace App\Http\Requests\ContentStudio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AddContentReleaseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('content-studio.publish');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content_node_id' => ['required', 'integer', 'exists:content_nodes,id'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
