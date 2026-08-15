<?php

declare(strict_types=1);

namespace App\Identity\UI\Form;

use App\Identity\Application\Data\RegisterUserData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<RegisterUserData> */
final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, ['label' => 'identity.form.first_name'])
            ->add('lastName', null, ['label' => 'identity.form.last_name'])
            ->add('email', EmailType::class, ['label' => 'identity.form.email'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'identity.form.password'],
                'second_options' => ['label' => 'identity.form.password_confirmation'],
                'invalid_message' => 'identity.validation.passwords_match',
            ])
            ->add('agreeTerms', CheckboxType::class, ['label' => 'identity.form.agree_terms']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RegisterUserData::class]);
    }
}
