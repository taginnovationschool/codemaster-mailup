<?php

namespace Codemaster\MailUp\HttpClient\Formatter;

use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;

/**
 * One way formatter, for accepting plain html from MailUp API
 * Should only be used as *accept* formatter in HttpClientCompositeFormatter
 */
class HttpClientMailUpHtmlFormatter implements HttpClientFormatterInterface
{
    /**
     * @var string
     */
    protected $mimeType;

    /**
     * Create a new MailUP HTML Formatter.
     */
    public function __construct()
    {
        $this->mimeType = 'text/html';
    }


    /**
     * {@inheritDoc}
     */
    public function serialize($data)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($data)
    {
        return (string) $data;
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
        return 'text/plain';
    }

    /**
     * The mime type
     *
     * @return string
     */
    public function mimeType()
    {
        return $this->mimeType;
    }
}
