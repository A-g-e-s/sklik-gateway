<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Exception;

use Throwable;

/**
 * Thrown when the API returns a non-successful HTTP status.
 */
class SklikApiException extends SklikException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(int $statusCode, string $body): self
    {
        $message = "Sklik API returned HTTP {$statusCode}";

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $detail = $decoded['detail'] ?? $decoded['message'] ?? $decoded['error'] ?? null;
            if (is_string($detail) && $detail !== '') {
                $message .= ": {$detail}";
            } elseif (is_array($detail)) {
                $message .= ': ' . json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return new self($message, $statusCode, $body);
    }
}
