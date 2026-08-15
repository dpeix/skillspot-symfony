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
            ->add('startsAt', DateTimeType::class, $dateOptions + ['label' => 'Début'])
            ->add('endsAt', DateTimeType::class, $dateOptions + ['label' => 'Fin'])
            ->add('capacity', IntegerType::class, ['label' => 'Nombre de places'])
            ->add('mode', ChoiceType::class, [
                'label' => 'Modalité',
                'choices' => WorkshopMode::cases(),
                'choice_label' => static fn (WorkshopMode $mode): string => $mode->label(),
                'choice_value' => static fn (WorkshopMode $mode): string => $mode->value,
            ])
            ->add('location', null, ['label' => 'Adresse (sur place)', 'required' => false])
            ->add('meetingUrl', UrlType::class, ['label' => 'Lien de visioconférence', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SessionData::class]);
    }
}
