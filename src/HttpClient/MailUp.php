<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\MailUpHttpClient;
use Codemaster\MailUp\HttpClient\HttpClientRequest;
use Codemaster\MailUp\HttpClient\Authentication\HttpClientMailUpBasicAuth;
use Codemaster\MailUp\HttpClient\Authentication\HttpClientMailUpOAuth2;
use Codemaster\MailUp\HttpClient\Exception\HttpClientException;
use Codemaster\MailUp\HttpClient\Exception\MailUpException;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientBaseFormatter;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientCompositeFormatter;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientMailUpHtmlFormatter;
use Codemaster\MailUp\HttpClient\Formatter\HttpClientXMLFormatter;

/**
 * Class to interact with MailUP API.
 */
class MailUp
{
    const MAILUP_CLIENT_ID     = '83844ffd-3110-4efa-bb92-87e20c0e305d';
    const MAILUP_CLIENT_SECRET = '983406a4-6a0f-4fb1-b348-f200fd471bee';

    const LOGON_ENDPOINT       = 'https://services.mailup.com/Authorization/OAuth/LogOn';
    const TOKEN_ENDPOINT       = 'https://services.mailup.com/Authorization/OAuth/Token';
    const AUTH_ENDPOINT        = 'https://services.mailup.com/Authorization/OAuth/Authorization';

    const CONSOLE_ENDPOINT     = 'https://services.mailup.com/API/v1.1/Rest/ConsoleService.svc';
    const PUBLIC_ENDPOINT      = 'https://services.mailup.com/API/v1.1/Rest/PublicService.svc';
    const STATS_ENDPOINT       = 'https://services.mailup.com/API/v1.1/Rest/MailStatisticsService.svc';

    protected $callbackUri;
    protected $accessToken;
    protected $refreshToken;
    protected $tokenExpires;
    protected $allowRefresh;

    /**
     * Create MailUP class.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->allowRefresh = true;
        $this->loadTokensFromSession();

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Clear MailUP token stored in session.
     */
    public static function clearSessionTokens()
    {
        unset($_SESSION['mailup_access_token']);
        unset($_SESSION['mailup_refresh_token']);
        unset($_SESSION['mailup_token_expiry']);
    }

    /**
     * Update token from response
     *
     * @param  mixed $response
     */
    public function updateTokenFromResponse($response)
    {
        $response = (array) $response;
        $this->accessToken = $response['access_token'];
        $this->refreshToken = $response['refresh_token'];
        $this->tokenExpires = time() + $response['expires_in'];

        $this->storeToken();
    }

    /**
     * Require trial activation.
     *
     * @param  mixed $data
     *
     * @return mixed          REST Response
     */
    public function requestTrialActivation($data)
    {
        return $this->callMethodHttpBasicAuth(self::PUBLIC_ENDPOINT.'/Console/TrialActivation', 'POST', null, $data);
    }

    /**
     * Get trial activation status.
     *
     * @param  integer $id
     * @param  string  $hash
     *
     * @return mixed          REST Response
     */
    public function getTrialActivationStatus($id, $hash)
    {
        $status = array(
            '0' => 'Activation not confirmed',
            '1' => 'Your MailUp account is being created. Account credentials will be sent to your email',
            '2' => 'Your trial has been activated and you’ll receive the account credentials in your inbox',
            '3' => 'An error occurred and an alert has been notified to our support team in order to solve this issue',
            '4' => 'MailUp platform is in maintenance mode, your request will be processed as soon as the platform will be restored',
            '5' => 'The trial activation is in progress, soon you’ll receive the account credentials',
            '6' => 'An error occurred and an alert has been notified to our support team in order to solve this issue',
            '7' => 'The trial activation is in progress, soon you’ll receive the account credential',
        );

        $result = $this->callMethodHttpBasicAuth(self::PUBLIC_ENDPOINT.'/Console/TrialActivationStatus', 'POST', null, array('Id' => $id, 'Hash' => $hash));

        return array(
            'code' => $result['Code'],
            'message' => $status[$result['Code']],
        );
    }

    /**
     * Retrive access token.
     *
     * @return string Access token.
     */
    public function retrieveAccessToken()
    {
        if (empty($this->username) || empty($this->password)) {
            throw new MailUpException(1000, 'Unable to access MailUp REST API without setting credentials.');
        }

        $authentication = new HttpClientMailUpBasicAuth(self::MAILUP_CLIENT_ID, self::MAILUP_CLIENT_SECRET);
        $formatter = new HttpClientBaseFormatter(HttpClientBaseFormatter::FORMAT_JSON);
        $httpClient = new MailUpHttpClient($authentication, $formatter);

        $params = array(
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
        );

        try {
            $result = $httpClient->post(self::TOKEN_ENDPOINT, null, $params);
            $this->updateTokenFromResponse($result);

            return $this->accessToken;
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    /**
     * Log-on user with credentials.
     *
     * @param  string $username
     * @param  string $password
     *
     * @return string Access token
     */
    public function logOnWithPassword($username, $password)
    {
        return $this->retrieveAccessToken($username, $password);
    }

    /**
     * Indicate if there is a valid token.
     *
     * @return boolean
     */
    public function hasValidToken()
    {
        $properties = array('accessToken', 'refreshToken', 'tokenExpires');

        foreach ($properties as $property) {
            if (empty($this->{$property})) {
                return false;
            }
        }

        if (time() > $this->tokenExpires) {
            return false;
        }

        return true;
    }

    /**
     * Helper to parse HttpClientException
     * Re-throws as MailUpException
     *
     * @param  HttpClientException $e
     *
     * @throw  MailUpException
     */
    public function handleHttpClientException(HttpClientException $e)
    {
        // Default message (useful when unreachable host etc)
        $msg = $e->getMessage();

        // Use a non-http code
        $code = 1000;

        // A response was received
        if ($response = $e->getResponse()) {
            // Use the HTTP code
            $code = $response->responseCode;
            // Static message
            if ($staticMessage = $this->lookupResponseCode($response->responseCode)) {
                $msg = $staticMessage;
            }
            // Try and better this by parsing the actual error from message body
            if (!empty($response->body) && $parsedBody = json_decode($response->body)) {
                if (!empty($parsedBody->error)) {
                    $msg = $parsedBody->error;
                    if (!empty($parsedBody->{'error_description'})) {
                        $msg = $parsedBody->{'error_description'};
                    }
                    $msg .= ' (HTTP '.$response->responseCode.')';
                }
            }
        }

        // Re-throw
        throw new MailUpException($code, $msg);
    }

    /**
     * Call method on MailUP frontend.
     *
     * @param  string $url
     * @param  string $httpMethod
     * @param  array  $params
     * @param  array  $data
     *
     * @return mixed              REST Response
     */
    public function callMethodFrontend($url, $httpMethod = 'GET', $params = array(), $data = array())
    {
        $formatter = new HttpClientCompositeFormatter(
            new HttpClientBaseFormatter(HttpClientBaseFormatter::FORMAT_FORM),
            new HttpClientMailUpHtmlFormatter()
        );

        $httpClient = new MailUpHttpClient(null, $formatter);

        $request = array(
            'method' => $httpMethod,
            'parameters' => $params,
        );

        if (!empty($data)) {
            $request['data'] = $data;
        }

        try {
            return $httpClient->execute(new HttpClientRequest($url, $request));
        } catch (HttpClientException $e) {
            return $this->handleHttpClientException($e);
        }
    }

    /**
     * Call method using HTTP
     *
     * @param  string $url
     * @param  string $httpMethod
     * @param  array  $params
     * @param  array  $data
     *
     * @return mixed              REST Response
     */
    public function callMethodHTTP($url, $httpMethod = 'GET', $params = array(), $data = array())
    {
        $httpClient = new MailUpHttpClient(null, new HttpClientXMLFormatter());

        $request = array(
            'method' => $httpMethod,
            'parameters' => $params,
        );

        if (!empty($data)) {
            $request['data'] = $data;
        }

        try {
            return $httpClient->execute(new HttpClientRequest($url, $request));
        } catch (HttpClientException $e) {
            return $this->handleHttpClientException($e);
        }
    }

    /**
     * Call method using HTTP BasicAuth.
     *
     * @param  string $url
     * @param  string $httpMethod
     * @param  array  $params
     * @param  array  $data
     *
     * @return mixed              REST Response
     */
    public function callMethodHttpBasicAuth($url, $httpMethod = 'GET', $params = array(), $data = array())
    {
        $authentication = new HttpClientMailUpBasicAuth(self::MAILUP_CLIENT_ID, self::MAILUP_CLIENT_SECRET);
        $formatter = new HttpClientBaseFormatter(HttpClientBaseFormatter::FORMAT_JSON);

        $httpClient = new MailUpHttpClient($authentication, $formatter);

        $request = array(
            'method' => $httpMethod,
            'parameters' => $params,
        );

        if (!empty($data)) {
            $request['data'] = $data;
        }

        try {
            return $httpClient->execute(new HttpClientRequest($url, $request));
        } catch (HttpClientException $e) {
            return $this->handleHttpClientException($e);
        }
    }

    /**
     * Call method using REST interface.
     *
     * @param  string $url
     * @param  string $httpMethod
     * @param  array  $params
     * @param  array  $data
     *
     * @return mixed              REST Response
     */
    public function callMethodREST($url, $httpMethod = 'GET', $params = array(), $data = array())
    {
        // Will request or refresh token only when required.
        $this->ensureAccessToken();

        $authentication = new HttpClientMailUpOAuth2($this->accessToken);
        $formatter = new HttpClientBaseFormatter(HttpClientBaseFormatter::FORMAT_JSON);

        $httpClient = new MailUpHttpClient($authentication, $formatter);

        try {
            $request = array(
                'method' => $httpMethod,
                'parameters' => $params,
            );

            if (!empty($data)) {
                $request['data'] = $data;
            }

            return $httpClient->execute(new HttpClientRequest($url, $request));
        } catch (HttpClientException $e) {
            // Get object containing HTTP response
            if ($response = $e->getResponse()) {

                // DELETE seems to
                if ($response->responseCode == 200) {
                    return true;
                }

                // Unauthorized
                if ($response->responseCode == 401) {
                    // Try refreshing the access token if we can
                    if ($this->allowRefresh && $this->refreshAccessToken()) {
                        // And try again
                        return $this->callMethodREST($url);
                    }
                }
            }

            // Something else went wrong
            return $this->handleHttpClientException($e);
        }
    }

    /**
     * Lookup response code description.
     *
     * @param  integer $code
     *
     * @return string
     */
    private function lookupResponseCode($code = null)
    {
        $responseCodes = array(
            '200' => '200: OK.',
            '201' => '201: The request has been accepted for processing, but the processing has not been completed.',
            '203' => '203: The server successfully processed the request, but is returning information that may be from another source.',
            '204' => '204: The server successfully processed the request, but is not returning any content.',
            '205' => '205: The server successfully processed the request, but is not returning any content. Requires the requester to reset the document view.',
            '206' => '206: The server is delivering only part of the resource due to a range header sent by the client.',
            '207' => '207: The message body that follows is an XML message and can contain a number of separate response codes.',
            '208' => '208: The members of a DAV binding have already been enumerated in a previous reply to this request, and are not being included again.',
            '226' => '226: The server has fulfilled a GET request for the resource, and the response is a representation of the result of one or more instance-manipulations applied to the current instance.',
            '300' => '300: Indicates multiple options for the resource that the client may follow.',
            '301' => '301: This and all future requests should be directed to the given URI.',
            '302' => '302: Required the client to perform a temporary redirect',
            '303' => '303: The response to the request can be found under another URI using a GET method.',
            '304' => '304: Indicates that the resource has not been modified since the version specified by the request headers If-Modified-Since or If-Match.',
            '305' => '305: The requested resource is only available through a proxy, whose address is provided in the response.',
            '307' => '307: The request should be repeated with another URI',
            '308' => '308: The request, and all future requests should be repeated using another URI.',
            '400' => '400: The request cannot be fulfilled due to bad syntax.',
            '401' => '401: Authentication faild.',
            '403' => '403: The request was a valid request, but the server is refusing to respond to it.',
            '404' => '404: The requested resource could not be found but may be available again in the future.',
            '405' => '405: A request was made of a resource using a request method not supported by that resource.',
            '406' => '406: The requested resource is only capable of generating content not acceptable according to the Accept headers sent in the request',
            '407' => '407: The client must first authenticate itself with the proxy.',
            '408' => '408: The server timed out waiting for the request.',
            '409' => '409: Indicates that the request could not be processed because of conflict in the request, such as an edit conflict in the case of multiple updates.',
            '410' => '410: Indicates that the resource requested is no longer available and will not be available again.',
            '411' => '411: The request did not specify the length of its content, which is required by the requested resource.',
            '412' => '412: The server does not meet one of the preconditions that the requester put on the request.',
            '413' => '413: The request is larger than the server is willing or able to process.',
            '414' => '414: The URI provided was too long for the server to process',
            '415' => '415: The request entity has a media type which the server or resource does not support.',
            '416' => '416: The client has asked for a portion of the file, but the server cannot supply that portion.',
            '417' => '417: The server cannot meet the requirements of the Expect request-header field.',
            '419' => '419: Authentication Timeout denotes that previously valid authentication has expired.',
            '422' => '422: The request was well-formed but was unable to be followed due to semantic errors',
            '423' => '423: The resource that is being accessed is locked.',
            '424' => '424: The request failed due to failure of a previous request',
            '426' => '426: The client should switch to a different protocol such as TLS/1.0',
            '428' => '428: The origin server requires the request to be conditional.',
            '429' => '429: The user has sent too many requests in a given amount of time.',
            '431' => '431: The server is unwilling to process the request because either an individual header field, or all the header fields collectively, are too large.',
            '440' => '440: Your session has expired.',
            '444' => '444: The server did not return any information to the client and close the connection.',
            '449' => '449: The request should be retried after performing the appropriate action.',
            '450' => '450: Windows Parental Controls are turned on and are blocking access to the given webpage.',
            '451' => '451: If there either is a more efficient server to use or the server cannot access the users\' mailbox.',
            '500' => '500: Internal Server Error.',
            '501' => '501: The server either does not recognize the request method, or it lacks the ability to fulfill the request.',
            '502' => '502: The server was acting as a gateway or proxy and received an invalid response from the upstream server.',
            '503' => '503: The server is currently unavailable.',
            '504' => '504: The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.',
            '505' => '505: The server does not support the HTTP protocol version used in the request.',
            '507' => '507: The server is unable to store the representation needed to complete the request.',
            '508' => '508: The server detected an infinite loop while processing the request.',
            '509' => '509: Bandwidth Limit Exceeded.',
            '510' => '510: Further extensions to the request are required for the server to fulfill it.',
            '511' => '511: The client needs to authenticate to gain network access.',
            '598' => '598: The network read timeout behind the proxy to a client in front of the proxy.',
            '599' => '599: Network connect timeout behind the proxy to a client in front of the proxy.',
        );

        if (!empty($responseCodes[$code])) {
            return $responseCodes[$code];
        }

        return false;
    }

    /**
     * Ensure that there is a valid token.
     */
    private function ensureAccessToken()
    {
        if ($this->allowRefresh) {
            if (empty($this->accessToken)) {
                $this->retrieveAccessToken();
            } else {
                if (time() > $this->tokenExpires) {
                    $this->refreshAccessToken();
                }
            }
        }
    }

    private function refreshAccessToken()
    {
        $this->allowRefresh = false;

        $authentication = new HttpClientMailUpBasicAuth(self::MAILUP_CLIENT_ID, self::MAILUP_CLIENT_SECRET);
        $formatter = new HttpClientBaseFormatter(HttpClientBaseFormatter::FORMAT_JSON);
        $httpClient = new MailUpHttpClient($authentication, $formatter);

        $params = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
        );

        try {
            $result = $httpClient->post(self::TOKEN_ENDPOINT, null, $params);
            $this->updateTokenFromResponse($result);

            return true;
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);

            return false;
        }
    }

    private function storeToken()
    {
        $_SESSION['mailup_access_token'] = $this->accessToken;
        $_SESSION['mailup_refresh_token'] = $this->refreshToken;
        $_SESSION['mailup_token_expiry'] = $this->tokenExpires;
    }

    private function loadTokensFromSession()
    {
        if (isset($_SESSION["mailup_access_token"])) {
            $this->accessToken = $_SESSION["mailup_access_token"];
        }
        if (isset($_SESSION["mailup_refresh_token"])) {
            $this->refreshToken = $_SESSION["mailup_refresh_token"];
        }
        if (isset($_SESSION["mailup_token_expiry"])) {
            $this->tokenExpires = $_SESSION["mailup_token_expiry"];
        }
    }
}
