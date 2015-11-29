<?php

namespace Codemaster\MailUp\HttpClient\Authentication;

use Codemaster\MailUp\HttpClient\Authentication\HttpClientAuthenticationInterface;
use Codemaster\MailUp\HttpClient\HttpClientRequest;

/**
 * Authentication class for HTTP Client
 */
class HttpClientMailUpBasicAuth implements HttpClientAuthenticationInterface
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * Create a mailup basic auth implementation.
     *
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(HttpClientRequest $request)
    {
        $request->setHeader('Authorization', 'Basic '.base64_encode($this->clientId.":".$this->clientSecret));
    }
}
