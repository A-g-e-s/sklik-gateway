<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Config;

/**
 * Configuration for the Seznam RS-API (Fénix) client.
 *
 * The refresh token (API key) is generated in your Sklik.cz account in the
 * "Nástroje / API" section. It is exchanged for a short-lived access token
 * (valid ~1 hour) which is then sent as a Bearer token with every request.
 */
readonly class SklikConfig
{
    public function __construct(
        /** Refresh token (API key) generated in Sklik.cz. */
        public string $refreshToken,
        /**
         * Foreign account user ID. When set, the access token is issued for that
         * account instead of the one that owns the refresh token. You must have
         * been granted access to it beforehand.
         */
        public ?int $userId = null,
        /**
         * Default Seznam Nákupy premise (shop) ID used by /nakupy/* calls when
         * none is passed explicitly. Handy for single-shop projects.
         */
        public ?int $premiseId = null,
        /** Base API URL, without trailing slash. */
        public string $apiUrl = 'https://api.sklik.cz/v1',
        /** Request timeout in seconds. */
        public int $timeout = 30,
        /**
         * How many times to retry a request after a 429 (Too Many Requests),
         * honouring the Retry-After header. The global limit is 5 req/s.
         */
        public int $rateLimitRetries = 3,
        /**
         * Minimum spacing between two requests in milliseconds. Enforced proactively
         * so batch processing stays under the 5 req/s global limit (200 ms = 5 req/s)
         * instead of relying on reactive 429 retries. Set to 0 to disable.
         */
        public int $minRequestIntervalMs = 200,
    ) {
    }
}
