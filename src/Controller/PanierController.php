<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\PanierItem;
use App\Repository\ProduitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $em->getRepository(Panier::class)->findOneBy(['User' => $user, 'purchase_state' => false]);

        $total = 0;
        $panierWithDetails = [];

        if ($panier) {
            foreach ($panier->getContenuPaniers() as $item) {
                $panierWithDetails[] = [
                    'produit' => $item->getProduit(),
                    'quantity' => $item->getQuantity(),
                ];
                $total += $item->getProduit()->getPrice() * $item->getQuantity();
            }
        }

        return $this->render('panier/index.html.twig', [
            'items' => $panierWithDetails,
            'total' => $total,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'app_panier_add')]
    public function add($id, ProduitsRepository $produitsRepository, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $produit = $produitsRepository->find($id);

        if (!$produit) {
            return $this->redirectToRoute('app_panier');
        }

        $panier = $em->getRepository(Panier::class)->findOneBy(['User' => $user, 'purchase_state' => false]);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($user);
            $panier->setPurchaseState(false);
            $panier->setPurchaseDate(new \DateTime());
            $em->persist($panier);
        }

        $panierItem = $panier->getContenuPaniers()->filter(fn($item) => $item->getProduit() === $produit)->first();

        if ($panierItem) {
            $panierItem->setQuantity($panierItem->getQuantity() + 1);
        } else {
            $panierItem = new ContenuPanier();
            $panierItem->setProduit($produit);
            $panierItem->setQuantity(1);
            $panierItem->setPanier($panier);
            $panierItem->setDate(new \DateTime());

            $em->persist($panierItem);
        }

        $em->flush();

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/remove/{id}', name: 'app_panier_remove')]
    public function remove($id, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $em->getRepository(Panier::class)->findOneBy(['User' => $user, 'purchase_state' => false]);

        if ($panier) {
            $panierItem = $panier->getContenuPaniers()->filter(fn($item) => $item->getProduit()->getId() == $id)->first();

            if ($panierItem) {
                $em->remove($panierItem);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/paiement', name: 'payer_panier')]
    public function paiement(EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $panier = $em->getRepository(Panier::class)->findOneBy(['User' => $user, 'purchase_state' => false]);

        if ($panier) {
            $panier->setPurchaseState(true);
            $panier->setPurchaseDate(new \DateTime());

            $em->persist($panier);
            $em->flush();
        }

        return $this->redirectToRoute('app_home');
    }
}
