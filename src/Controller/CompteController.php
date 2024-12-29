<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Repository\PanierRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CompteController extends AbstractController
{
    #[Route('/compte', name: 'app_compte')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $paniers = $em->getRepository(Panier::class)->findBy(['User' => $user]);

        $paniersEnCours = [];
        $anciensPaniers = [];

        foreach ($paniers as $panier) {
            if ($panier->isPurchaseState()) {
                $anciensPaniers[] = $panier; 
            } else {
                $paniersEnCours[] = $panier;
            }
        }

        return $this->render('compte/index.html.twig', [
            'User' => $user,
            'paniersEnCours' => $paniersEnCours,
            'anciensPaniers' => $anciensPaniers,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/compte/maxiadmin', name: 'app_super_admin')]
    public function superAdmin(EntityManagerInterface $em, UserRepository $userRepository): Response
{
    // Récupérer les paniers non achetés
    $paniersNonAchetes = $em->getRepository(Panier::class)->findBy(['purchase_state' => false]);

    // Récupérer les utilisateurs inscrits aujourd'hui
    $dateToday = new \DateTime('today');
    $utilisateursAujourdHui = $userRepository->findByRegistrationDate($dateToday); // Utilisation de $dateToday ici

    // Tri des utilisateurs du plus récent au plus ancien
    usort($utilisateursAujourdHui, function($a, $b) {
        return $b->getCreationDate()->getTimestamp() - $a->getCreationDate()->getTimestamp();
    });

    return $this->render('compte/super_admin.html.twig', [
        'paniersNonAchetes' => $paniersNonAchetes,
        'utilisateursAujourdHui' => $utilisateursAujourdHui,
    ]);
}

}
