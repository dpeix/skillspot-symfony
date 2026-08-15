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
            ->add('titleFr', null, ['label' => 'workshop.form.title'])
            ->add('descriptionFr', TextareaType::class, ['label' => 'workshop.form.description', 'attr' => ['rows' => 8]])
            ->add('titleEn', null, ['label' => 'workshop.form.title'])
            ->add('descriptionEn', TextareaType::class, ['label' => 'workshop.form.description', 'attr' => ['rows' => 8]])
            ->add('category', ChoiceType::class, [
                'label' => 'workshop.form.category',
                'choices' => WorkshopCategory::cases(),
                'choice_label' => static fn (WorkshopCategory $category): string => $category->labelKey(),
                'choice_value' => static fn (WorkshopCategory $category): string => $category->value,
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'workshop.form.level',
                'choices' => WorkshopLevel::cases(),
                'choice_label' => static fn (WorkshopLevel $level): string => $level->labelKey(),
                'choice_value' => static fn (WorkshopLevel $level): string => $level->value,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => WorkshopData::class]);
    }
}
