<?php

declare(strict_types=1);

namespace App\Identity\UI\Form;

use App\Identity\Application\Data\OrganizerApplicationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<OrganizerApplicationData> */
final class OrganizerApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('motivation', TextareaType::class, [
            'label' => 'organizer.application.form.motivation',
            'help' => 'organizer.application.form.motivation_help',
            'attr' => ['rows' => 8],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrganizerApplicationData::class]);
    }
}
