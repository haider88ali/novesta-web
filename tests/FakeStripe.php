<?php

declare(strict_types=1);

namespace Tests;

use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP client so billing tests never call the real API.
 * Responses are matched by "METHOD /v1/path" prefix; every request is recorded.
 */
final class FakeStripe implements ClientInterface
{
    /** @var list<array{method:string, path:string, params:array<string, mixed>}> */
    public array $requests = [];

    /** @param array<string, array<string, mixed>> $responses */
    public function __construct(private array $responses) {}

    /** @param array<string, array<string, mixed>> $responses */
    public static function fake(array $responses): self
    {
        ApiRequestor::setHttpClient($fake = new self($responses));

        return $fake;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1')
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => mb_strtoupper($method), 'path' => $path, 'params' => $params];

        foreach ($this->responses as $route => $body) {
            if (str_starts_with(mb_strtoupper($method).' '.$path, $route)) {
                return [json_encode($body), 200, []];
            }
        }

        return [json_encode(['error' => ['message' => "No fake for {$method} {$path}"]]), 404, []];
    }

    public function sent(string $method, string $path): ?array
    {
        return collect($this->requests)->first(fn (array $r): bool => $r['method'] === $method && $r['path'] === $path);
    }
}
