<?php

declare(strict_types=1);

namespace App\Workshop\UI\Form;

use App\Workshop\Application\Data\SessionData;
use App\Workshop\Domain\Enum\WorkshopMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<SessionData> */
final class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateOptions = ['widget' => 'single_text', 'input' => 'datetime_immutable', 'model_timezone' => 'UTC', 'view_timezone' => 'Europe/Paris'];
        $builder
            ->add('startsAt', DateTimeType::class, $dateOptions + ['label' => 'session.form.starts_at'])
            ->add('endsAt', DateTimeType::class, $dateOptions + ['label' => 'session.form.ends_at'])
            ->add('capacity', IntegerType::class, ['label' => 'session.form.capacity'])
            ->add('mode', ChoiceType::class, [
                'label' => 'session.form.mode',
                'choices' => WorkshopMode::cases(),
                'choice_label' => static fn (WorkshopMode $mode): string => $mode->labelKey(),
                'choice_value' => static fn (WorkshopMode $mode): string => $mode->value,
            ])
            ->add('location', null, ['label' => 'session.form.location', 'required' => false])
            ->add('meetingUrl', UrlType::class, ['label' => 'session.form.meeting_url', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SessionData::class]);
    }
}
