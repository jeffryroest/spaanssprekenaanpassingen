<?php

namespace App\Http\Requests\Game;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class TranscribeSpeechRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Closure|string>>
     */
    public function rules(): array
    {
        return [
            'audio' => [
                'bail',
                'required',
                'file',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || strtolower($value->getClientOriginalExtension()) !== 'webm') {
                        $fail('De opname moet een WebM-bestand zijn.');

                        return;
                    }

                    $path = $value->getRealPath();
                    $stream = $path === false ? false : fopen($path, 'rb');
                    $signature = $stream === false ? false : fread($stream, 4);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    if ($signature !== "\x1A\x45\xDF\xA3") {
                        $fail('De opname bevat geen geldige WebM-container.');
                    }
                },
            ],
            'duration_seconds' => ['bail', 'required', 'numeric', 'min:0.2', 'max:12.5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.required' => 'Neem eerst een zin op.',
            'audio.file' => 'De opname kon niet als bestand worden gelezen.',
            'audio.max' => 'De opname is te groot. Neem een kortere zin op.',
            'duration_seconds.required' => 'De opnameduur ontbreekt.',
            'duration_seconds.max' => 'Een spreekpoging mag maximaal 12 seconden duren.',
        ];
    }
}
