<?php

namespace dlds\mailchimp;

class MailChimpException extends \yii\base\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'MailChimp Exception';
    }
}
