<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Shared\Application\Message\SendTransactionalEmail;
use App\Shared\Infrastructure\Messenger\SendTransactionalEmailHandler;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Messenger\MessageHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class TransactionalEmailTest extends WebTestCase
{
    public function testWorkerRendersAnEnglishEmailAndLocalizedLink(): void
    {
        static::createClient();
        self::getContainer()->get(SendTransactionalEmailHandler::class)(new SendTransactionalEmail(
            'member@example.test',
            'Jane Doe',
            'en',
            'email.verification.subject',
            'email.verification.heading',
            'email.verification.body',
            [],
            'https://skillspot.example/en/verify-email/42',
            'email.verification.action',
        ));

        $this->deliverQueuedEmail();
        $email = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEmailSubjectContains($email, 'Verify your email address');
        self::assertEmailHtmlBodyContains($email, 'Hello Jane Doe');
        self::assertEmailHtmlBodyContains($email, '/en/verify-email/42');
    }

    public function testWorkerRendersAFrenchParameterizedEmail(): void
    {
        static::createClient();
        self::getContainer()->get(SendTransactionalEmailHandler::class)(new SendTransactionalEmail(
            'member@example.test',
            'Jean Dupont',
            'fr',
            'email.booking.confirmed.subject',
            'email.booking.confirmed.heading',
            'email.booking.confirmed.body',
            ['workshop' => 'Symfony sans magie noire'],
            'https://skillspot.example/fr/tableau-de-bord',
            'email.booking.action',
        ));

        $this->deliverQueuedEmail();
        $email = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEmailSubjectContains($email, 'Réservation confirmée');
        self::assertEmailHtmlBodyContains($email, 'Symfony sans magie noire');
        self::assertEmailHtmlBodyContains($email, '/fr/tableau-de-bord');
    }

    public function testWorkerFormatsReminderDateInRecipientLocaleAndParisTimezone(): void
    {
        static::createClient();
        self::getContainer()->get(SendTransactionalEmailHandler::class)(new SendTransactionalEmail(
            'member@example.test',
            'Jane Doe',
            'en',
            'email.booking.reminder.subject',
            'email.booking.reminder.heading',
            'email.booking.reminder.body',
            ['workshop' => 'Symfony without black magic'],
            date: new \DateTimeImmutable('2030-01-03 10:00:00 UTC'),
        ));

        $this->deliverQueuedEmail();
        $email = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEmailHtmlBodyContains($email, 'Thursday, January 3, 2030 at 11:00');
    }

    private function deliverQueuedEmail(): void
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(1, $transport->getSent());
        $message = $transport->getSent()[0]->getMessage();
        self::assertInstanceOf(SendEmailMessage::class, $message);
        $handler = self::getContainer()->get('mailer.messenger.message_handler');
        self::assertInstanceOf(MessageHandler::class, $handler);
        $handler($message);
        self::assertEmailCount(1);
    }
}
