<?php

namespace Codemaster\MailUp\HttpClient\Exception;

use \Exception;

/**
 * Exception genrated by MailUP integration.
 */
class MailUpException extends Exception
{
    /**
     * @var integer
     */
    private $statusCode;

    /**
     * Create a new exception using a specified status code and message.
     *
     * @param integer $statusCode
     * @param string  $message
     */
    public function __construct($statusCode, $message)
    {
        parent::__construct($message);

        $this->statusCode = $statusCode;
    }

    /**
     * Get the status code
     *
     * @return integer Status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set the status code
     *
     * @param integer $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }
}
