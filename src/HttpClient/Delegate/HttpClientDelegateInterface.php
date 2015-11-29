<?php

namespace Codemaster\MailUp\HttpClient\Delegate;

use Codemaster\MailUp\HttpClient\HttpClient;
use Codemaster\MailUp\HttpClient\HttpClientRequest;

/**
 * Interface to handle the HTTP request.
 */
interface HttpClientDelegateInterface
{
    /**
     * Executes a request for the HttpClient.
     *
     * @param HttpClient        $client
     *  The client we're acting as a delegate for.
     * @param HttpClientRequest $request
     *  The request to execute.
     *
     * @return object
     *  The interpreted response.
     */
    public function execute(HttpClient $client, HttpClientRequest $request);
}
