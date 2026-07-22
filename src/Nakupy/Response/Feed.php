<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Response;

/**
 * A product feed (GET /nakupy/feeds/).
 */
readonly class Feed
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $feedUrl,
        public ?string $lastSuccessfulImport,
        public ?int $maxFeedDownloadsPerDay,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            feedUrl: (string) ($data['feedUrl'] ?? ''),
            lastSuccessfulImport: isset($data['lastSuccessfulImport']) ? (string) $data['lastSuccessfulImport'] : null,
            maxFeedDownloadsPerDay: isset($data['maxFeedDownloadsPerDay']) ? (int) $data['maxFeedDownloadsPerDay'] : null,
            raw: $data,
        );
    }
}
