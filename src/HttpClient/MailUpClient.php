<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\MailUp;
use Codemaster\MailUp\HttpClient\Exception\MailUpException;

/**
 * A client to interact with MailUP API.
 */
class MailUpClient extends MailUp
{
    /**
     * @var string
     */
    private $consoleUrl;

    /**
     * Create a new mailup client
     *
     * @param string $username
     * @param string $password
     * @param string $consoleUrl
     */
    public function __construct($username, $password, $consoleUrl = null)
    {
        $this->consoleUrl = $consoleUrl;

        parent::__construct($username, $password);
    }

    /**
     * Check authorization status.
     *
     * @param  boolean $checkConsoleUrl
     *
     * @return mixed                    REST Response
     *
     * @throw  MailUpException
     */
    public function checkAuth($checkConsoleUrl = false)
    {
        if ($checkConsoleUrl) {
            if (empty($this->consoleUrl)) {
                throw new MailUpException(1000, 'No Console URL supplied');
            } else {
                try {
                    $this->callMethodFrontend($this->consoleUrl.'frontend/xmlSubscribe.aspx');
                } catch (MailUpException $e) {
                    throw new MailUpException(1000, sprintf('Invalid Console URL (%s)', $e->getMessage()));
                }
            }
        }

        return $this->callMethodREST(self::CONSOLE_ENDPOINT.'/Console/Authentication/Info', 'GET');
    }

    /**
     * Get array of lists.
     *
     * @return mixed REST Response
     */
    public function getLists()
    {
        $params = array(
            'orderby' => 'idList+asc',
        );

        return $this->callMethodREST(self::CONSOLE_ENDPOINT.'/Console/User/Lists', 'GET', $params);
    }

    /**
     * Get list of groups in specified list.
     *
     * @param  integer $listId
     *
     * @return mixed            REST Response
     */
    public function getListGroups($listId)
    {
        return $this->callMethodREST(self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Groups', 'GET');
    }

    /**
     * Get array of recipients by email.
     *
     * @param  string $email
     *
     * @return mixed          REST Response
     */
    public function getRecipientByEmail($email)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Recipient/'.$email;

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Update recipient.
     *
     * @param  mixed $recipient
     *
     * @return mixed            REST Response
     */
    public function updateRecipient($recipient)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Recipient/Detail';

        return $this->callMethodREST($url, 'PUT', null, $recipient);
    }

    /**
     * Get groups for recipient in list
     *
     * @param  integer $recipientId
     * @param  integer $listId
     *
     * @return mixed                  REST Response
     */
    public function getRecipientListGroups($recipientId, $listId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Recipient/'.$recipientId.'/Groups';

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Get list of dynamic fields.
     *
     * @param  integer $pageSize
     *
     * @return mixed              REST Response
     */
    public function getFields($pageSize = 100)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Recipient/DynamicFields';

        $params = array(
            'pageSize' => $pageSize,
            'orderby' => 'Id',
        );

        return $this->callMethodREST($url, 'GET', $params);
    }

    /**
     * Subscribe recipeints to a list
     *
     * @param  integer $listId
     * @param  array   $recipients
     *
     * @return mixed                REST Response
     */
    public function subscribeToListNew($listId, $recipients)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Recipients';

        return $this->callMethodREST($url, 'POST', array(), $recipients);
    }

    /**
     * Unsubscibe recipeint from list.
     *
     * @param  integer $listId
     * @param  integer $recipientId
     *
     * @return mixed                  REST Response
     */
    public function listUnsubscribe($listId, $recipientId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Unsubscribe/'.$recipientId;

        return $this->callMethodREST($url, 'DELETE');
    }

    /**
     * Check subscription status.
     *
     * @param  integer $listGuid
     * @param  integer $listId
     * @param  string  $email
     *
     * @return mixed              REST Response
     */
    public function checkSubscriptionStatusFrontEnd($listGuid, $listId, $email)
    {
        $url = $this->consoleUrl.'frontend/Xmlchksubscriber.aspx';

        $params = array(
            'listguid' => $listGuid,
            'list' => $listId,
            'email' => $email,
        );

        return $this->callMethodFrontend($url, 'POST', $params);
    }

    /**
     * Unsubscribe from list.
     *
     * @param integer $listGuid
     * @param integer $listId
     * @param string  $email
     *
     * @return mixed              REST Response
     */
    public function unsubscribeFromListFrontEnd($listGuid, $listId, $email)
    {
        $url = $this->consoleUrl.'frontend/xmlunsubscribe.aspx';

        $params = array(
            'listguid' => $listGuid,
            'list' => $listId,
            'email' => $email,
        );

        return $this->callMethodFrontend($url, 'POST', $params);
    }

    /**
     * Subscribe to list
     * @param string $email
     * @param mixed  $listIds Array of list ID or string with list id separated by comma.
     * @param array  $params
     *
     * @return mixed          REST Response
     */
    public function subscribeToListFrontEnd($email, $listIds, array $params)
    {
        $url = $this->consoleUrl.'frontend/xmlSubscribe.aspx';

        $params = $params + array(
            'retcode' => '1',
            'list' => is_array($listIds) ? implode(',', $listIds) : $listIds,
            'email' => $email,
        );

        return $this->callMethodFrontend($url, 'POST', $params);
    }

    /**
     * Subscribe recipient to group.
     *
     * @param  integer $groupId
     * @param  integer $recipientId
     *
     * @return mixed                REST Response
     */
    public function groupSubscribe($groupId, $recipientId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Group/'.$groupId.'/Subscribe/'.$recipientId;

        return $this->callMethodREST($url, 'POST');
    }

    /**
     * Unsubscribe recipient form group.
     *
     * @param  integer $groupId
     * @param  integer $recipientId
     *
     * @return mixed                  REST Response
     */
    public function groupUnsubscribe($groupId, $recipientId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Group/'.$groupId.'/Unsubscribe/'.$recipientId;

        return $this->callMethodREST($url, 'DELETE');
    }

    /**
     * Get array of recipient in list paged.
     *
     * @param  integer $listId
     * @param  integer $pageSize
     * @param  integer $pageNumber
     * @param  string  $type
     *
     * @return mixed                REST Response
     */
    public function getListRecipientsPaged($listId, $pageSize = 100, $pageNumber = 0, $type = 'Subscribed')
    {
        $params = array(
            'pageSize' => $pageSize,
            'pageNumber' => $pageNumber,
        );

        return $this->getListRecipients($listId, $type, $params);
    }

    /**
     * Get array of recipient in list by status.
     *
     * @param  string $listId
     * @param  string $status
     * @param  array  $params
     *
     * @return mixed              REST Response
     */
    public function getListRecipients($listId, $status = 'Subscribed', $params = array())
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Recipients/'.$status;

        return $this->callMethodREST($url, 'GET', $params);
    }

    /**
     * Enable WebService for current IP.
     *
     * @param  string $consoleUrl
     *
     * @return mixed              REST Response
     */
    public function enableWebServiceForCurrentIP($consoleUrl)
    {
        // Extract host, for 'nl_url' param
        $parsedUrl = parse_url($consoleUrl);
        $consoleHostname = empty($parsedUrl['host']) ? $consoleUrl : $parsedUrl['host'];

        $params = array(
            'usr' => $this->username,
            'pwd' => $this->password,
            'nl_url' => $consoleHostname,
            'ws_name' => 'FrontEnd',
        );

        // Construct endpoint URL from console url
        $endpointUrl = 'http://'.$consoleHostname.'/frontend/WSActivation.aspx';

        $xml = $this->callMethodHTTP($endpointUrl, 'GET', $params);

        if (isset($xml->mailupBody)) {
            $code = (int) $xml->mailupBody->ReturnCode;
            // Success
            if ($code === 0) {
                return true;
            }
        }

        return false;
    }
}
