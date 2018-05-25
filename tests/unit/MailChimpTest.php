<?php

use Codeception\Specify;
use Codeception\Stub;
use DrewM\MailChimp\Batch;
use dlds\mailchimp\MailChimp;
use dlds\mailchimp\MailChimpException;
use dlds\mailchimp\MailChimpResult;
use dlds\mailchimp\dto\MailChimpUser;

/**
 *  @SuppressWarnings(PHPMD.CamelCaseMethodName)
 *  @noinspection PhpHierarchyChecksInspection
 */
class MailChimpTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var MailChimp $chimp
     */
    protected $chimp;

    /** @specify */
    protected static $contacts;

    public static function setUpBeforeClass()
    {
        self::$contacts = array(
            new MailChimpUser('user@domain.cz', 'Name', 'Surname', 'cs', []),
            new MailChimpUser('user2@domain2.sk', 'Name2', 'Surname2', 'sk', array('investors'))
        );
    }

    // phpcs:disable
    protected function _before()
    {
        $this->chimp = Yii::$app->mailchimp;
    }

    protected function _after() {}
    // phpcs:enable

    // tests

    public function testMissingAPIKey()
    {
        // reset API key set from config
        $this->chimp->apiKey = null;

        $this->tester->expectException(
            MailChimpException::class,
            function () {
                $this->chimp->init();
            }
        );
    }

    public function testMissingListId()
    {
        // reset list ID set from config
        $this->chimp->listId = null;

        $this->tester->expectException(
            MailChimpException::class,
            function () {
                $this->chimp->addOrUpdateContacts(array());
            }
        );
    }

    public function testMissingCategoryId()
    {
        // reset list ID set from config
        $this->chimp->categoryId = null;

        $this->tester->expectException(
            MailChimpException::class,
            function () {
                $this->chimp->addOrUpdateContacts(array());
            }
        );
    }

    /**
     * @throws \Exception
     */
    public function testUpdateContactsInvalidDataType()
    {
        $this->setBatchMock(array());

        $this->tester->expectException(
            MailChimpException::class,
            function () {
                $this->chimp->addOrUpdateContacts(array('stringValue'));
            }
        );
    }

    /**
     * @throws \Exception
     */
    public function testUpdateContactsSuccess()
    {
        $batchResponseParsed = array('id' => 'abc123');
        $this->setBatchMock($batchResponseParsed);

        $result = $this->chimp->addOrUpdateContacts(self::$contacts);

        verify($result->getStatus())->equals(MailChimpResult::STATUS_SUCCESS);
        verify($result->getData()->getId())->equals($batchResponseParsed['id']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateContactsWarning()
    {
        $batchResponseParsed = array('id' => 'abc123');
        $this->setBatchMock($batchResponseParsed);

        $this->specify('Empty email address', function () {
            self::$contacts[0]->setEmail('');
            $result = $this->chimp->addOrUpdateContacts(self::$contacts);
            verify($result->getStatus())->equals(MailChimpResult::STATUS_WARNING);
        });
    }

    /**
     * @throws \Exception
     */
    public function testUpdateContactsError()
    {
        $batchResponseParsed = array('some unexpected' => 'value');
        $this->setBatchMock($batchResponseParsed);

        $result = $this->chimp->addOrUpdateContacts(self::$contacts);

        verify($result->getStatus())->equals(MailChimpResult::STATUS_ERROR);
    }

    /**
     * @throws \Exception
     */
    public function testGetBatchStatusSuccess()
    {
        $batchId = 'b4tch1d';
        $submittedAt = '2018-03-15T14:34:17+00:00';

        $batchMock = $this->makeEmpty(
            'DrewM\MailChimp\Batch',
            ['check_status' => function ($passedBatchId) use ($submittedAt) {
                return array(
                    'id' => $passedBatchId,
                    'submitted_at' => $submittedAt
                );
            }]
        );
        $this->setMailChimpWrapperMock($batchMock);

        $result = $this->chimp->getBatchStatus($batchId);

        verify($result->getStatus())->equals(MailChimpResult::STATUS_SUCCESS);
        verify($result->getData()->getId())->equals($batchId);

        $parsedDate = $result->getData()->getSubmittedAt();
        verify($parsedDate)->notEmpty();
        verify($parsedDate->format('Y-m-d\TH:i:sP'))->equals($submittedAt);
    }

    /**
     * @throws \Exception
     */
    public function testGetLastBatchesSuccess()
    {
        $batchId = 'b4tch1d';
        $mockedGetMethod = function ($path, $args) use ($batchId) {
            // verify correct API path and default count parameter
            verify($path)->equals('batches');
            verify($args['count'] ?? '')->equals(5);
            return array(
                'batches' => array(
                    array ('id' => $batchId)
                )
            );
        };
        $this->setMailChimpWrapperMock(null, array('get' => $mockedGetMethod));

        $result = $this->chimp->getLastBatches();

        verify($result->getStatus())->equals(MailChimpResult::STATUS_SUCCESS);
        verify($result->getData()[0]->getId())->equals($batchId);
    }

    /**
     * @throws \Exception
     */
    public function testGetUnsubscribedContacts()
    {
        $sequenceNum = 0;
        $offset = 0;
        $sinceLastChanged = new DateTime('now');
        $lastResponseSize = 2;
        $lastUserEmail = 'lastUser@email.cz';

        $mockedGetMethod = function ($path, $args) use (
            &$sequenceNum, &$offset, $sinceLastChanged, $lastResponseSize, $lastUserEmail
        ) {
            // verify request parameters
            verify($path)->equals('lists/' . $this->chimp->listId . '/members');
            $this->assertTrue(isset($args['status']));
            verify($args['status'])->equals([MailChimp::USER_STATUS_UNSUBSCRIBED, MailChimp::USER_STATUS_CLEANED]);
            verify($args['since_last_changed'])->equals($sinceLastChanged->format(MailChimp::DATE_FORMAT));
            verify($args['count'])->equals($this->chimp->recordsPerResponse);
            verify($args['offset'])->equals($offset);

            if ($sequenceNum > 1) {
                return array('members' => array());
            }

            $returnedItemsCount = ($sequenceNum == 1) ? $lastResponseSize : $this->chimp->recordsPerResponse;
            $sequenceNum++;
            $offset += $this->chimp->recordsPerResponse;

            $membersArr = array_fill(0, $returnedItemsCount - 1, array(
                'id' => 'someId', 'email_address' => 'some@email.cz'
            ));
            array_push($membersArr, array('id' => 'someId', 'email_address' => $lastUserEmail));

            return array('members' => $membersArr);
        };
        $this->setMailChimpWrapperMock(null, array('get' => $mockedGetMethod));

        $result = $this->chimp->getUnsubscribedContacts($sinceLastChanged);

        verify($result->getStatus())->equals(MailChimpResult::STATUS_SUCCESS);
        verify($result->getData())->count($this->chimp->recordsPerResponse + $lastResponseSize);

        verify($result->getData()[$this->chimp->recordsPerResponse + $lastResponseSize - 1]->getEmail())
            ->equals($lastUserEmail);
    }


    /*
     * protected methods
     */

    /**
     * @param string $batchResponseParsed
     * @throws \Exception
     */
    protected function setBatchMock(array $batchResponseParsed)
    {
        $batchMock = $this->makeEmpty(
            'DrewM\MailChimp\Batch',
            ['execute' => function () use ($batchResponseParsed) {
                return $batchResponseParsed;
            }]
        );
        $this->setMailChimpWrapperMock($batchMock);
    }

    /**
     * @param Batch|null $batchMock
     * @param array|null $properties
     * @throws \Exception
     */
    protected function setMailChimpWrapperMock(Batch $batchMock = null, array $properties = null)
    {
        $propArr = ($batchMock) ?
            ['new_batch' => function () use ($batchMock) {
                return $batchMock;
            }] : array();
        if ($properties) {
            $propArr = array_merge($propArr, $properties);
        }
        $mailChimpWrapperMock = $this->makeEmpty(
            'DrewM\MailChimp\MailChimp',
            $propArr
        );
        $this->chimp->setMailChimpWrapper($mailChimpWrapperMock);
    }
}
