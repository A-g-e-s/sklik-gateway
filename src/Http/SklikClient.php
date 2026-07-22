<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Http;

use Ages\SklikGateway\Auth\AccessToken;
use Ages\SklikGateway\Config\SklikConfig;
use Ages\SklikGateway\Exception\SklikApiException;
use Ages\SklikGateway\Exception\SklikException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;

/**
 * Low-level HTTP client for the Fénix API.
 *
 * Handles authentication (refresh token -> access token), token caching and
 * rate-limit retries. All resource-specific API classes go through this client.
 */
class SklikClient
{
    private Client $httpClient;

    private ?AccessToken $token = null;

    public function __construct(
        private readonly SklikConfig $config,
        /** Optional PSR-16 cache to keep the access token across requests. */
        private readonly ?CacheInterface $cache = null,
        ?Client $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => rtrim($config->apiUrl, '/') . '/',
            'timeout' => $config->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $this->normalizeQuery($query)]);
    }

    /**
     * @param array<string, mixed> $json
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function post(string $path, array $json = [], array $query = []): array
    {
        return $this->request('POST', $path, [
            'json' => $json,
            'query' => $this->normalizeQuery($query),
        ]);
    }

    /**
     * @param array<string, mixed> $json
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function patch(string $path, array $json = [], array $query = []): array
    {
        return $this->request('PATCH', $path, [
            'json' => $json,
            'query' => $this->normalizeQuery($query),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options): array
    {
        $options['headers'] = ($options['headers'] ?? []) + [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Accept' => 'application/json',
        ];

        $response = $this->send($method, ltrim($path, '/'), $options);
        $status = $response['status'];
        $body = $response['body'];

        if ($status < 200 || $status >= 300) {
            throw SklikApiException::fromResponse($status, $body);
        }

        if ($body === '') {
            return [];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new SklikException('Invalid JSON response from Sklik API.');
        }

        return $data;
    }

    /**
     * Sends a request with rate-limit (429) retries.
     *
     * @param array<string, mixed> $options
     * @return array{status: int, body: string}
     */
    private function send(string $method, string $path, array $options): array
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->httpClient->request($method, $path, $options);
            } catch (GuzzleException $e) {
                throw new SklikException('Sklik API request failed: ' . $e->getMessage(), 0, $e);
            }

            $status = $response->getStatusCode();

            if ($status === 429 && $attempt < $this->config->rateLimitRetries) {
                $attempt++;
                $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: 1);
                sleep(max(1, $retryAfter));
                continue;
            }

            return ['status' => $status, 'body' => (string) $response->getBody()];
        }
    }

    private function getAccessToken(): string
    {
        if ($this->token !== null && !$this->token->isExpired()) {
            return $this->token->token;
        }

        $cached = $this->loadTokenFromCache();
        if ($cached !== null && !$cached->isExpired()) {
            $this->token = $cached;
            return $cached->token;
        }

        $token = $this->fetchToken();
        $this->token = $token;
        $this->storeTokenInCache($token);

        return $token->token;
    }

    private function fetchToken(): AccessToken
    {
        $form = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->config->refreshToken,
        ];
        if ($this->config->userId !== null) {
            $form['user_id'] = $this->config->userId;
        }

        try {
            $response = $this->httpClient->post('user/token', ['form_params' => $form]);
        } catch (GuzzleException $e) {
            throw new SklikException('Sklik token request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw SklikApiException::fromResponse($status, $body);
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new SklikException('Invalid token response from Sklik API.');
        }

        return AccessToken::fromArray($data);
    }

    private function loadTokenFromCache(): ?AccessToken
    {
        if ($this->cache === null) {
            return null;
        }

        $value = $this->cache->get($this->cacheKey());

        return $value instanceof AccessToken ? $value : null;
    }

    private function storeTokenInCache(AccessToken $token): void
    {
        if ($this->cache === null) {
            return;
        }

        $ttl = $token->expiresAt->getTimestamp() - time() - 60;
        if ($ttl > 0) {
            $this->cache->set($this->cacheKey(), $token, $ttl);
        }
    }

    private function cacheKey(): string
    {
        return 'sklik.access_token.' . substr(hash('sha256', $this->config->refreshToken), 0, 16)
            . '.' . ($this->config->userId ?? 'self');
    }

    /**
     * Builds a query string, dropping nulls and normalizing booleans. Array
     * values are serialized as repeated keys (`id=1&id=2`) as expected by the
     * Fénix API — not the PHP-style `id[]=1` that Guzzle would produce by default.
     *
     * @param array<string, mixed> $query
     */
    private function normalizeQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode($key) . '=' . rawurlencode($this->scalarToString($item));
                }
                continue;
            }
            $parts[] = rawurlencode($key) . '=' . rawurlencode($this->scalarToString($value));
        }

        return implode('&', $parts);
    }

    private function scalarToString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
