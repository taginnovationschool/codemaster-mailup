# Mailup

## Install

Enable external repository, add in ```composer.json```

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/taginnovationschool/codemaster-mailup"
        }
    ]
}
```

Decrease stability level in `composer.json`:

```json
{
    "minimum-stability": "dev"
}
```

Download using composer:

```
composer require codemaster/mailup
```

## Usage

```php
include 'vendor/autoload.php';

use Codemaster\MailUp\HttpClient\MailUpClient;

$username = 'm00000';
$password = '012345689';

// Create a new mailup client
$client = new MailUpClient($username, $password);

// Get authentication information.
$response = $client->getAuthenticationInfo();
var_dump($response);

// Get logged user emails.
$response = $client->getUserEmails();
var_dump($response);

// Get lists available.
$response = $client->getLists();
var_dump($response);

// Get groups in the specified list.
$response = $client->getListGroups(1);
var_dump($response);

// Create new group in the specified list.
$response = $client->createListGroup(1, array(
    'Name' => 'Gruppo prova 1',
    'Notes' => 'Informazioni di prova',
));
var_dump($response);

// Update group in the specified list.
$response = $client->updateListGroup(1, 1, array(
    'Name' => 'Gruppo prova 1 cambiato',
));
var_dump($response);

// Delete group in the specified list.
$response = $client->deleteListGroup(1, 1);
var_dump($response);

// Get recipients in list for each status, by default 'Subscribed'.
$response = $client->getListRecipients(1, 'Subscribed');
var_dump($response);
$response = $client->getListRecipients(1, 'Unsubscribed');
var_dump($response);
$response = $client->getListRecipients(1, 'Pending');
var_dump($response);

// Get list of groups for the specified recipient in specified list.
$response = $client->getRecipientListGroups(4, 1);
var_dump($response);

// Create recipients in the specified list.
$response = $client->importRecipientsInList(1, [
    [
        'Name' => 'Mario Rossi',
        'Email' => 'mario.rossi@test.it',
        'MobilePrefix' => '+39',
        'MobileNumber' => '3331231234',
    ],
    [
        'Name' => 'Luca Bianchi',
        'Email' => 'luca.bianchi@test.it',
        'MobilePrefix' => '+39',
        'MobileNumber' => '3349876543',
    ]
]);
var_dump($response);

// Subscribe recipient in list
$response = $client->listSubscribe(1, 2);
var_dump($response);

// Unsubscribe recipient from list
$response = $client->listSubscribe(1, 2);
var_dump($response);

// Create recipients in the specified group.
$response = $client->importRecipientsInGroup(3, [
    [
        'Name' => 'Mario Rossi',
        'Email' => 'mario.rossi2@test.it',
        'MobilePrefix' => '+39',
        'MobileNumber' => '3339231234',
    ],
    [
        'Name' => 'Luca Bianchi',
        'Email' => 'luca.bianchi2@test.it',
        'MobilePrefix' => '+39',
        'MobileNumber' => '3341876543',
    ]
]);
var_dump($response);

// Get recipients for the specified group.
$response = $client->getGroupRecipients(3);
var_dump($response);


// Subscribe recipient in group
$response = $client->groupSubscribe(1, 2);
var_dump($response);

// Unsubscribe recipient from group
$response = $client->groupUnsubscribe(1, 2);
var_dump($response);

// Update recipient.
$response = $client->updateRecipient([
    'idRecipient' => 18,
    'Name' => 'Changed NAme',
    'Email' => 'changed+mail@test.it',
]);
var_dump($response);

// Get list of custom fields.
$response = $client->getFields();
var_dump($response);
```
