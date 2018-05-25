<?php

namespace dlds\mailchimp\dto;

interface MailChimpUserInterface extends MailChimpBasicUserInterface
{
    public function getFirstName();
    public function setFirstName($firstName);
    public function getLastName();
    public function setLastName($lastName);
    public function getLanguage();
    public function setLanguage($language);
    public function getMembershipGroups();
    public function setMembershipGroups(array $membershipGroups);
}
