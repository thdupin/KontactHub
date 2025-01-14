<?php

namespace App\Form;

use App\Entity\ContactField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ContactFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('value', TextType::class, [
                'label' => 'Valeur',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isDeleted', HiddenType::class, [
                'mapped' => false, // Ne pas mapper directement ce champ sur l'entité ContactField
                'required' => false,
                'data' => false,   // Par défaut, non supprimé
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactField::class
        ]);
    }
}
