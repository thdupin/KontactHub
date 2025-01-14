<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Group;
use App\Repository\GroupRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email (optionnel)',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('photoFile', FileType::class, [
                'label' => false,
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'mapped' => false,  // Ne pas mapper directement ce champ dans la base de données
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser {{ limit }}Mo',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image au format JPG, PNG ou GIF.',
                    ])
                ]
            ])
            ->add('groups', EntityType::class, [
                'class' => Group::class,
                'label' => 'Sélectionner un ou plusieurs groupes',
                'choice_label' => function (Group $group) {
                    return strtoupper($group->getName()); // Transforme le label en majuscules
                },
                'multiple' => true, // Permet la sélection multiple
                'expanded' => true, // Utilise des cases à cocher (true) ou un select (false)
                'required' => false,
                'query_builder' => function (GroupRepository $gr) {
                    return $gr->createQueryBuilder('g')
                        ->orderBy('LOWER(g.name)', 'ASC'); // Tri par ordre alphabétique
                },
                'data' => $options['groups']
            ])
            ->add('newGroups', CollectionType::class, [
                'entry_type' => GroupType::class,       // Type des champs (un champ texte par groupe)
                'label' => false,
                'allow_add' => true,                   // Autoriser l'ajout dynamique de champs
                'allow_delete' => true,                // Autoriser la suppression dynamique
                'mapped' => false,                     // Ce champ n'est pas directement mappé à une entité
                'prototype' => true,                   // Utilisé pour générer des champs dynamiques côté JS
                'required' => false,
            ])
            ->add('customFields', CollectionType::class, [
                'entry_type' => ContactFieldType::class,  // Type de champ pour chaque ContactField
                'allow_add' => true,  // Permet d'ajouter dynamiquement des champs
                'allow_delete' => true,  // Permet de supprimer des champs
                'by_reference' => false,  // Assure que les données sont persistées correctement
                'label' => false,
                'mapped' => true, // Lier les champs à l'entité Contact
                'data' => $options['customFields'], // Passer une collection d'objets ContactField au formulaire
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'customFields' => null,
            'groups' => null
        ]);
    }
}
