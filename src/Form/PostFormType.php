<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('content', null, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(),
                    new File([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Selecciona una imagen válida.',
                    ]),
                ],
            ])
            ->add('Send', SubmitType::class, [
                'label' => 'Publicar',
                'attr' => ['class' => 'pull-right btn btn-lg sr-button'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
