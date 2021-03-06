<?php

namespace Tests\AppBundle\Mailjet\Message;

use AppBundle\Entity\Adherent;
use AppBundle\Mailjet\Message\EventNotificationMessage;
use AppBundle\Mailjet\Message\MailjetMessageRecipient;
use Tests\AppBundle\Config;

class EventNotificationMessageTest extends AbstractEventMessageTest
{
    const SHOW_EVENT_URL = 'https://'.Config::APP_HOST.'/comites/59b1314d-dcfb-4a4c-83e1-212841d0bd0f/evenements/2017-01-31-en-marche-lyon';
    const ATTEND_EVENT_URL = 'https://'.Config::APP_HOST.'/comites/59b1314d-dcfb-4a4c-83e1-212841d0bd0f/evenements/2017-01-31-en-marche-lyon/inscription';

    public function testCreateEventNotificationMessage()
    {
        $recipients[] = $this->createAdherentMock('em@example.com', 'Émmanuel', 'Macron');
        $recipients[] = $this->createAdherentMock('jb@example.com', 'Jean', 'Berenger');
        $recipients[] = $this->createAdherentMock('ml@example.com', 'Marie', 'Lambert');
        $recipients[] = $this->createAdherentMock('ez@example.com', 'Éric', 'Zitrone');

        $message = EventNotificationMessage::create(
            $recipients,
            $recipients[0],
            $this->createEventMock('En Marche Lyon', '2017-02-01 15:30:00', '15 allées Paul Bocuse', '69006-69386', 'EM Lyon'),
            self::SHOW_EVENT_URL,
            self::ATTEND_EVENT_URL,
            function (Adherent $adherent) {
                return EventNotificationMessage::getRecipientVars($adherent->getFirstName());
            }
        );

        $this->assertInstanceOf(EventNotificationMessage::class, $message);
        $this->assertSame('54917', $message->getTemplate());
        $this->assertCount(4, $message->getRecipients());
        $this->assertSame('1 février - 15h30 : Nouvel événement de EM Lyon : En Marche Lyon', $message->getSubject());
        $this->assertCount(10, $message->getVars());
        $this->assertSame(
            [
                'animator_firstname' => 'Émmanuel',
                'event_name' => 'En Marche Lyon',
                'event_date' => 'mercredi 1 février 2017',
                'event_hour' => '15h30',
                'event_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'event_slug' => self::SHOW_EVENT_URL,
                'event-slug' => self::SHOW_EVENT_URL,
                'event_ok_link' => self::ATTEND_EVENT_URL,
                'event_ko_link' => self::SHOW_EVENT_URL,
                'target_firstname' => '',
            ],
            $message->getVars()
        );

        $recipient = $message->getRecipient(0);
        $this->assertInstanceOf(MailjetMessageRecipient::class, $recipient);
        $this->assertSame('em@example.com', $recipient->getEmailAddress());
        $this->assertSame('Émmanuel Macron', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Émmanuel',
                'event_name' => 'En Marche Lyon',
                'event_date' => 'mercredi 1 février 2017',
                'event_hour' => '15h30',
                'event_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'event_slug' => self::SHOW_EVENT_URL,
                'event-slug' => self::SHOW_EVENT_URL,
                'event_ok_link' => self::ATTEND_EVENT_URL,
                'event_ko_link' => self::SHOW_EVENT_URL,
                'target_firstname' => 'Émmanuel',
            ],
            $recipient->getVars()
        );

        $recipient = $message->getRecipient(3);
        $this->assertInstanceOf(MailjetMessageRecipient::class, $recipient);
        $this->assertSame('ez@example.com', $recipient->getEmailAddress());
        $this->assertSame('Éric Zitrone', $recipient->getFullName());
        $this->assertSame(
            [
                'animator_firstname' => 'Émmanuel',
                'event_name' => 'En Marche Lyon',
                'event_date' => 'mercredi 1 février 2017',
                'event_hour' => '15h30',
                'event_address' => '15 allées Paul Bocuse, 69006 Lyon 6e',
                'event_slug' => self::SHOW_EVENT_URL,
                'event-slug' => self::SHOW_EVENT_URL,
                'event_ok_link' => self::ATTEND_EVENT_URL,
                'event_ko_link' => self::SHOW_EVENT_URL,
                'target_firstname' => 'Éric',
            ],
            $recipient->getVars()
        );
    }
}
