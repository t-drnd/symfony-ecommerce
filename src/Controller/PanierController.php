<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProduitsRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(SessionInterface $session, ProduitsRepository $produitsRepository): Response
    {
        // Récupère le panier depuis la session
        $panier = $session->get('panier', []);
        
        // Préparer les détails des produits pour affichage
        $panierWithDetails = [];
        $total = 0;

        foreach ($panier as $id => $quantity) {
            $produit = $produitsRepository->find($id);
            if ($produit) {
                $panierWithDetails[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                ];
                $total += $produit->getPrice() * $quantity;
            }
        }

        return $this->render('panier/index.html.twig', [
            'items' => $panierWithDetails,
            'total' => $total,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'app_panier_add')]
    public function add($id, SessionInterface $session): RedirectResponse
    {
        // Récupère le panier depuis la session
        $panier = $session->get('panier', []);

        // Ajoute ou met à jour la quantité pour le produit
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }

        // Enregistre le panier dans la session
        $session->set('panier', $panier);

        // Redirige vers la page panier
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/remove/{id}', name: 'app_panier_remove')]
    public function remove($id, SessionInterface $session): RedirectResponse
    {
        // Récupère le panier depuis la session
        $panier = $session->get('panier', []);

        // Supprime le produit du panier si présent
        if (!empty($panier[$id])) {
            unset($panier[$id]);
        }

        // Enregistre le panier dans la session
        $session->set('panier', $panier);

        // Redirige vers la page panier
        return $this->redirectToRoute('app_panier');
    }
}
