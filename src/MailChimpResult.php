<?php

namespace dlds\mailchimp;

class MailChimpResult
{
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    private $status = null;

    /**
     * @var array
     */
    private $messages = array();
    private $dataType = null;
    private $data = null;

    public function __construct($status = null)
    {
        $this->status = $status;
    }

    public function addMessage(string $message)
    {
        $this->messages[] = $message;
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
     * @return array
     */
    public function getMessages() : array
    {
        return $this->messages;
    }

    /**
     * @param mixed $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return null
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param null $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

}
