<?php

namespace Codemaster\MailUp\HttpClient\Exception;

use \Exception;

/**
 * Exception that's used to pass information about the response when
 * a operation fails.
 */
class HttpClientException extends Exception
{
    protected $response;

    /**
     * Create ah HTTP Exception
     *
     * @param string    $message
     * @param integer   $code
     * @param mixed     $response
     * @param Exception $exception
     */
    public function __construct($message, $code = 0, $response = null, $exception = null)
    {
        parent::__construct($message, $code);

        $this->response = $response;
    }

    /**
     * Gets the response object, if any.
     *
     * @return mixed Response obejct
     */
    public function getResponse()
    {
        $response = $this->response;
        if (is_object($response)) {
            $response = clone $response;
        }

        return $response;
    }
}
