<?php

namespace Codemaster\MailUp\HttpClient\Delegate;

use Codemaster\MailUp\HttpClient\HttpClient;
use Codemaster\MailUp\HttpClient\HttpClientRequest;
use Codemaster\MailUp\HttpClient\Delegate\AbstractHttpClientDelegate;
use Codemaster\MailUp\HttpClient\Exception\HttpClientException;

/**
 * A delegate for the HttpClient that uses curl to fetch data.
 */
class HttpClientCurlDelegate extends AbstractHttpClientDelegate
{
    /**
     * {@inheritDoc}
     */
    public function execute(HttpClient $client, HttpClientRequest $request)
    {
        $curlopts = array();
        if (isset($client->options['curlopts'])) {
            $curlopts = $curlopts + $client->options['curlopts'];
        }
        if (isset($request->options['curlopts'])) {
            $curlopts = $request->options['curlopts'] + $curlopts;
        }

        $ch = $this->curl($request, $curlopts);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new HttpClientException('Curl Error: '.$error);
        }

        return $this->interpretResponse($client, $response);
    }

    /**
     * Gets a curl handle for the given request.
     */
    private function curl(HttpClientRequest $request, $curlopts)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, 'Codemaster (+http://codemaster/)');
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $request->url());

        // Only add Content-Length if we actually have any content or if it is a POST
        // or PUT request. Some non-standard servers get confused by Content-Length in
        // at least HEAD/GET requests, and Squid always requires Content-Length in
        // POST/PUT requests.
        $contentLength = strlen($request->data);
        if ($contentLength > 0 || $request->method == 'POST' || $request->method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request->getHeaders());

        curl_setopt_array($ch, $curlopts);

        return $ch;
    }
}
