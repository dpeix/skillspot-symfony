<?php

declare(strict_types=1);

namespace App\Workshop\UI\Form;

use App\Workshop\Application\Data\WorkshopData;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<WorkshopData> */
final class WorkshopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ['label' => 'Titre'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 10]])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => WorkshopCategory::cases(),
                'choice_label' => static fn (WorkshopCategory $category): string => $category->label(),
                'choice_value' => static fn (WorkshopCategory $category): string => $category->value,
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => WorkshopLevel::cases(),
                'choice_label' => static fn (WorkshopLevel $level): string => $level->label(),
                'choice_value' => static fn (WorkshopLevel $level): string => $level->value,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => WorkshopData::class]);
    }
}
