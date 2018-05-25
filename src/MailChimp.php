<?php

namespace dlds\mailchimp;

use DateTime;
use yii\base\Component;
use DrewM\MailChimp\Batch;
use DrewM\MailChimp\MailChimp as MailChimpEngine;
use dlds\mailchimp\dto\MailChimpBasicUser;
use dlds\mailchimp\dto\MailChimpBatchResponse;
use dlds\mailchimp\dto\MailChimpBatchResponseInterface;
use dlds\mailchimp\dto\MailChimpUserInterface;

class MailChimp extends Component
{
    const USER_STATUS_SUBSCRIBED = 'subscribed';
    const USER_STATUS_UNSUBSCRIBED = 'unsubscribed';
    const USER_STATUS_CLEANED = 'cleaned';

    const DATE_FORMAT = DateTime::ATOM;

    /**
     * @var string MailChimp API key
     */
    public $apiKey = null;
    /**
     * @var string MailChimp list ID to work with
     */
    public $listId = null;
    /**
     * @var string MailChimp category ID which contains membership groups
     */
    public $categoryId = null;
    /**
     * @var int number of records to ask for in one response when returning many items
     * (currently used only in {@see getUnsubscribedContacts})
     */
    public $recordsPerResponse = 500;

    /**
     * @var MailChimp
     */
    private $mailChimpWrapper = null;
    public function setMailChimpWrapper($mailChimpWrapper)
    {
        $this->mailChimpWrapper = $mailChimpWrapper;
    }

    /**
     * Initializes MailChimp API v3 wrapper with provided API key.
     * This is invoked by Yii when this component is initialized.
     *
     * @throws MailChimpException
     */
    public function init()
    {
        if (empty($this->apiKey)) {
            throw new MailChimpException(
                'Empty MailChimp API key. Please provide valid API key in module configuration.'
            );
        }
        $this->mailChimpWrapper = new MailChimpEngine($this->apiKey);
    }

    /**
     * Submits batch PUT request to MailChimp. Returns generated ID of the batch operation.
     *
     * @param array $contacts
     * @param string|null $listId
     * @param string|null $categoryId
     * @return MailChimpResult
     * @throws MailChimpException
     */
    public function addOrUpdateContacts(
        array $contacts, string $listId = null, string $categoryId = null
    ) : MailChimpResult {
        $listId = $this->determineListId($listId);
        $existingGroups = $this->getCategoryGroups($listId, $categoryId);

        $result = new MailChimpResult(MailChimpResult::STATUS_SUCCESS);

        $batch = $this->prepareContactsBatchRequest($contacts, $existingGroups, $listId, $result);

        $batchResult = $batch->execute();

        $batchResponseObj = $this->parseBatchResponse($batchResult);

        // check response for generated batch ID
        if (isset($batchResponseObj)) {
            $result->setDataType(MailChimpBatchResponseInterface::class);
            $result->setData($batchResponseObj);
        } else {
            $result->setStatus(MailChimpResult::STATUS_ERROR);
            $result->addMessage('Got unexpected error message from server or timeout.'
                .'Please check config variables or try again later.');
        }

        return $result;
    }

    /**
     * Get information about single MailChimp batch job.
     *
     * @param string $batchId
     * @return MailChimpResult
     */
    public function getBatchStatus(string $batchId) : MailChimpResult
    {
        $batch = $this->mailChimpWrapper->new_batch();
        $result = new MailChimpResult();

        $batchResponseObj = $this->parseBatchResponse($batch->check_status($batchId));

        if (isset($batchResponseObj)) {
            $result->setStatus(MailChimpResult::STATUS_SUCCESS);
            $result->setDataType(MailChimpBatchResponseInterface::class);
            $result->setData($batchResponseObj);
        } else {
            $result->setStatus(MailChimpResult::STATUS_ERROR);
            $result->addMessage('Got unexpected error message from server or timeout.'
                .' Please check config variables or try again later.');
        }

        return $result;
    }

    /**
     * Gets information about n last (by default n = 5) submitted batch jobs.
     * @param int $count
     * @return MailChimpResult
     */
    public function getLastBatches(int $count = 5) : MailChimpResult
    {
        $result = new MailChimpResult();

        $batches = $this->mailChimpWrapper->get('batches', ['count' => $count]);

        $batchResponseArr = $this->parseBatchesResponse($batches);

        if (isset($batchResponseArr)) {
            $result->setStatus(MailChimpResult::STATUS_SUCCESS);
            $result->setData($batchResponseArr);
        } else {
            $result->setStatus(MailChimpResult::STATUS_ERROR);
            $result->addMessage('Got unexpected error message from server or timeout.'
                .' Please check config variables or try again later.');
        }

        return $result;
    }

    /**
     * Gets all unsubscribed or cleaned contacts.
     *
     * @param DateTime|null $sinceLastChanged
     * @param string|null $listId
     * @return MailChimpResult
     * @throws MailChimpException
     */
    public function getUnsubscribedContacts(DateTime $sinceLastChanged = null, string $listId = null) : MailChimpResult
    {
        $listId = $this->determineListId($listId);
        $result = new MailChimpResult();

        $requestData = ['status' => [self::USER_STATUS_UNSUBSCRIBED, self::USER_STATUS_CLEANED]];
        if (isset($sinceLastChanged)) {
            $requestData['since_last_changed'] = $sinceLastChanged->format(self::DATE_FORMAT);
        }
        $requestData['count'] = $this->recordsPerResponse;
        $requestData['offset'] = 0;

        $contactsArr = array();

        while (true) {
            $contacts = $this->mailChimpWrapper->get('lists/' . $listId . '/members', $requestData);
            $contactsParsed = $this->parseContactsResponse($contacts);

            if (isset($contactsParsed)) {
                if (count($contactsParsed) > 0) {
                    $contactsArr = array_merge($contactsArr, $contactsParsed);
                } else {
                    break;
                }
            } else {
                $result->setStatus(MailChimpResult::STATUS_ERROR);
                $result->addMessage('Got unexpected error message from server or timeout.'
                    .' Please check config variables or try again later.');
                break;
            }

            $requestData['offset'] += $requestData['count'];
        }

        $result->setStatus(MailChimpResult::STATUS_SUCCESS);
        $result->setData($contactsArr);

        return $result;
    }


    /*
     * private methods
     */

    /**
     * @param string $listId
     * @return string
     * @throws MailChimpException
     */
    private function determineListId(string $listId = null)
    {
        if (is_null($listId)) {
            if (isset($this->listId)) {
                return $this->listId;
            } else {
                throw new MailChimpException('List ID not defined in module config'
                    .' and specific list ID not provided as function call parameter.');
            }
        } else {
            return $listId;
        }
    }

    /**
     * @param string $listId
     * @param string|null $categoryId
     * @return mixed
     * @throws MailChimpException
     */
    private function getCategoryGroups(string $listId, string $categoryId = null)
    {
        if (is_null($categoryId)) {
            if (isset($this->categoryId)) {
                $categoryId = $this->categoryId;
            } else {
                throw new MailChimpException('Category ID not defined in module config'
                    .' and specific category ID not provided as function call parameter.');
            }
        }

        $interestsResult = $this->mailChimpWrapper->get(
            'lists/' . $listId . '/interest-categories/' . $categoryId . '/interests'
        );

        $groups = array();
        if ($interestsResult && isset($interestsResult['interests'])) {
            foreach ($interestsResult['interests'] as $interest) {
                $groups[strtolower(trim($interest['name']))] = $interest['id'];
            }
        }

        return $groups;
    }


    /**
     * @param array $contacts
     * @param array $existingGroups
     * @param string $listId
     * @param MailChimpResult $result
     * @throws MailChimpException
     */
    private function prepareContactsBatchRequest(
        array &$contacts,
        array &$existingGroups,
        string $listId,
        MailChimpResult $result
    ) : Batch {
        /** @var Batch $batch */
        $batch = $this->mailChimpWrapper->new_batch();

        foreach ($contacts as $contact) {
            // check data format
            if (!($contact instanceof MailChimpUserInterface)) {
                throw new MailChimpException(
                    'Invalid contact format! Please use array of MailChimpUserInterface objects.'
                );
            }
            // check required data
            if (empty($contact->getEmail())) {
                $result->setStatus(MailChimpResult::STATUS_WARNING);
                $result->addMessage('Missing email address for user ' . $contact->getFirstName() ?? ''
                    . ' ' . $contact->getLastName() ?? '' . ', skipping.');
                continue;
            }

            // prepare request content
            //
            $requestData = [
                'email_address' => $contact->getEmail(),
                'status' => $contact->getStatus()
            ];
            if (!empty($contact->getFirstName())) {
                $requestData['merge_fields']['FNAME'] = $contact->getFirstName();
            }
            if (!empty($contact->getLastName())) {
                $requestData['merge_fields']['LNAME'] = $contact->getLastName();
            }
            if (!empty($contact->getLanguage()) && strlen($contact->getLanguage()) == 2) {
                $requestData['language'] = $contact->getLanguage();
            }

            if (!is_null($contact->getMembershipGroups())) {
                $userGroups = array_map('strtolower', $contact->getMembershipGroups());
                foreach ($existingGroups as $existingGroupName => $existingGroupId) {
                    $requestData['interests'][$existingGroupId] =
                        (in_array($existingGroupName, $userGroups)) ? true : false;
                }
            }

            $batch->put(
                $contact->getEmail(),
                'lists/' . $listId . '/members/' . $this->mailChimpWrapper->subscriberHash($contact->getEmail()),
                $requestData
            );
        }

        return $batch;
    }

    private function parseBatchesResponse($batchesData)
    {
        if (!isset($batchesData['batches'])) {
            return null;
        }

        $result = array();
        foreach ($batchesData['batches'] as $batchData) {
            $batchObj = $this->parseBatchResponse($batchData);
            if (isset($batchObj)) {
                $result[] = $batchObj;
            }
        }

        return $result;
    }

    private function parseBatchResponse($batchData)
    {
        if (!isset($batchData['id'])) {
            return null;
        }

        $submittedAt = $this->parseMailChimpDate($batchData['submitted_at'] ?? null);
        $completedAt = $this->parseMailChimpDate($batchData['completed_at'] ?? null);

        $batchResponseObj = new MailChimpBatchResponse(
            $batchData['id'],
            $batchData['status'] ?? 'UNKNOWN',
            $batchData['total_operations'] ?? null,
            $batchData['finished_operations'] ?? null,
            $batchData['errored_operations'] ?? null,
            $submittedAt ?: null,
            $completedAt ?: null,
            $batchData['response_body_url'] ?? null
        );

        return $batchResponseObj;
    }

    private function parseContactsResponse($contactsData)
    {
        if (!isset($contactsData['members'])) {
            return null;
        }

        $result = array();
        foreach ($contactsData['members'] as $contactData) {
            $contactObj = $this->parseContactResponse($contactData);
            if (isset($contactObj)) {
                $result[] = $contactObj;
            }
        }

        return $result;
    }

    private function parseContactResponse($contactData)
    {
        if (!isset($contactData['id'])) {
            return null;
        }

        $contactObj = new MailChimpBasicUser(
            $contactData['email_address'] ?? null,
            $contactData['status'] ?? null
        );

        return $contactObj;
    }

    private function parseMailChimpDate(string $dateString = null)
    {
        $dateTime = DateTime::createFromFormat(self::DATE_FORMAT, $dateString);
        return $dateTime;
    }
}
