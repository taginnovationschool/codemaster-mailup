<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\HttpClient;

/**
 * This is a convenience class that allows the manipulation of a http request
 * before it's handed over to curl.
 */
class HttpClientRequest
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    public $method = self::METHOD_GET;
    public $url = '';
    public $parameters = array();
    public $headers = array();
    public $data = null;

    /**
     * Allows specification of additional custom options.
     */
    public $options = array();

    /**
     * Construct a new client request.
     *
     * @param string $url
     *   The url to send the request to.
     * @param array  $values
     *   An array of values for the object properties to set for the request.
     */
    public function __construct($url, array $values = array())
    {
        $this->url = $url;
        foreach (get_object_vars($this) as $key => $value) {
            if (isset($values[$key])) {
                $this->$key = $values[$key];
            }
        }
    }

    /**
     * Gets the values of a header, or the value of the header if
     * $treatAsSingle is set to true.
     *
     * @param string $name
     * @param string $treatAsSingle
     *  Optional. If set to FALSE an array of values will be returned. Otherwise
     *  The first value of the header will be returned.
     * @return string|array
     */
    public function getHeader($name, $treatAsSingle = true)
    {
        $value = null;

        if (!empty($this->headers[$name])) {
            if ($treatAsSingle) {
                $value = reset($this->headers[$name]);
            } else {
                $value = $this->headers[$name];
            }
        }

        return $value;
    }

    /**
     * Returns the headers as a array. Multiple valued headers will have their
     * values concatenated and separated by a comma as per
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = array();
        foreach ($this->headers as $name => $values) {
            $headers[] = $name.': '.join($values, ', ');
        }

        return $headers;
    }

    /**
     * Appends a header value. Use HttpClientRequest::setHeader() it you want to
     * set the value of a header.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addHeader($name, $value)
    {
        if (!is_array($value)) {
            $this->headers[$name][] = $value;
        } else {
            $values = isset($this->headers[$name]) ? $this->headers[$name] : array();
            $this->headers[$name] = $values + $value;
        }
    }

    /**
     * Sets a header value.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setHeader($name, $value)
    {
        if (!is_array($value)) {
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header.
     *
     * @param string $name
     * @return void
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }

    /**
     * Returns the url taken the parameters into account.
     *
     * @return  string
     */
    public function url()
    {
        if (empty($this->parameters)) {
            return $this->url;
        }
        $total = array();
        foreach ($this->parameters as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $va) {
                    $total[] = HttpClient::urlencodeRFC3986($k)."[]=".HttpClient::urlencodeRFC3986($va);
                }
            } else {
                $total[] = HttpClient::urlencodeRFC3986($k)."=".HttpClient::urlencodeRFC3986($v);
            }
        }
        $out = implode("&", $total);

        return $this->url.'?'.$out;
    }
}
