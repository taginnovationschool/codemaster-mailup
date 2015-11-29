<?php

namespace Codemaster\MailUp\HttpClient\Delegate;

use Codemaster\MailUp\HttpClient\HttpClient;
use Codemaster\MailUp\HttpClient\HttpClientRequest;
use Codemaster\MailUp\HttpClient\Delegate\HttpClientDelegateInterface;
use Codemaster\MailUp\HttpClient\Exception\HttpClientException;

/**
 * Abstract base class for Http client delegates.
 */
abstract class AbstractHttpClientDelegate implements HttpClientDelegateInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function execute(HttpClient $client, HttpClientRequest $request);

    /**
     * This function interprets a raw http response.
     *
     * @param HttpClient $client
     * @param string $response
     * @return object
     *  The interpreted response.
     */
    protected function interpretResponse(HttpClient $client, $response)
    {
        $client->rawResponse = $response;
        if (preg_match('/\nProxy-agent: .*\r?\n\r?\nHTTP/', $response)) {
            $split = preg_split('/\r?\n\r?\n/', $response, 3);
            if (!isset($split[2])) {
                throw new HttpClientException(
                    'Error interpreting response',
                    0,
                    (object) array('rawResponse' => $response)
                );
            }
            $headers = $split[1];
            $body = $split[2];
        } else {
            $split = preg_split('/\r?\n\r?\n/', $response, 2);
            if (!isset($split[1])) {
                throw new HttpClientException(
                    'Error interpreting response',
                    0,
                    (object) array('rawResponse' => $response)
                );
            }
            $headers = $split[0];
            $body = $split[1];
        }

        $obj = (object) array(
            'headers' => $headers,
            'body' => $body,
        );

        $matches = array();
        if (preg_match('/HTTP\/1.\d (\d{3}) (.*)/', $headers, $matches)) {
            $obj->responseCode = intval(trim($matches[1]), 10);
            $obj->responseMessage = trim($matches[2]);

            // Handle HTTP/1.1 100 Continue
            if ($obj->responseCode == 100) {
                return $this->interpretResponse($client, $body);
            }
        }

        return $obj;
    }
}
