<?php

namespace App\Actions\ContentStudio;

use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use App\Models\AuditLog;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateMediaAsset
{
    public function handle(
        User $actor,
        UploadedFile $file,
        MediaKind $kind,
        string $title,
        ?string $description,
        ?string $altText,
        ?string $transcript,
        MediaRightsStatus $rightsStatus,
        ?string $sourceName,
        ?string $creatorName,
        ?string $licenseName,
        ?string $rightsExpiresAt,
    ): MediaAsset {
        Gate::forUser($actor)->authorize('content-studio.edit');

        $mimeType = strtolower((string) $file->getMimeType());
        $detectedKind = match (true) {
            in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true) => MediaKind::Image,
            in_array($mimeType, ['audio/mpeg', 'audio/ogg', 'application/ogg', 'audio/webm', 'audio/wav', 'audio/x-wav', 'audio/wave'], true) => MediaKind::Audio,
            default => null,
        };
        if ($detectedKind === null || $detectedKind !== $kind) {
            throw ValidationException::withMessages([
                'file' => 'Het werkelijke bestandstype wordt niet ondersteund of komt niet overeen met het gekozen mediatype.',
            ]);
        }

        if ($kind === MediaKind::Image && blank($altText)) {
            throw ValidationException::withMessages(['alt_text' => 'Een afbeelding vereist alt-tekst.']);
        }
        if ($kind === MediaKind::Audio && blank($transcript)) {
            throw ValidationException::withMessages(['transcript' => 'Een audiobestand vereist een transcript.']);
        }
        if ($rightsStatus === MediaRightsStatus::Licensed && (blank($sourceName) || blank($licenseName))) {
            throw ValidationException::withMessages([
                'license_name' => 'Gelicentieerde media vereist een bron en licentienaam.',
            ]);
        }

        $disk = (string) config('content-studio.media_disk', 'local');
        $checksum = hash_file('sha256', $file->getRealPath());
        $uuid = Str::uuid()->toString();
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $objectKey = "content-media/{$uuid}/original.{$extension}";
        $stored = $file->storeAs("content-media/{$uuid}", "original.{$extension}", $disk);
        if ($stored === false) {
            throw ValidationException::withMessages(['file' => 'Het mediabestand kon niet veilig worden opgeslagen.']);
        }

        [$width, $height] = $this->imageDimensions($file, $kind);

        try {
            return DB::transaction(function () use (
                $actor,
                $file,
                $kind,
                $disk,
                $objectKey,
                $uuid,
                $mimeType,
                $title,
                $description,
                $altText,
                $transcript,
                $rightsStatus,
                $sourceName,
                $creatorName,
                $licenseName,
                $rightsExpiresAt,
                $width,
                $height,
                $checksum,
            ): MediaAsset {
                $asset = MediaAsset::query()->create([
                    'uuid' => $uuid,
                    'kind' => $kind,
                    'disk' => $disk,
                    'object_key' => $objectKey,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'byte_size' => $file->getSize(),
                    'width' => $width,
                    'height' => $height,
                    'checksum_sha256' => $checksum,
                    'title' => $title,
                    'description' => $description,
                    'alt_text' => $kind === MediaKind::Image ? $altText : null,
                    'transcript' => $kind === MediaKind::Audio ? $transcript : null,
                    'source_name' => $sourceName,
                    'creator_name' => $creatorName,
                    'license_name' => $licenseName,
                    'rights_status' => $rightsStatus,
                    'rights_expires_at' => $rightsExpiresAt,
                    'created_by' => $actor->getKey(),
                ]);

                AuditLog::recordMediaChange($actor, 'media.created', $asset, [
                    'uuid' => $asset->uuid,
                    'kind' => $asset->kind->value,
                    'mime_type' => $asset->mime_type,
                    'byte_size' => $asset->byte_size,
                    'rights_status' => $asset->rights_status->value,
                ]);

                return $asset;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($objectKey);

            throw $exception;
        }
    }

    /** @return array{int|null, int|null} */
    private function imageDimensions(UploadedFile $file, MediaKind $kind): array
    {
        if ($kind !== MediaKind::Image) {
            return [null, null];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return is_array($dimensions) ? [(int) $dimensions[0], (int) $dimensions[1]] : [null, null];
    }
}
