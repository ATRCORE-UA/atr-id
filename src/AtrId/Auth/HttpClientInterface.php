<?php

declare(strict_types=1);

namespace AtrId\Auth;

interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers = []): HttpResponse;

    /**
     * @param array<string, string> $headers
     * @param array<string, scalar|null> $body
     */
    public function postForm(string $url, array $body, array $headers = []): HttpResponse;
}
