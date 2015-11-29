<?php

namespace Codemaster\MailUp\HttpClient\Formatter;

use Codemaster\MailUp\HttpClient\Formatter\HttpClientFormatterInterface;

/**
 * Class for handling xml-responses.
 * Returns a SimpleXML object
 *
 * @author Simon Ljungberg <simon.ljungberg@goodold.se>
 */
class HttpClientXMLFormatter implements HttpClientFormatterInterface
{
    private $defaultRoot;
    private $adaptiveRoot;

    /**
     * Creates a HttpClientXMLFormatter.
     *
     * @param string $defaultRoot
     *  Optional. Defaults to 'result'. The default name that should be used for root elements,
     *  if $adaptiveRoot is set to FALSE the default name will always be used.
     * @param bool   $adaptiveRoot
     *  Optional. Defaults to FALSE. If $adaptiveRoot is set to TRUE and the source data has a
     *  single root attribute the serializer will use that attribute as root. The object {"foo":"bar"}
     *  would be serialized to <foo>bar</foo> instead of <result><foo>bar</foo></result>.
     */
    public function __construct($defaultRoot = 'result', $adaptiveRoot = false)
    {
        $this->defaultRoot = $defaultRoot;
        $this->adaptiveRoot = $adaptiveRoot;
    }


    /**
     * {@inheritDoc}
     */
    public function serialize($data)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $rootTag = $this->defaultRoot;

        // Normalize any objects into an array.
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        // Check if we should adapt the name of the root element.
        if ($this->adaptiveRoot && is_array($data) && (count($data) == 1) && !is_numeric(key($data))) {
            $rootTag = $this->sanitizeNodeName(key($data));
            $data = current($data);
        }

        $root = $doc->createElement($rootTag);
        $doc->appendChild($root);

        $this->xmlRecurse($doc, $root, $data);

        return $doc->saveXML();
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($data)
    {
        $xml = simplexml_load_string($data);

        if ($xml instanceof SimpleXMLElement) {
            // Only return data if we got well formed xml
            return $xml;
        } else {
            // Data was messed up
            throw new InvalidArgumentException('XML response was malformed.');
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
     * The mime type
     *
     * @return string
     */
    public function mimeType()
    {
        return 'application/xml';
    }

    /**
     * Directly stolen from http_server by Hugo Wetterberg
     */
    private function xmlRecurse(&$doc, &$parent, $data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            $assoc = false || empty($data);
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $key = 'item';
                } else {
                    $assoc = true;
                    $key = $this->sanitizeNodeName($key);
                }
                $element = $doc->createElement($key);
                $parent->appendChild($element);
                $this->xmlRecurse($doc, $element, $value);
            }

            if (!$assoc) {
                $parent->setAttribute('is_array', 'true');
            }
        } elseif ($data !== null) {
            $parent->appendChild($doc->createTextNode($data));
        }
    }

    /**
     * Sanitizes a string so that it's suitable for use as a element
     * or attribute name.
     *
     * @param string $name
     * @return string
     *  The sanitized name.
     */
    private function sanitizeNodeName($name)
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);

        return preg_replace('/^([0-9]+)/', '_$1', $name);
    }
}
