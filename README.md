# SilverStripe Notifications Module

Send CMS managed system email notifications from code.

## Maintainer Contacts
*  Marcus Nyeholt (<marcus@symbiote.com.au>)
*  Shea Dawson (<shea@symbiote.com.au>)

## Requirements
* SilverStripe 3.1 +

## Installation Instructions

```
composer require symbiote/silverstripe-notifications
```

## Creating System Notifications

### 1)
In your _config yml file, add an identifier for each notification you require. This allows you to lookup Notification objects in the database from your code. 

```
SystemNotification:
  identifiers:
    - 'NAME_OF_NOTIFICATION1'
    - 'NAME_OF_NOTIFICATION2'
```

### 2)
Add the NotifiedOn interface to any dataobjects that are relevant to the notifications you will be sending. This is required so the Notifications module can look up the below methods on your object to send the notification.

```php
class MyDataObject extends DataObject implements NotifiedOn {
	...
```

Define the following interface methods on the Object being notified on. 

```php
/**
 * Return a list of available keywords in the format 
 * array('keyword' => 'A description') to help users format notification fields
 * @return array
 */
public function getAvailableKeywords();
```
```php
/**
 * Gets an associative array of data that can be accessed in
 * notification fields and templates 
 * @return array
 */
public function getNotificationTemplateData();
```

Note: the follow template data is automatically included:

* $ThemeDir
* $SiteConfig
* $MyDataObject (whatever the ClassName of your NotifiedOn DataObject is)
* $Member (The Member object this message is being sent to)

```php
/**
 * Gets the list of recipients for a given notification event, based on this object's 
 * state. 
 * $event The identifier of the event that triggered this notification
 * @return array An array of Member objects	
 */
public function getRecipients($event);
```

Note: getRecipients() can return an array of any objects, as long as they have an Email property or method

### 3)

Create a notification in the Notifications model admin, in the CMS.

### 4)
Send the notification from your code, where $contextObject is an instance of the DataObject being NotifiedOn 
```php
singleton('NotificationService')->notify('NOTIFICATION_IDENTIFIER', $contextObject);
```

## Templates

Notifications can be rendered with .ss templates. This is useful if you want to have a header/footer in your email notifications. You can either specify a template on a per/notification basis in the CMS, and/or set a default template for all notifications to be rendered with:

```
SystemNotification:
  default_template: EmailNotification
```

In your templates, you render the notification text with the $Body variable.

## Configuration

You will probably want to configure a send_from email address - 
```
EmailNotificationSender:
  send_notifications_from: 'notifications@example.com'
```  

## TODO 

* Test with QueuedJobs module for handling large amounts of notifications in configurable batches/queues
