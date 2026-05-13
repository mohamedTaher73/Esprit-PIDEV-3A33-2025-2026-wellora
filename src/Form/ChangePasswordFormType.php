<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordFormType extends AbstractType
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'] ?? null;

        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => ['placeholder' => '••••••••'],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => '••••••••'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['placeholder' => '••••••••'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
            ])
        ;

        // Validate in the event listener to avoid requiring ValidatorExtension on PasswordType
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($currentUser) {
            $form = $event->getForm();
            $data = $event->getData();

            $currentPassword = $data['currentPassword'] ?? null;
            $newPassword = $data['newPassword'] ?? null;

            // Check current password is not empty
            if (empty($currentPassword)) {
                $form->get('currentPassword')->addError(
                    new FormError('Le mot de passe actuel est obligatoire.')
                );
            }

            // Check new password is not empty
            if (empty($newPassword)) {
                $form->get('newPassword')->addError(
                    new FormError('Le nouveau mot de passe est obligatoire.')
                );
            } elseif (strlen($newPassword) < 8) {
                $form->get('newPassword')->addError(
                    new FormError('Le mot de passe doit contenir au moins 8 caractères.')
                );
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $newPassword)) {
                $form->get('newPassword')->addError(
                    new FormError('Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).')
                );
            }

            // Verify current password against user record
            if ($currentUser && !empty($currentPassword)) {
                if (!$this->passwordHasher->isPasswordValid($currentUser, $currentPassword)) {
                    $form->get('currentPassword')->addError(
                        new FormError('Le mot de passe actuel est incorrect.')
                    );
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'current_user' => null,
        ]);
    }
}
