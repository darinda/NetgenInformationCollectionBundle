<?php

namespace Netgen\Bundle\InformationCollectionBundle\Tests\Mailer;

use Netgen\Bundle\InformationCollectionBundle\Mailer\Mailer;
use Netgen\Bundle\InformationCollectionBundle\Value\EmailData;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    /**
     * @var \Netgen\Bundle\InformationCollectionBundle\Mailer\MailerInterface
     */
    protected $mailer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $swiftMailer;

    public function setUp()
    {
        $this->swiftMailer = $this->getMockBuilder(\Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createMessage', 'send'))
            ->getMock();

        $this->mailer = new Mailer($this->swiftMailer);
    }

    /**
     * @expectedException \Netgen\Bundle\InformationCollectionBundle\Exception\EmailNotSentException
     * @expectedExceptionMessage Error occurred while trying to send email: recipient failed with error Address in mailbox given [[][][]][]] does not comply with RFC 2822, 3.6.2.
     */
    public function testCreateAndSendMessageWithWrongRecipient()
    {
        $data = new EmailData(
            array(
                'recipient' => '[][][]][]',
                'sender' => 'sender@example.com',
                'subject' => 'Test',
                'body' => 'Body',
            )
        );

        $this->swiftMailer->expects($this->never())
            ->method('send');

        $this->mailer->createAndSendMessage($data);
    }

    /**
     * @expectedException \Netgen\Bundle\InformationCollectionBundle\Exception\EmailNotSentException
     * @expectedExceptionMessage Error occurred while trying to send email: sender failed with error Address in mailbox given [[][][]][]] does not comply with RFC 2822, 3.6.2.
     */
    public function testCreateAndSendMessageWithWrongSender()
    {
        $data = new EmailData(
            array(
                'sender' => '[][][]][]',
                'recipient' => 'sender@example.com',
                'subject' => 'Test',
                'body' => 'Body',
            )
        );

        $this->swiftMailer->expects($this->never())
            ->method('send');

        $this->mailer->createAndSendMessage($data);
    }

    /**
     * @expectedException \Netgen\Bundle\InformationCollectionBundle\Exception\EmailNotSentException
     * @expectedExceptionMessage Error occurred while trying to send email: send failed with error invalid mailer configuration?
     */
    public function testCreateAndSendMessageWithErrorFromInternalMailer()
    {
        $data = new EmailData(
            array(
                'recipient' => 'recipient@example.com',
                'sender' => 'sender@example.com',
                'subject' => 'Test',
                'body' => 'Body',
            )
        );

        $this->swiftMailer->expects($this->once())
            ->method('send')
            ->willReturn(0);

        $this->mailer->createAndSendMessage($data);
    }

    public function testCreateAndSendMessage()
    {
        $data = new EmailData(
            array(
                'recipient' => 'recipient@example.com',
                'sender' => 'sender@example.com',
                'subject' => 'Test',
                'body' => 'Body',
            )
        );

        $this->swiftMailer->expects($this->once())
            ->method('send')
            ->willReturn(1);

        $this->mailer->createAndSendMessage($data);
    }
}
