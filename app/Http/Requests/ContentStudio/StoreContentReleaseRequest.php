<?php

namespace App\Http\Requests\ContentStudio;

use App\Enums\ContentReleaseChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreContentReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('content-studio.publish');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_channel' => ['required', Rule::enum(ContentReleaseChannel::class)],
            'desired_publish_at' => ['nullable', 'date'],
        ];
    }
}
