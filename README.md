# Yii2 MailChimp extension for YeahCoach project

MailChimp API (v3) integration developed for YeahCoach project.

## Installation
### Extension installaton
Use `composer` to install the extension to your Yii2 installation.

### Component Setup
The extension works as a standard Yii2 component.

To use it, just add and configure it inside the `main.php` configuration array
(or wherever suitable for your setup), e.g.

````php
    'components' => [    
        
        // ...
        
        'mailChimp' => [
            'class' => 'dlds\mailchimp\YcMailChimp',
            'apiKey' => 'a576b8adf6d34916d2f7eedf0eb4dd2f-us17',
            'listId' => '123abc4560',
            'categoryId' => 'def890abcd'
        ]
    ]
````

Required initial values are only `class` and `apiKey`, but it's recommended to specify all properties as shown
to avoid the need to pass them later (you can always override the default values and pass them as method parameters).

## Usage example
Example usage:
````php
// load the component
$chimp = Yii::$app->mailChimp;

// get unsubscribed users on your MailChimp list
$resultObject = $chimp->getUnsubscribedContacts();

// print returned array of MailChimpBasicUser objects
print_r($resultObject->getData());
````

## Testing
Codeception unit tests are included.
From the root directory:
````shell
php ./vendor/bin/codecept run unit
````

## Built with
* [MailChimp API](https://github.com/drewm/mailchimp-api) - simple MailChimp API wrapper (uses cURL for API calls) 

