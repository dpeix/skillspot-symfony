<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Application\Message\SendTransactionalEmail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final readonly class SendTransactionalEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(SendTransactionalEmail $message): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'SkillSpot'))
            ->to(new Address($message->to, $message->recipientName))
            ->subject($message->subject)
            ->htmlTemplate('email/transactional.html.twig')
            ->context(['message' => $message]);

        $this->mailer->send($email);
    }
}
