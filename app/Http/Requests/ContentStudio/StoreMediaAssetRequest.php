<?php

namespace App\Http\Requests\ContentStudio;

use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('content-studio.edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'mp3', 'ogg', 'webm', 'wav'])->max('20mb')],
            'kind' => ['required', Rule::enum(MediaKind::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'alt_text' => ['nullable', 'string', 'max:1000'],
            'transcript' => ['nullable', 'string', 'max:20000'],
            'rights_status' => ['required', Rule::enum(MediaRightsStatus::class)],
            'source_name' => ['nullable', 'string', 'max:500'],
            'creator_name' => ['nullable', 'string', 'max:255'],
            'license_name' => ['nullable', 'string', 'max:255'],
            'rights_expires_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
