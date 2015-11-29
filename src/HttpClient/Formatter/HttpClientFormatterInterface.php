<?php

namespace Codemaster\MailUp\HttpClient\Formatter;

/**
 * Interface implemented by formatter implementations for the http client
 */
interface HttpClientFormatterInterface
{
    /**
     * Serializes arbitrary data to the implemented format.
     *
     * @param mixed $data
     *  The data that should be serialized.
     *
     * @return string
     *  The serialized data as a string.
     */
    public function serialize($data);

    /**
     * Unserializes data in the implemented format.
     *
     * @param string $data
     *  The data that should be unserialized.
     *
     * @return mixed
     *  The unserialized data.
     */
    public function unserialize($data);

    /**
     * Return the mime type that the formatter can parse.
     */
    public function accepts();

    /**
     * Return the content type form the data the formatter generates.
     */
    public function contentType();
}
