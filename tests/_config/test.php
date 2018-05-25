<?php
// config/test.php
$config =  yii\helpers\ArrayHelper::merge(
    array(),
//    require(__DIR__ . '/main.php'),
//    require(__DIR__ . '/main-local.php'),
    [
        'id' => 'mailchimp-tests',
        'basePath' => dirname(__DIR__ . '/../'),
        'components' => [
            'mailchimp' => [
                'class' => 'dlds\mailchimp\MailChimp',
                'apiKey' => 'b566b8adf6d21626d2f7ffdf0eb4dd2f-us17',
                'listId' => '6da44b4a05',
                'categoryId' => '9b60acfe43'
            ]
        ]
    ]
);
return $config;
