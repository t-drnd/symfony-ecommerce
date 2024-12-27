<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitsRepository;

class FicheProduitController extends AbstractController
{
    #[Route('/fiche/produit/{id}', name: 'app_fiche_produit', requirements: ['id' => '\d+'])]
    public function index(int $id, ProduitsRepository $produitsRepository): Response
    {
        // Récupérer le produit directement avec le repository
        $produit = $produitsRepository->find($id);

        // Si le produit n'existe pas, retourner une erreur 404
        if (!$produit) {
            throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
        }

        // Rendre la vue avec les informations du produit
        return $this->render('fiche_produit/index.html.twig', [
            'controller_name' => 'FicheProduitController',
            'produit' => $produit,
        ]);
    }
}
