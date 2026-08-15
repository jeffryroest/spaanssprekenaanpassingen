<?php

namespace App\ContentApi;

use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PublicApiResponder
{
    public const API_VERSION = '1.0.0';

    /**
     * @param  array<string, mixed>                $payload
     * @param  array<string, string|list<string>>  $additionalHeaders
     */
    public function respond(
        Request $request,
        array $payload,
        int $status = 200,
        ?DateTimeInterface $lastModified = null,
        array $additionalHeaders = [],
    ): JsonResponse|Response {
        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
        $etag = '"'.hash('sha256', $encoded).'"';
        $headers = [
            'Cache-Control' => $status === 200
                ? 'public, max-age=60, stale-while-revalidate=300'
                : 'no-store',
            'ETag' => $etag,
            'Vary' => 'Accept, Accept-Encoding',
            'X-Content-API-Version' => self::API_VERSION,
        ];

        if ($lastModified !== null) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $lastModified->getTimestamp()).' GMT';
        }

        $headers = array_merge($headers, $additionalHeaders);

        if ($status === 200 && $this->etagMatches($request, $etag)) {
            return response('', 304, $headers);
        }

        return response()->json(
            data: $payload,
            status: $status,
            headers: $headers,
            options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    private function etagMatches(Request $request, string $etag): bool
    {
        $candidates = array_map(
            static fn (string $candidate): string => trim($candidate),
            explode(',', (string) $request->header('If-None-Match')),
        );

        return in_array('*', $candidates, true)
            || in_array($etag, $candidates, true)
            || in_array("W/{$etag}", $candidates, true);
    }
}
