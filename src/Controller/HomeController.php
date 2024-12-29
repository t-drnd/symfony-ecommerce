<?php

namespace App\Controller;

use App\Entity\Produits;
use App\Form\ProduitAddType;
use App\Repository\ProduitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProduitsRepository $produitsRepository): Response
    {
        // Récupère tous les produits depuis la base de données
        $produits = $produitsRepository->findAll();

        // Rend la vue avec les produits
        return $this->render('home/index.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    #[Route(path: '/add', name: 'app_produit_add')]
    public function addProduct(Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $produit = new Produits();
        $form = $this->createForm(ProduitAddType::class, $produit);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $imageFile = $form->get('photo')->getData();
 
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
 
                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', $translator->trans('error.image'));
                    return $this->redirectToRoute('app_home');
                }
 
                $produit->setPhoto($newFilename);
            }
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', $translator->trans('success.produit_added'));
            return $this->redirectToRoute('app_home');
        }
        return $this->render('produit/add.html.twig', [
            'form' => $form->createView(),
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

