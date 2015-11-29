<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\HttpClient;
use Codemaster\MailUp\HttpClient\Authentication\HttpClientAuthenticationInterface;
use Codemaster\MailUp\HttpClient\Delegate\HttpClientDelegateInterface;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;

/**
 * A HTTP client to interact with MailUP API.
 */
class MailUpHttpClient extends HttpClient
{
    /**
     * {@inheritDoc}
     */
    public function __construct(
        HttpClientAuthenticationInterface $authentication = null,
        HttpClientFormatterInterface $formatter = null,
        HttpClientDelegateInterface $delegate = null
    ) {
        parent::__construct($authentication, $formatter, $delegate);

        $this->options['curlopts'] = array(
            CURLOPT_CAINFO => dirname(__FILE__).'/cert/cacert.pem',
        );
    }
}
