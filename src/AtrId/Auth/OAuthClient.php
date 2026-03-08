<?php

declare(strict_types=1);

namespace AtrId\Auth;

final class OAuthClient
{
    private Config $config;
    private HttpClientInterface $http;

    public function __construct(Config $config, ?HttpClientInterface $httpClient = null)
    {
        $this->config = $config;
        $this->http = $httpClient ?? new CurlHttpClient($config->timeout());
    }

    /**
     * @param array<string, scalar> $extraParams
     */
    public function buildAuthorizeUrl(string $state, array $extraParams = []): string
    {
        if ($state === '') {
            throw new AtrIdException('state is required');
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->config->clientId(),
            'redirect_uri' => $this->config->redirectUri(),
            'scope' => $this->config->scope(),
            'state' => $state,
        ];

        foreach ($extraParams as $key => $value) {
            $params[(string)$key] = (string)$value;
        }

        return $this->appendQuery($this->config->authorizeUrl(), $params);
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array{code:string,state:string,error:string}
     */
    public function parseCallback(array $queryParams): array
    {
        $error = isset($queryParams['error']) ? trim((string)$queryParams['error']) : '';
        $code = isset($queryParams['code']) ? trim((string)$queryParams['code']) : '';
        $state = isset($queryParams['state']) ? trim((string)$queryParams['state']) : '';

        // Provider compatibility: ?atr_id_callback=1?code=...&state=...
        if ($code === '' && isset($queryParams['atr_id_callback'])) {
            $raw = (string)$queryParams['atr_id_callback'];
            $qPos = strpos($raw, '?');
            if ($qPos !== false) {
                $embedded = [];
                parse_str(substr($raw, $qPos + 1), $embedded);
                if ($code === '' && !empty($embedded['code'])) {
                    $code = trim((string)$embedded['code']);
                }
                if ($state === '' && !empty($embedded['state'])) {
                    $state = trim((string)$embedded['state']);
                }
                if ($error === '' && !empty($embedded['error'])) {
                    $error = trim((string)$embedded['error']);
                }
            }
        }

        return [
            'code' => $code,
            'state' => $state,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCodeForToken(string $code): array
    {
        if ($code === '') {
            throw new AtrIdException('Authorization code is required');
        }

        $response = $this->http->postForm($this->config->tokenUrl(), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config->clientId(),
            'client_secret' => $this->config->clientSecret(),
            'redirect_uri' => $this->config->redirectUri(),
        ]);

        $json = $response->json();
        if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
            $error = (string)($json['error'] ?? 'token_exchange_failed');
            throw new OAuthException('Token endpoint returned HTTP ' . $response->statusCode() . ': ' . $error);
        }

        if (empty($json['access_token'])) {
            throw new OAuthException('Token endpoint did not return access_token');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $accessToken): array
    {
        if ($accessToken === '') {
            throw new AtrIdException('accessToken is required');
        }

        $response = $this->http->get($this->config->userinfoUrl(), [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        $json = $response->json();
        if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
            $error = (string)($json['error'] ?? 'userinfo_failed');
            throw new OAuthException('UserInfo endpoint returned HTTP ' . $response->statusCode() . ': ' . $error);
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $tokenPayload
     * @return array<string, mixed>|null
     */
    public function extractUserFromTokenPayload(array $tokenPayload): ?array
    {
        if (!empty($tokenPayload['email'])) {
            return $tokenPayload;
        }
        if (!empty($tokenPayload['user']) && is_array($tokenPayload['user'])) {
            return $tokenPayload['user'];
        }
        if (!empty($tokenPayload['profile']) && is_array($tokenPayload['profile'])) {
            return $tokenPayload['profile'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $tokenPayload
     * @return array<string, mixed>
     */
    public function resolveUserProfile(array $tokenPayload): array
    {
        $fromToken = $this->extractUserFromTokenPayload($tokenPayload);
        if (is_array($fromToken) && !empty($fromToken['email'])) {
            return $fromToken;
        }

        $accessToken = (string)($tokenPayload['access_token'] ?? '');
        if ($accessToken === '') {
            throw new OAuthException('Cannot resolve profile: access_token is missing');
        }

        $profile = $this->fetchUserInfo($accessToken);
        if (empty($profile['email'])) {
            throw new OAuthException('Cannot resolve profile: userinfo did not return email');
        }

        return $profile;
    }

    /**
     * @param array<string, scalar> $params
     */
    private function appendQuery(string $url, array $params): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $existing = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $query = http_build_query(array_merge($existing, $params));
        $out = '';

        if (!empty($parts['scheme'])) {
            $out .= $parts['scheme'] . '://';
        }
        if (!empty($parts['user'])) {
            $out .= $parts['user'];
            if (!empty($parts['pass'])) {
                $out .= ':' . $parts['pass'];
            }
            $out .= '@';
        }
        if (!empty($parts['host'])) {
            $out .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $out .= ':' . (int)$parts['port'];
        }
        $out .= $parts['path'] ?? '';
        if ($query !== '') {
            $out .= '?' . $query;
        }
        if (!empty($parts['fragment'])) {
            $out .= '#' . $parts['fragment'];
        }

        return $out;
    }
}
