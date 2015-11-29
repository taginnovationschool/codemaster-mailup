<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\Authentication\HttpClientAuthenticationInterface;
use Codemaster\MailUp\HttpClient\Delegate\HttpClientCurlDelegate;
use Codemaster\MailUp\HttpClient\Exception\HttpClientException;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;
use \Exception;

/**
 * A http client.
 */
class HttpClient
{
    /**
     * @var HttpClientAuthenticationInterface
     */
    protected $authentication = null;

    /**
     * @var HttpClientFormatterInterface
     */
    protected $formatter = null;

    /**
     * @var HttpClientDelegateInterface
     */
    protected $delegate = null;

    /**
     * Allows specification of additional custom options.
     */
    public $options = array();

    public $rawResponse;
    public $lastResponse;

    /**
     * Creates a Http client.
     *
     * @param HttpClientAuthenticationInterface $authentication
     *  Optional. Authentication to use for the request.
     * @param HttpClientFormatterInterface      $formatter
     *  Optional. Formatter to use for request and response bodies.
     * @param HttpClientDelegateInterface       $delegate
     *  Optional. The delegate that executes the call for the HttpClient.
     *  Defaults to a HttpClientCurlDelegate if curl is available.
     */
    public function __construct(
        HttpClientAuthenticationInterface $authentication = null,
        HttpClientFormatterInterface $formatter = null,
        HttpClientDelegateInterface $delegate = null
    ) {
        $this->authentication = $authentication;
        $this->formatter = $formatter;

        if (!$formatter || in_array(HttpClientFormatterInterface::class, class_implements($formatter))) {
            $this->formatter = $formatter;
        } else {
            throw new Exception('The formatter parameter must either be a object '.'implementing HttpClientFormatterInterface, or evaluate to FALSE.');
        }

        if (!$delegate && function_exists('curl_init')) {
            $delegate = new HttpClientCurlDelegate();
        }
        if (!$delegate) {
            throw new Exception('The HttpClient cannot execute requests without a delegate. '.'This probably means that you don\'t have curl installed on your system.');
        }
        $this->delegate = $delegate;
    }

    /**
     * Executes a GET request.
     *
     * @param string $url
     * @param array  $parameters
     *
     * @return mixed  Response
     */
    public function get($url, array $parameters = array())
    {
        return $this->execute(new HttpClientRequest($url, array(
            'method' => 'GET',
            'parameters' => $parameters,
        )));
    }

    /**
     * Executes a POST request.
     *
     * @param  string $url
     * @param  mixed  $data
     * @param  array  $parameters
     *
     * @return mixed  Response
     */
    public function post($url, $data = null, array $parameters = array())
    {
        return $this->execute(new HttpClientRequest($url, array(
            'method' => 'POST',
            'parameters' => $parameters,
            'data' => $data,
        )));
    }

    /**
     * Executes a PUT request.
     *
     * @param  string $url
     * @param  mixed  $data
     * @param  array  $parameters
     *
     * @return mixed  Response
     */
    public function put($url, $data = null, array $parameters = array())
    {
        return $this->execute(new HttpClientRequest($url, array(
            'method' => 'PUT',
            'parameters' => $parameters,
            'data' => $data,
        )));
    }

    /**
     * Executes a DELETE request.
     *
     * @param  string $url
     * @param  array  $parameters
     *
     * @return mixed  Response
     */
    public function delete($url, $parameters = array())
    {
        return $this->execute(new HttpClientRequest($url, array(
            'method' => 'DELETE',
            'parameters' => $parameters,
        )));
    }

    /**
     * Executes the given request.
     *
     * @param  HttpClientRequest $request
     *
     * @return mixed             The response
     */
    public function execute(HttpClientRequest $request)
    {
        if (isset($request->data)) {
            if ($this->formatter) {
                $request->setHeader('Content-type', $this->formatter->contentType());
                $request->data = $this->formatter->serialize($request->data);
            } else {
                $request->data = (string) $request->data;
            }
            if (is_string($request->data)) {
                $request->setHeader('Content-length', strlen($request->data));
            }
        }
        if ($this->formatter) {
            $request->setHeader('Accept', $this->formatter->accepts());
        }

        // Allow the authentication implementation to do it's magic
        if ($this->authentication) {
            $this->authentication->authenticate($request);
        }

        $response = $this->delegate->execute($this, $request);
        $this->lastResponse = $response;

        $result = null;

        if ($response->responseCode >= 200 && $response->responseCode <= 299) {
            if ($this->formatter) {
                try {
                    $result = $this->formatter->unserialize($response->body);
                } catch (Exception $e) {
                    throw new HttpClientException('Failed to unserialize response', 0, $response, $e);
                }
            } else {
                $result = $response->body;
            }
        } // Treat all remaining non-200 responses as errors
        else {
            throw new HttpClientException($response->responseMessage, $response->responseCode, $response);
        }

        return $result;
    }

    /**
     * Stolen from OAuth_common
     *
     * @param  string $input
     *
     * @return string Encoded string
     */
    public static function urlencodeRFC3986($input)
    {
        if (is_array($input)) {
            return array_map(array('HttpClient', 'urlencodeRFC3986'), $input);
        } elseif (is_scalar($input)) {
            return str_replace(
                '+',
                ' ',
                str_replace('%7E', '~', rawurlencode($input))
            );
        } else {
            return '';
        }
    }
}
