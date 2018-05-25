<?php

namespace dlds\mailchimp\dto;

class MailChimpBatchResponse implements MailChimpBatchResponseInterface
{
    private $id;
    private $status;
    private $totalOperations;
    private $finishedOperations;
    private $erroredOperations;
    private $submittedAt;
    private $completedAt;
    private $logUrl;

    public function __construct(
        $id,
        $status,
        $totalOperations = null,
        $finishedOperations = null,
        $erroredOperations = null,
        $submittedAt = null,
        $completedAt = null,
        $logUrl = null
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->totalOperations = $totalOperations;
        $this->finishedOperations = $finishedOperations;
        $this->erroredOperations = $erroredOperations;
        $this->submittedAt = $submittedAt;
        $this->completedAt = $completedAt;
        $this->logUrl = $logUrl;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getTotalOperations()
    {
        return $this->totalOperations;
    }

    /**
     * @param mixed $totalOperations
     */
    public function setTotalOperations($totalOperations)
    {
        $this->totalOperations = $totalOperations;
    }

    /**
     * @return mixed
     */
    public function getFinishedOperations()
    {
        return $this->finishedOperations;
    }

    /**
     * @param mixed $finishedOperations
     */
    public function setFinishedOperations($finishedOperations)
    {
        $this->finishedOperations = $finishedOperations;
    }

    /**
     * @return mixed
     */
    public function getErroredOperations()
    {
        return $this->erroredOperations;
    }

    /**
     * @param mixed $erroredOperations
     */
    public function setErroredOperations($erroredOperations)
    {
        $this->erroredOperations = $erroredOperations;
    }

    /**
     * @return mixed
     */
    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }

    /**
     * @param mixed $submittedAt
     */
    public function setSubmittedAt(DateTime $submittedAt = null)
    {
        $this->submittedAt = $submittedAt;
    }

    /**
     * @return mixed
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * @param mixed $completedAt
     */
    public function setCompletedAt(DateTime $completedAt = null)
    {
        $this->completedAt = $completedAt;
    }

    /**
     * @return mixed
     */
    public function getLogUrl()
    {
        return $this->logUrl;
    }

    /**
     * @param mixed $logUrl
     */
    public function setLogUrl($logUrl)
    {
        $this->logUrl = $logUrl;
    }

}
