<?php

namespace Codemaster\MailUp\HttpClient;

use Codemaster\MailUp\HttpClient\MailUp;
use Codemaster\MailUp\HttpClient\Exception\MailUpException;

/**
 * A client to interact with MailUP API.
 *
 * Implmeneted API:
 *
 * - `GET     Console/Authentication/Info`
 * - `GET     Console/User/Lists`
 * - `GET     Console/User/Emails`
 * - `GET     Console/List/@id_List/Groups`
 * - `POST    Console/List/@id_List/Group`
 * - `PUT     Console/List/@id_List/Group/@id_Group`
 * - `DELETE  Console/List/@id_List/Group/@id_Group`
 * - `GET     Console/List/@id_List/Recipient/@id_Recipient/Groups`
 * - `GET     Console/List/@id_List/Recipients/Subscribed`
 * - `GET     Console/List/@id_List/Recipients/Unsubscribed`
 * - `GET     Console/List/@id_List/Recipients/Pending`
 * - `POST    Console/List/@id_List/Recipients`
 * - `POST    Console/List/@id_List/Subscribe/@id_Recipient`
 * - `DELETE  Console/List/@id_List/Unsubscribe/@id_Recipient`
 * - `POST    Console/Group/@id_Group/Recipients`
 * - `GET     Console/Group/@id_Group/Recipients`
 * - `POST    Console/Group/@id_Group/Subscribe/@id_Recipient`
 * - `DELETE  Console/Group/@id_Group/Unsubscribe/@id_Recipient`
 * - `PUT     Console/Recipient/Detail`
 * - `GET     Console/Recipient/DynamicFields`
 *
 * Some API are missing:
 *
 * - `POST     Console/Email/Send`
 * - `GET      Console/Images`
 * - `POST     Console/Images`
 * - `DELETE   Console/Images`
 * - `GET      Console/Import/@id_Import`
 * - `POST     Console/Group/@id_Group/Email/@id_Message/Send`
 * - `GET      Console/List/@id_List/Tags`
 * - `POST     Console/List/@id_List/Tag`
 * - `PUT      Console/List/@id_List/Tag/@id_Tag`
 * - `DELETE   Console/List/@id_List/Tag/@id_Tag`
 * - `GET      Console/List/@id_List/Email/@id_Message/Attachment`
 * - `POST     Console/List/@id_List/Email/@id_Message/Attachment/@Slot`
 * - `DELETE   Console/List/@id_List/Email/@id_Message/Attachment/@Slot`
 * - `GET      Console/List/@id_List/Images`
 * - `POST     Console/List/@id_List/Images`
 * - `POST     Console/List/@id_List/Email/Template/@id_Template`
 * - `POST     Console/List/@id_List/Email`
 * - `PUT      Console/List/@id_List/Email/@id_Message`
 * - `PUT      Console/List/@id_List/Email/@id_Message/OnlineVisibility`
 * - `DELETE   Console/List/@id_List/Email/@id_Message`
 * - `GET      Console/List/@id_List/Email/@id_Message`
 * - `GET      Console/List/@id_List/Emails`
 * - `GET      Console/List/@id_List/Online/Emails`
 * - `GET      Console/List/@id_List/Archived/Emails`
 * - `GET      Console/List/@id_List/Email/@id_Message/SendHistory`
 * - `POST     Console/List/@id_List/Email/@id_Message/Send`
 * - `GET      Console/List/@id_List/Templates`
 * - `GET      Console/List/@id_List/Templates/@id_Template`
 */
class MailUpClient extends MailUp
{
    /**
     * Get authentication information.
     *
     * @return mixed REST Response
     */
    public function getAuthenticationInfo()
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Authentication/Info';

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Retrieve the email messages (cloned and not cloned) by current user id..
     *
     * @return mixed REST Response
     */
    public function getUserEmails()
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/User/Emails';

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Get array of lists.
     *
     * @return mixed REST Response
     */
    public function getLists()
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/User/Lists';

        $params = array(
            'orderby' => 'idList+asc',
        );

        return $this->callMethodREST($url, 'GET', $params);
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
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Groups';

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Create a new group in the specified list.
     *
     * @param  integer $listId
     * @param  array   $params
     *
     * @return mixed            REST Response
     */
    public function createListGroup($listId, array $params)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Group';

        return $this->callMethodREST($url, 'POST', array(), $params);
    }

    /**
     * Update a group in the specified list.
     *
     * @param  integer $listId
     * @param  integer $groupId
     * @param  array   $params
     *
     * @return mixed            REST Response
     */
    public function updateListGroup($listId, $groupId, array $params)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Group/'.$groupId;

        return $this->callMethodREST($url, 'PUT', null, $params);
    }

    /**
     * Delete a group from the specified list.
     *
     * @param  integer $listId
     * @param  integer $groupId
     *
     * @return mixed            REST Response
     */
    public function deleteListGroup($listId, $groupId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Group/'.$groupId;

        return $this->callMethodREST($url, 'DELETE');
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
        if (!in_array($status, array('Subscribed', 'Unsubscribed', 'Pending'))) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid status', $status));
        }

        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Recipients/'.$status;

        return $this->callMethodREST($url, 'GET', $params);
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
     * Create recipients into a list
     *
     * @param  integer $listId
     * @param  array   $recipients
     *
     * @return mixed                REST Response
     */
    public function importRecipientsInList($listId, $recipients)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Recipients';

        return $this->callMethodREST($url, 'POST', array(), $recipients);
    }

    /**
     * Subscribe recipient to list.
     *
     * @param  integer $listId
     * @param  integer $recipientId
     *
     * @return mixed                REST Response
     */
    public function listSubscribe($listId, $recipientId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/List/'.$listId.'/Subscribe/'.$recipientId;

        return $this->callMethodREST($url, 'POST');
    }

    /**
     * Unsubscibe recipient from list.
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
     * Get group recipients.
     *
     * @param  integer $groupId
     *
     * @return mixed                REST Response
     */
    public function getGroupRecipients($groupId)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Group/'.$groupId.'/Recipients';

        return $this->callMethodREST($url, 'GET');
    }

    /**
     * Create recipients into a group.
     *
     * @param  integer $groupId
     * @param  array   $recipients
     *
     * @return mixed                REST Response
     */
    public function importRecipientsInGroup($groupId, array $recipients)
    {
        $url = self::CONSOLE_ENDPOINT.'/Console/Group/'.$groupId.'/Recipients';

        return $this->callMethodREST($url, 'POST', array(), $recipients);
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
}
