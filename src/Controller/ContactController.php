<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ContactFieldRepository;
use Doctrine\Common\Collections\ArrayCollection;

class ContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function strToCapitalize($string) {
        // Utilisation de mb_convert_case pour préserver les caractères accentués
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    public function strToUpper($string) {
        // Utilisation de mb_strtoupper pour convertir toute la chaîne en majuscules
        return mb_strtoupper($string, 'UTF-8');
    }

    #[Route('/contact', name: 'app_contact_view', methods: ['GET'])]
    public function index(ContactFieldRepository $contactFieldRepository): Response
    {

        // Récupérer les contacts
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();
        
        // Collecter tous les champs personnalisés uniques
        $uniqueFields = [];
        foreach ($contacts as $contact) {
            // Récupérer les champs personnalisés pour ce contact
            $customFields = $contactFieldRepository->findBy(['contact' => $contact]);

            // Attacher les champs personnalisés au contact
            $contact->setCustomFields(new ArrayCollection($customFields));

            // Ajouter les noms de champs uniques
            foreach ($customFields as $field) {
                // Comparer en minuscules pour ignorer la casse
                $fieldName = strtolower($field->getName());

                if (!in_array($fieldName, $uniqueFields)) {
                    $uniqueFields[] = $fieldName;
                }
            }
        }

        // Transmettre les données à la vue
        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,          // Envoie tous les contacts récupérés
            'uniqueFields' => $uniqueFields,  // Envoie les champs personnalisés uniques
        ]);
    }

    #[Route('/contact/data', name: 'app_contact_data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        // Récupérer tous les contacts sans appliquer de recherche
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        // Collecter tous les champs personnalisés uniques pour tous les contacts
        $uniqueFields = [];
        foreach ($contacts as $contact) {
            foreach ($contact->getCustomFields() as $customField) {
                // Comparer en minuscules pour ignorer la casse
                $fieldName = strtolower($customField->getName());

                if (!in_array($fieldName, $uniqueFields)) {
                    $uniqueFields[] = $fieldName;
                }
            }
        }

        // Récupérer les paramètres de pagination et de recherche
        $search = $request->query->get('search', '');

        // Récupérer les paramètres de pagination depuis la requête
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 10);

        // Création d'une QueryBuilder pour la recherche
        $queryBuilder = $this->entityManager->getRepository(Contact::class)->createQueryBuilder('c')
            ->setFirstResult($offset) // Définir l'offset
            ->setMaxResults($limit);

        // Si un terme de recherche est fourni, appliquer les conditions de filtre
        if ($search) {
            $queryBuilder
                ->leftJoin('c.groups', 'g') // Joindre les groupes
                ->leftJoin('c.customFields', 'cf') // Joindre les champs personnalisés
                ->addSelect('g', 'cf') // Ajouter les informations du groupe et des champs personnalisés
                ->andWhere('LOWER(c.firstName) LIKE :searchTerm')
                ->orWhere('LOWER(c.lastName) LIKE :searchTerm')
                ->orWhere('LOWER(c.email) LIKE :searchTerm')
                ->orWhere('LOWER(c.phone) LIKE :searchTerm')
                // Recherche sur le nom du groupe
                ->orWhere('LOWER(g.name) LIKE :searchTerm')
                // Recherche dans les valeurs des champs personnalisés
                ->orWhere('LOWER(cf.value) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($search) . '%'); // Ajout des % pour correspondance partielle
        }

        // Récupérer les contacts triés par nom de famille et prénom via DQL
        $contacts = $queryBuilder
            ->orderBy('c.lastName', 'ASC')  // Trier par nom de famille
            ->addOrderBy('c.firstName', 'ASC')  // Trier par prénom si les noms de famille sont identiques
            ->getQuery()
            ->getResult();

        // Compter le nombre total de résultats (sans pagination)
        $total = $this->entityManager->getRepository(Contact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Transformer les contacts en format JSON attendu
        $data = array_map(function ($contact) use ($uniqueFields) {

            $photoUrl = $contact->getPhoto() ? '/uploads/photos/' . $contact->getPhoto() : '/images/no_photo.webp';

            $customFieldsData = [];
            // Remplir les données des champs personnalisés
            foreach ($uniqueFields as $fieldName) {
                // Normaliser le nom du champ
                $normalizedFieldName = strtolower(str_replace(' ', '_', $fieldName));
                $fieldValue = '❌';  // Valeur par défaut si aucun champ n'est trouvé

                // Recherche du champ personnalisé par nom normalisé
                foreach ($contact->getCustomFields() as $customField) {
                    if (strtolower(str_replace(' ', '_', $customField->getName())) === $normalizedFieldName) {
                        $fieldValue = $customField->getValue();
                        break;
                    }
                }

                // Ajouter la valeur du champ personnalisé dans les données
                $customFieldsData[$fieldName] = $fieldValue;
            }

            $groupsHtml = '';
            foreach ($contact->getGroups() as $group) {
                $groupsHtml .= sprintf(
                    '<span class="badge border border-primary text-primary shadow-sm d-inline-flex align-items-center p-1" style="font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease;">
                        <i class="material-icons" style="font-size: 1rem; margin-right: 3px;">group</i>
                        %s
                    </span> ',
                    ucfirst($group->getName()) // Capitaliser le nom du groupe
                );
            }

            return [
                'id' => $contact->getId(),
                'firstName' => $contact->getFirstName(),
                'lastName' => $contact->getLastName(),
                'photo_name' => sprintf(
                    '<div class="d-flex align-items-center">' .
                    '<img src="%s" class="rounded-circle" width="45" height="45">' .
                    '<span class="ms-2" style="font-weight: 500;">%s %s</span>' .
                    '</div>',
                    $photoUrl,
                    $this->strToCapitalize($contact->getFirstName()),
                    $this->strToUpper($contact->getLastName())
                ),
                'phone' => $contact->getPhone(),
                'email' => $contact->getEmail(),
                'groups' => $groupsHtml,
                ...$customFieldsData,
                'actions' => sprintf(
                    '<a href="%s" class="btn btn-outline-primary me-1">
                        <span class="material-symbols-outlined fs-6">edit</span>
                    </a>
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal%d">
                        <span class="material-symbols-outlined fs-6">delete</span>
                    </button>

                    <!-- Modal de confirmation -->
                    <div class="modal fade" id="confirmDeleteModal%d" tabindex="-1" aria-labelledby="confirmDeleteModalLabel%d" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmDeleteModalLabel%d">Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Êtes-vous sûr de vouloir supprimer %s %s ?
                                </div>
                                <div class="modal-footer">
                                    <form action="%s" method="post">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </div>
                    </div>',
                    $this->generateUrl('app_contact_edit', ['id' => $contact->getId()]),  // URL pour éditer le contact
                    $contact->getId(),  // ID pour le modal
                    $contact->getId(),  // ID pour le modal
                    $contact->getId(),  // ID pour le modal
                    $contact->getId(),  // ID pour le modal
                    htmlspecialchars($contact->getFirstName(), ENT_QUOTES),  // Prénom
                    htmlspecialchars(strtoupper($contact->getLastName()), ENT_QUOTES),  // Nom de famille
                    $this->generateUrl('app_contact_delete', ['id' => $contact->getId()]),  // URL pour supprimer le contact
                )
            ];
        }, $contacts);

        // Retourner la réponse au format attendu par bootstrap-table
        return new JsonResponse([
            'total' => $total, // Total des contacts
            'rows' => $data, // Données paginées
        ]);
    }

    #[Route('/contact/add', name: 'app_contact_add', methods: ['GET', 'POST'])]
    public function add (Request $request, EntityManagerInterface  $entityManager): Response 
    {
        $contact = new Contact();

        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Pour la photo du contact
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                // Générer un nom de fichier basé sur le prénom et le nom
                $filename = strtolower($contact->getFirstname() . '_' . $contact->getLastname());
                $filename = preg_replace('/[^a-z0-9_\p{L}]/u', '_', $filename); // Nettoyer les caractères spéciaux sauf lettres accentuées
                $newFilename = $filename . '.' . $photoFile->guessExtension();
            
                // Définir le chemin du répertoire des photos (sans sous-dossiers)
                $photoDirectory = $this->getParameter('photos_directory');
            
                // Déplacer la photo directement dans le répertoire des photos
                $photoFile->move(
                    $photoDirectory, // Dossier de destination
                    $newFilename // Nom du fichier
                );
            
                // Mettre à jour la propriété "photo" avec le nom final
                $contact->setPhoto($newFilename);
            }

            // Pour les groupe du contact
            $selectedGroups = $form->get('groups')->getData();
            $newGroups = $form->get('newGroups')->getData();

            // Ajouter les groupes existants au contact
            if($selectedGroups) {
                foreach($selectedGroups as $selectedGroup) {
                    // Ajouter chaque groupe existant au contact
                    $contact->addGroup($selectedGroup);
                    $selectedGroup->addContact($contact); // Ajouter le contact au groupe
                }
            }

            // Gérer l'ajout des nouveaux groupes
            if ($newGroups) {
                foreach ($newGroups as $newGroup) {
                    // On s'assure que le groupe n'est pas déjà associé au contact
                    if (!$contact->getGroups()->contains($newGroup)) {
                        $contact->addGroup($newGroup);
                        $newGroup->addContact($contact);  // Ajouter le contact au groupe
                    }
            
                    $entityManager->persist($newGroup);
                }
            }

            // Supprimer les groupes non sélectionnés
            foreach ($selectedGroups as $group) {
                // Vérifier si le champ est marqué comme supprimé
                $isDeleted = $group->getIsDeleted() ? $group->getIsDeleted() : false;
                
                if ($isDeleted) {
                    // Utiliser la méthode removeGroup pour dissocier et supprimer
                    $contact->removeGroup($group);
                    $entityManager->remove($group); // Supprimer de la DB
                } else {
                    // Si ce n'est pas supprimé, persister les nouvelles données
                    $entityManager->persist($group);
                }
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Contact ajouté avec succès');
            return $this->redirectToRoute('app_contact_view');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('contact/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contact/edit/{id}', name: 'app_contact_edit', methods: ['GET', 'POST', 'PUT'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = $entityManager->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Contact introuvable');
        }

        $originalFirstName = $contact->getFirstname();
        $originalLastName = $contact->getLastname();

        $customFields = $contact->getCustomFields();
        $groups = $contact->getGroups();

        $formOptions = [
            'customFields' => $customFields,
            'groups' => $groups
        ];

        $form = $this->createForm(ContactType::class, $contact, $formOptions);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();

            // Définir le chemin du répertoire principal des photos
            $photoDirectory = $this->getParameter('photos_directory');

            if ($photoFile) {
                // Générer un nom de fichier basé sur le prénom et le nom
                $filename = strtolower($contact->getFirstname() . '_' . $contact->getLastname());
                $filename = preg_replace('/[^a-z0-9_\p{L}]/u', '_', $filename); // Nettoyer les caractères spéciaux sauf lettres accentuées
                $newFilename = $filename . '.' . $photoFile->guessExtension();

    
                if ($contact->getPhoto()) {
                    $oldPhotoPath = $photoDirectory . '/' . $contact->getPhoto();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath); // Supprimer le fichier photo existant
                    }
                }
    
                // Déplacer la nouvelle photo directement dans le répertoire principal des photos
                $photoFile->move(
                    $photoDirectory, // Dossier de destination
                    $newFilename // Nom du fichier
                );
    
                // Mettre à jour la propriété "photo" avec le nouveau nom de fichier
                $contact->setPhoto($newFilename);
            } else {
                // Si le nom ou prénom change, renommer l'ancienne photo
                if ($contact->getPhoto() && ($originalFirstName !== $contact->getFirstname() || $originalLastName !== $contact->getLastname())) {
                    $oldFilename = strtolower($originalFirstName . '_' . $originalLastName);
                    $oldFilename = preg_replace('/[^a-z0-9_\p{L}]/u', '_', $oldFilename); // Nettoyer les caractères spéciaux
                    $oldPhotoPath = $photoDirectory . '/' . $oldFilename . '.' . pathinfo($contact->getPhoto(), PATHINFO_EXTENSION);
    
                    $newFilename = strtolower($contact->getFirstname() . '_' . $contact->getLastname());
                    $newFilename = preg_replace('/[^a-z0-9_\p{L}]/u', '_', $newFilename); // Nettoyer les caractères spéciaux
                    $newPhotoPath = $photoDirectory . '/' . $newFilename . '.' . pathinfo($contact->getPhoto(), PATHINFO_EXTENSION);
    
                    if (file_exists($oldPhotoPath)) {
                        rename($oldPhotoPath, $newPhotoPath);
                    }
    
                    $contact->setPhoto($newFilename . '.' . pathinfo($contact->getPhoto(), PATHINFO_EXTENSION));
                }
            }

            $data = $form->get('customFields')->getData();

            // Boucle à travers les champs personnalisés soumis
            foreach ($data as $fieldData) {
                // Vérifier si le champ est marqué comme supprimé
                $isDeleted = $fieldData->getIsDeleted() ? $fieldData->getIsDeleted() : false;
                
                if ($isDeleted) {
                    // Utiliser la méthode removeCustomField pour dissocier et supprimer
                    $contact->removeCustomField($fieldData);
                    $entityManager->remove($fieldData); // Supprimer de la DB
                } else {
                    // Si ce n'est pas supprimé, persister les nouvelles données
                    $entityManager->persist($fieldData);
                }
            }

            $selectedGroups = $form->get('groups')->getData();
            $newGroups = $form->get('newGroups')->getData();

            // Ajouter les groupes existants au contact
            if($selectedGroups) {
                foreach($selectedGroups as $selectedGroup) {
                    // Ajouter chaque groupe existant au contact
                    $contact->addGroup($selectedGroup);
                    $selectedGroup->addContact($contact); // Ajouter le contact au groupe
                }
            }

            // Gérer l'ajout des nouveaux groupes
            if ($newGroups) {
                foreach ($newGroups as $newGroup) {
                    // On s'assure que le groupe n'est pas déjà associé au contact
                    if (!$contact->getGroups()->contains($newGroup)) {
                        $contact->addGroup($newGroup);
                        $newGroup->addContact($contact);  // Ajouter le contact au groupe
                    }
            
                    $entityManager->persist($newGroup);
                }
            }

            // Supprimer les groupes non sélectionnés
            foreach ($groups as $group) {
                // Vérifier si le champ est marqué comme supprimé
                $isDeleted = $group->getIsDeleted() ? $group->getIsDeleted() : false;
                
                if ($isDeleted) {
                    // Utiliser la méthode removeGroup pour dissocier et supprimer
                    $contact->removeGroup($group);
                    $entityManager->remove($group); // Supprimer de la DB
                } else {
                    // Si ce n'est pas supprimé, persister les nouvelles données
                    $entityManager->persist($group);
                }
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Contact modifié avec succès');

            return $this->redirectToRoute('app_contact_view');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
            'contact' => $contact,
        ]);
    }

    #[Route('/contact/delete/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $photoDirectory = $this->getParameter('photos_directory');
        $photoPath = $photoDirectory . '/' . $contact->getPhoto();
        $groups = $contact->getGroups();

        // Supprimer la photo si elle existe
        if ($contact->getPhoto() && file_exists($photoPath)) {
            unlink($photoPath);
        }

        // Supprimer les associations avec les groupes
        foreach ($groups as $group) {
            $group->removeContact($contact);

            // Si le groupe est maintenant vide, le supprimer également
            if (count($group->getContacts()) === 0) {
                $entityManager->remove($group);
            }
        }

        $entityManager->remove($contact);

        $entityManager->flush();

        $this->addFlash('success', 'Contact supprimé avec succès');

        return $this->redirectToRoute('app_contact_view');
    }

    #[Route('/contact/bulk-delete', name: 'app_contact_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            // Récupérer les données JSON envoyées par le front-end
            $data = json_decode($request->getContent(), true);
    
            // Vérifier que l'array d'IDs existe et n'est pas vide
            if (!isset($data['ids']) || empty($data['ids'])) {
                return $this->json(['error' => 'Aucun ID fourni'], Response::HTTP_BAD_REQUEST);
            }
    
            $ids = $data['ids'];
    
            // Récupérer les contacts avec ces IDs
            $contacts = $entityManager->getRepository(Contact::class)->findBy(['id' => $ids]);
    
            if (count($contacts) === 0) {
                return $this->json(['error' => 'Aucun contact trouvé pour les IDs spécifiés'], Response::HTTP_NOT_FOUND);
            }
    
            // Récupérer le répertoire des photos depuis les paramètres
            $photoDirectory = $this->getParameter('photos_directory');
    
            // Suppression des contacts récupérés
            foreach ($contacts as $contact) {
                // Supprimer la photo associée si elle existe
                if ($contact->getPhoto()) {
                    $photoPath = $photoDirectory . '/' . $contact->getPhoto();
                    if (file_exists($photoPath)) {
                        unlink($photoPath); // Suppression de la photo
                    }
                }
    
                // Supprimer les associations avec les groupes
                $groups = $contact->getGroups();
                foreach ($groups as $group) {
                    $group->removeContact($contact); // Retirer le contact du groupe
    
                    // Si le groupe est maintenant vide, le supprimer également
                    if (count($group->getContacts()) === 0) {
                        $entityManager->remove($group); // Supprimer le groupe s'il est vide
                    }
                }
    
                $entityManager->remove($contact);
            }
    
            $entityManager->flush();
    
            $this->addFlash('success', 'Les contacts ont été supprimés avec succès');
    
            return $this->json(['success' => true]);
    
        } catch (\Exception $e) {
            // Log ou afficher l'erreur
            return $this->json(['error' => 'Erreur de suppression: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
