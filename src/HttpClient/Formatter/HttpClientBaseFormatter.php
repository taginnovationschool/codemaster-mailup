<?php

namespace Codemaster\MailUp\HttpClient\Formatter;

use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;

/**
 * A base formatter to format php and json.
 */
class HttpClientBaseFormatter implements HttpClientFormatterInterface
{
    const FORMAT_PHP = 'php';
    const FORMAT_JSON = 'json';
    const FORMAT_FORM = 'form';

    protected $mimeTypes = array(
        self::FORMAT_PHP => 'application/vnd.php.serialized',
        self::FORMAT_JSON => 'application/json',
        self::FORMAT_FORM => 'application/x-www-form-urlencoded',
    );

    protected $format;

    /**
     * Create the formatter using the specified type.
     *
     * @param string $format
     */
    public function __construct($format = self::FORMAT_PHP)
    {
        $this->format = $format;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($data)
    {
        switch ($this->format) {
            case self::FORMAT_PHP:
                return serialize($data);
                break;
            case self::FORMAT_JSON:
                return json_encode($data);
                break;
            case self::FORMAT_FORM:
                return http_build_query($data, null, '&');
                break;
        }
    }


    /**
     * {@inheritDoc}
     */
    public function unserialize($data)
    {
        switch ($this->format) {
            case self::FORMAT_PHP:
                if (($response = @unserialize($data)) !== false || $data === serialize(false)) {
                    return $response;
                } else {
                    throw new Exception('Unserialization of response body failed.', 1);
                }
                break;
            case self::FORMAT_JSON:
                $response = json_decode($data);
                if ($response === null && json_last_error() != JSON_ERROR_NONE) {
                    throw new Exception('Unserialization of response body failed.', 1);
                }

                return $response;
                break;
            case self::FORMAT_FORM:
                $response = array();
                parse_str($data, $response);

                return $response;
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function accepts()
    {
        return $this->mimeType();
    }

    /**
     * {@inheritDoc}
     */
    public function contentType()
    {
        return $this->mimeType();
    }

    /**
     * Returns the mime type to use.
     *
     * @return string
     */
    public function mimeType()
    {
        return $this->mimeTypes[$this->format];
    }
}
