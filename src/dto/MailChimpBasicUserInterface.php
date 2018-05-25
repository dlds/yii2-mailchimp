<?php

namespace dlds\mailchimp\dto;

interface MailChimpBasicUserInterface
{
    public function getEmail();
    public function setEmail($email);
    public function getStatus();
    public function setStatus($status);
}
