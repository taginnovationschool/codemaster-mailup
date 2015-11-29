<?php

namespace Codemaster\MailUp\HttpClient\Authentication;

use Codemaster\MailUp\HttpClient\Authentication\HttpClientAuthenticationInterface;
use Codemaster\MailUp\HttpClient\HttpClientRequest;

/**
 * Authentication class for HTTP Client
 */
class HttpClientMailUpOAuth2 implements HttpClientAuthenticationInterface
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * Create a mailup OAuth2 auth implementation.
     *
     * @param string $accessToken
     */
    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(HttpClientRequest $request)
    {
        $request->setHeader('Authorization', 'Bearer '.$this->accessToken);
    }
}
