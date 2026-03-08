<?php

declare(strict_types=1);

namespace AtrId\Auth;

final class Config
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $authorizeUrl;
    private string $tokenUrl;
    private string $userinfoUrl;
    private string $scope;
    private int $timeout;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->clientId = trim((string)($data['client_id'] ?? ''));
        $this->clientSecret = trim((string)($data['client_secret'] ?? ''));
        $this->redirectUri = trim((string)($data['redirect_uri'] ?? ''));
        $this->authorizeUrl = trim((string)($data['authorize_url'] ?? 'https://id.atrcore.live/authorize.php'));
        $this->tokenUrl = trim((string)($data['token_url'] ?? 'https://id.atrcore.live/api_oauth_token.php'));
        $this->userinfoUrl = trim((string)($data['userinfo_url'] ?? 'https://id.atrcore.live/api_oauth_userinfo.php'));
        $this->scope = trim((string)($data['scope'] ?? 'openid email profile'));
        $this->timeout = (int)($data['timeout'] ?? 20);

        if ($this->clientId === '' || $this->clientSecret === '' || $this->redirectUri === '') {
            throw new AtrIdException('client_id, client_secret and redirect_uri are required');
        }
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }

    public function authorizeUrl(): string
    {
        return $this->authorizeUrl;
    }

    public function tokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function userinfoUrl(): string
    {
        return $this->userinfoUrl;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function timeout(): int
    {
        return max(1, $this->timeout);
    }
}
