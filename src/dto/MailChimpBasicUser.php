<?php

namespace dlds\mailchimp\dto;

class MailChimpBasicUser implements MailChimpBasicUserInterface
{
    private $email;
    private $status;

    public function __construct(string $email, $status = null)
    {
        $this->email = $email;
        $this->status = $status;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
}