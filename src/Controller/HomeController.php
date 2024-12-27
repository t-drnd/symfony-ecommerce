<?php

namespace App\Controller;

use App\Repository\ProduitsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(ProduitsRepository $produitsRepository): Response
    {
        // Récupère tous les produits depuis la base de données
        $produits = $produitsRepository->findAll();

        // Rend la vue avec les produits
        return $this->render('home/index.html.twig', [
            'produits' => $produits,
        ]);
    }
}


//namespace App\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Attribute\Route;

//class HomeController extends AbstractController
//{
//    #[Route('/home', name: 'app_home')]
//    public function index(): Response
//    {
//        return $this->render('home/index.html.twig', [
//            'controller_name' => 'HomeController',
//        ]);
//    }
//}

