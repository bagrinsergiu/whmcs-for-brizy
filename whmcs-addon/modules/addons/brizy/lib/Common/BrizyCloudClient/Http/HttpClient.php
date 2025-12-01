<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http;

use \GuzzleHttp\Client as GuzzleClient;
use \GuzzleHttp\Exception\GuzzleException;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception\ApiException;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception\UnauthorizedException;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception\NotFoundException;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception\RateLimitException;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception\ServerException;

class HttpClient
{
    private GuzzleClient $client;

    public function __construct(string $secretKey, string $baseUri)
    {
        $this->client = new GuzzleClient([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'headers'  => [
                'x-auth-user-token' => $secretKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'timeout'  => 60 * 5,
            'http_errors' => false
        ]);
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        try {
            $resp = $this->client->request($method, $uri, $options);
            $code = $resp->getStatusCode();
            $body = (string) $resp->getBody();
            $json = json_decode($body, true);

            if ($code >= 400) {
                $this->handleError($code, $json['message'] ?? $body);
            }

            return $json ?? [];
        } catch (GuzzleException $e) {
            throw ApiException::fromGuzzle($e);
        }
    }

    public function requestRaw(string $method, string $uri, array $options = []): string
    {
        try {
            $resp = $this->client->request($method, $uri, $options);
            $code = $resp->getStatusCode();
            $body = (string) $resp->getBody();

            if ($code >= 400) {
                $this->handleError($code, $json['message'] ?? $body);
            }

            return $body;
        } catch (GuzzleException $e) {
            throw ApiException::fromGuzzle($e);
        }
    }

    public function requestAsync(string $method, string $uri, array $options = []): string
    {
        try {
            $resp = $this->client->requestAsync($method, $uri, $options);

            return true;
        } catch (GuzzleException $e) {
            throw ApiException::fromGuzzle($e);
        }
    }

    private function handleError(int $status, string $msg): void
    {
        if (in_array($status, [401, 403])) {
            throw new UnauthorizedException($msg, $status);
        }

        if ($status === 404) {
            throw new NotFoundException($msg, $status);
        }

        if ($status === 429) {
            throw new RateLimitException($msg, $status);
        }

        if ($status >= 500) {
            throw new ServerException($msg, $status);
        }

        throw new ApiException($msg, $status);
    }
}
