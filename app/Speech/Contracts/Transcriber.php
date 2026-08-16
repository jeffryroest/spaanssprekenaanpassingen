<?php

namespace App\Speech\Contracts;

use App\Speech\TranscriptionResult;
use Illuminate\Http\UploadedFile;

interface Transcriber
{
    public function transcribe(UploadedFile $audio): TranscriptionResult;
}
