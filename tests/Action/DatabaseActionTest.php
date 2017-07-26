<?php

namespace Netgen\Bundle\InformationCollectionBundle\Tests\Action;

use Doctrine\DBAL\DBALException;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\TextLine\Value as TextLineValue;
use eZ\Publish\Core\Repository\ContentService;
use eZ\Publish\Core\Repository\Repository;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\Repository\Values\User\User;
use eZ\Publish\SPI\Persistence\Content\ContentInfo;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\EzFormsBundle\Form\Payload\InformationCollectionStruct;
use Netgen\Bundle\InformationCollectionBundle\Action\DatabaseAction;
use Netgen\Bundle\InformationCollectionBundle\Entity\EzInfoCollection;
use Netgen\Bundle\InformationCollectionBundle\Entity\EzInfoCollectionAttribute;
use Netgen\Bundle\InformationCollectionBundle\Event\InformationCollected;
use Netgen\Bundle\InformationCollectionBundle\Factory\FieldDataFactory;
use Netgen\Bundle\InformationCollectionBundle\Repository\RepositoryAggregate;
use Netgen\Bundle\InformationCollectionBundle\Value\LegacyData;
use PHPUnit\Framework\TestCase;

class DatabaseActionTest extends TestCase
{
    /**
     * @var DatabaseAction
     */
    protected $action;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ezRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentService;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var InformationCollectionStruct
     */
    protected $struct;

    /**
     * @var LegacyData
     */
    protected $legacyData;

    public function setUp()
    {
        $this->factory = $this->getMockBuilder(FieldDataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLegacyValue'))
            ->getMock();

        $this->repository = $this->getMockBuilder(RepositoryAggregate::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createChild', 'createMain'))
            ->getMock();

        $this->ezRepository = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getContentService', 'getCurrentUser'))
            ->getMock();

        $this->contentType = new ContentType(array(
            'fieldDefinitions' => array(
                new FieldDefinition(array(
                    'identifier' => 'some_field',
                    'id' => 321,
                )),
                new FieldDefinition(array(
                    'identifier' => 'some_field_1',
                    'id' => 654,
                )),
                new FieldDefinition(array(
                    'identifier' => 'some_field_2',
                    'id' => 987,
                )),
            ),
        ));

        $this->contentService = $this->getMockBuilder(ContentService::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadContent'))
            ->getMock();

        $this->fields = array(
            new Field(array(
                'id' => 123,
                'fieldDefIdentifier' => 'some_field',
                'value' => new TextLineValue('some value'),
                'languageCode' => 'eng_GB',
            )),
            new Field(array(
                'id' => 456,
                'fieldDefIdentifier' => 'some_field_1',
                'value' => new TextLineValue('some value 1'),
                'languageCode' => 'eng_GB',
            )),
            new Field(array(
                'id' => 789,
                'fieldDefIdentifier' => 'some_field_2',
                'value' => new TextLineValue('some value 2'),
                'languageCode' => 'eng_GB',
            )),
        );

        $this->struct = new InformationCollectionStruct();
        foreach ($this->fields as $field) {
            $this->struct->setCollectedFieldValue($field->fieldDefIdentifier, $field->value);
        }

        $this->legacyData = new LegacyData(
            array(
                'contentClassAttributeId' => 123,
                'dataInt' => 0,
                'dataFloat' => 0.0,
                'dataText' => 'some value',
            )
        );

        $this->action = new DatabaseAction($this->factory, $this->repository, $this->ezRepository);
        parent::setUp();
    }

    public function testAct()
    {
        $location = new Location(array(
            'contentInfo' => new ContentInfo(array(
                'id' => 123,
            )),
        ));

        $content = new Content(array(
            'internalFields' => $this->fields,
            'versionInfo' => new VersionInfo(array(
                'contentInfo' => new ContentInfo(array(
                    'mainLanguageCode' => 'eng_GB',
                )),
            )),
        ));

        $dataWrapper = new DataWrapper($this->struct, $this->contentType, $location);
        $event = new InformationCollected($dataWrapper);

        $user = new User(array(
            'content' => new Content(array(
                'versionInfo' => new VersionInfo(array(
                    'contentInfo' => new ContentInfo(array(
                        'id' => 123,
                    )),
                )),
            )),
            'login' => 'login',
        ));

        $ezInfoCollection = new EzInfoCollection();
        $ezInfoCollectionAttribute = new EzInfoCollectionAttribute();

        $this->ezRepository->expects($this->once())
            ->method('getContentService')
            ->willReturn($this->contentService);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($content);

        $this->ezRepository->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('createMain')
            ->willReturn($ezInfoCollection);

        $this->factory->expects($this->exactly(3))
            ->method('getLegacyValue')
            ->withAnyParameters()
            ->willReturn($this->legacyData);

        $this->repository->expects($this->exactly(3))
            ->method('createChild')
            ->willReturn($ezInfoCollectionAttribute);

        $this->action->act($event);
    }

    /**
     * @expectedException \Netgen\Bundle\InformationCollectionBundle\Exception\ActionFailedException
     */
    public function testActWithExceptionOnInformationCollectionRepository()
    {
        $location = new Location(array(
            'contentInfo' => new ContentInfo(array(
                'id' => 123,
            )),
        ));

        $content = new Content(array(
            'internalFields' => $this->fields,
            'versionInfo' => new VersionInfo(array(
                'contentInfo' => new ContentInfo(array(
                    'mainLanguageCode' => 'eng_GB',
                )),
            )),
        ));

        $dataWrapper = new DataWrapper($this->struct, $this->contentType, $location);
        $event = new InformationCollected($dataWrapper);

        $user = new User(array(
            'content' => new Content(array(
                'versionInfo' => new VersionInfo(array(
                    'contentInfo' => new ContentInfo(array(
                        'id' => 123,
                    )),
                )),
            )),
            'login' => 'login',
        ));

        $ezInfoCollection = new EzInfoCollection();

        $this->ezRepository->expects($this->once())
            ->method('getContentService')
            ->willReturn($this->contentService);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($content);

        $this->ezRepository->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('createMain')
            ->willThrowException(new DBALException());

        $this->factory->expects($this->never())
            ->method('getLegacyValue');

        $this->repository->expects($this->never())
            ->method('createChild');

        $this->action->act($event);
    }

    /**
     * @expectedException \Netgen\Bundle\InformationCollectionBundle\Exception\ActionFailedException
     */
    public function testActWithExceptionOnInformationCollectionAttributeRepository()
    {
        $location = new Location(array(
            'contentInfo' => new ContentInfo(array(
                'id' => 123,
            )),
        ));

        $content = new Content(array(
            'internalFields' => $this->fields,
            'versionInfo' => new VersionInfo(array(
                'contentInfo' => new ContentInfo(array(
                    'mainLanguageCode' => 'eng_GB',
                )),
            )),
        ));

        $dataWrapper = new DataWrapper($this->struct, $this->contentType, $location);
        $event = new InformationCollected($dataWrapper);

        $user = new User(array(
            'content' => new Content(array(
                'versionInfo' => new VersionInfo(array(
                    'contentInfo' => new ContentInfo(array(
                        'id' => 123,
                    )),
                )),
            )),
            'login' => 'login',
        ));

        $ezInfoCollection = new EzInfoCollection();
        $ezInfoCollectionAttribute = new EzInfoCollectionAttribute();

        $this->ezRepository->expects($this->once())
            ->method('getContentService')
            ->willReturn($this->contentService);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($content);

        $this->ezRepository->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('createMain')
            ->willReturn($ezInfoCollection);

        $this->factory->expects($this->exactly(1))
            ->method('getLegacyValue')
            ->withAnyParameters()
            ->willReturn($this->legacyData);

        $this->repository->expects($this->once())
            ->method('createChild')
            ->willThrowException(new DBALException());

        $this->action->act($event);
    }
}
