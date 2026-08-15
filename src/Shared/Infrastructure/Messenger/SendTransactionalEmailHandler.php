<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Application\Message\SendTransactionalEmail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class SendTransactionalEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(SendTransactionalEmail $message): void
    {
        $parameters = $message->parameters;
        if ($message->date) {
            $formatter = new \IntlDateFormatter(
                $message->locale,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::SHORT,
                'Europe/Paris',
            );
            $parameters['date'] = $formatter->format($message->date) ?: '';
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'SkillSpot'))
            ->to(new Address($message->to, $message->recipientName))
            ->subject($this->translator->trans($message->subjectKey, $parameters, locale: $message->locale))
            ->htmlTemplate('email/transactional.html.twig')
            ->context([
                'locale' => $message->locale,
                'recipientName' => $message->recipientName,
                'heading' => $this->translator->trans($message->headingKey, $parameters, locale: $message->locale),
                'body' => $this->translator->trans($message->bodyKey, $parameters, locale: $message->locale),
                'actionUrl' => $message->actionUrl,
                'actionLabel' => $message->actionLabelKey
                    ? $this->translator->trans($message->actionLabelKey, locale: $message->locale)
                    : null,
            ]);

        $this->mailer->send($email);
    }
}
