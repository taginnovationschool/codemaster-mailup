<?php

namespace Codemaster\MailUp\HttpClient\Formatter;

use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;

/**
 * A utility formatter to use for creating assymetrical http client formatters.
 */
class HttpClientCompositeFormatter implements HttpClientFormatterInterface
{
    /**
     * @var HttpClientBaseFormatter
     */
    private $send = null;

    /**
     * @var HttpClientBaseFormatter
     */
    private $accept = null;

    /**
     * Creates an assymetrical formatter.
     *
     * @param string|HttpClientFormatterInterface $send
     *  Optional. The formatter to use when sending requests. Accepts one of
     *  the HttpClientBaseFormatter::FORMAT_ constants or a HttpClientFormatterInterface
     *  object. Defaults to form encoded.
     * @param string|HttpClientFormatterInterface $accept
     *  Optional. The formatter to use when parsing responses. Accepts one of
     *  the HttpClientBaseFormatter::FORMAT_ constants or a HttpClientFormatterInterface
     *  object. Defaults to json.
     */
    public function __construct(
        $send = HttpClientBaseFormatter::FORMAT_FORM,
        $accept = HttpClientBaseFormatter::FORMAT_JSON
    ) {
        if (is_string($send)) {
            $send = new HttpClientBaseFormatter($send);
        }
        if (is_string($accept)) {
            $accept = new HttpClientBaseFormatter($accept);
        }

        $this->send = $send;
        $this->accept = $accept;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($data)
    {
        return $this->send->serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($data)
    {
        return $this->accept->unserialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function accepts()
    {
        return $this->accept->mimeType();
    }

    /**
     * {@inheritDoc}
     */
    public function contentType()
    {
        return $this->send->mimeType();
    }
}
