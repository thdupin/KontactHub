<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class GroupController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/group', name: 'app_group', methods: ['GET'])]
    public function index(): Response
    {
        $groups = $this->entityManager->getRepository(Group::class)->findAll();

        foreach ($groups as $group) {
            // Vérifier si le groupe n'a pas de contacts
            if ($group->getContacts()->isEmpty()) {
                $this->entityManager->remove($group);
            }
        }

        $this->entityManager->flush();

        $groups = $this->entityManager->getRepository(Group::class)->findAll();
        usort($groups, function($a, $b) {
            return strcmp($a->getName(), $b->getName());  // Trie par nom
        });

        return $this->render('group/index.html.twig', [
            "groups" => $groups,
        ]);
    }

    #[Route('/group/edit/{id}', name: 'app_group_edit')]
    public function editGroup(Group $group, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persiste les modifications dans la base de données
            $entityManager->flush();
    
            $this->addFlash('success', 'Le groupe a été modifié avec succès.');
    
            return $this->redirectToRoute('app_group');
        }

        return $this->render('group/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
