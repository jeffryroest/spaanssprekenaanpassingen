<?php

namespace App\Http\Requests\ContentStudio;

use App\Enums\ContentReviewAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DecideContentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->input('action')) {
            ContentReviewAction::Approved->value => Gate::allows('content-studio.approve'),
            ContentReviewAction::ChangesRequested->value => Gate::allows('content-studio.review'),
            default => false,
        };
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'action' => ['required', Rule::in([
                ContentReviewAction::Approved->value,
                ContentReviewAction::ChangesRequested->value,
            ])],
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
