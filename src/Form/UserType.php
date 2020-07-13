<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * This form is used in order to add a user into our website
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'help' => 'Your email must contain a "@" and a "." special character',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please fill this field',
                    ]),
                    new Email([
                        'message' => 'Your email does not have the right format'
                    ]),
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'help' => 'Your password must contain atleast 1 uppercase character, 1 lowercase character, 1 number and must be a minimum of   8 characters',
                'invalid_message' => 'Your password does not match with the confirm password',
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm your password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please fill this field'
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/',
                        'message' => 'Your password must contain atleast 1 uppercase character, 1 lowercase character, 1 number and must be a  minimum of 8 characters'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, ['label'=>'Send', 'attr'=>['class'=>'btn-primary btn-block']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
