<?php

namespace dlds\mailchimp\dto;

interface MailChimpBatchResponseInterface
{
    public function getId();
    public function setId($id);
    public function getStatus();
    public function setStatus($status);
    public function getTotalOperations();
    public function setTotalOperations($totalOperations);
    public function getFinishedOperations();
    public function setFinishedOperations($finishedOperations);
    public function getErroredOperations();
    public function setErroredOperations($erroredOperations);
    public function getSubmittedAt();
    public function setSubmittedAt(DateTime $submittedAt);
    public function getCompletedAt();
    public function setCompletedAt(DateTime $completedAt);
    public function getLogUrl();
    public function setLogUrl($logUrl);
}
