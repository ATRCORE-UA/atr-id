<?php

declare(strict_types=1);

namespace AtrId\Auth;

final class CurlHttpClient implements HttpClientInterface
{
    private int $timeout;

    public function __construct(int $timeout = 20)
    {
        if (!extension_loaded('curl')) {
            throw new HttpException('cURL extension is required');
        }
        $this->timeout = max(1, $timeout);
    }

    public function get(string $url, array $headers = []): HttpResponse
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function postForm(string $url, array $body, array $headers = []): HttpResponse
    {
        return $this->request('POST', $url, http_build_query($body), $headers + [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    /**
     * @param array<string, string> $headers
     */
    private function request(string $method, string $url, ?string $payload, array $headers): HttpResponse
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new HttpException('Failed to initialize cURL');
        }

        $headerList = [];
        foreach ($headers as $key => $value) {
            $headerList[] = $key . ': ' . $value;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerList,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $payload ?? '';
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new HttpException('HTTP request failed: ' . $err);
        }

        return new HttpResponse($status, (string)$raw);
    }
}
