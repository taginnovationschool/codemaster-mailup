<?php

namespace Codemaster\MailUp\HttpClient\Authentication;

use Codemaster\MailUp\HttpClient\HttpClientRequest;

/**
 * Interface to handle the authentication in HTTP request.
 */
interface HttpClientAuthenticationInterface
{
    /**
     * Used by the HttpClient to authenticate requests.
     *
     * @param HttpClientRequest $request
     */
    public function authenticate(HttpClientRequest $request);
}
