<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Produits;
use App\Form\ProduitAddType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[Route('/fiche/produit/delete/{id}', name: 'app_produit_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, EntityManagerInterface $em, Produits $produit = null): Response
    {
        if (!$produit) {
            throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('csrf_token'))) {
            $contenuPaniers = $em->getRepository(ContenuPanier::class)->findBy(['Produit' => $produit]);
            if($contenuPaniers) {
                foreach($contenuPaniers as $contenuPanier) {
                    $em -> remove($contenuPanier);
                }

                $em-> flush();
            }

            $em -> remove($produit);
            $em -> flush();
        }

        $this->addFlash('success', 'Le produit a été supprimé avec succès.');

        return $this -> redirectToRoute('app_home');
    }

    public function removeImage(string $imageName): void
    {
        $imagePath = $this->getParameter('upload_directory') . '/' . $imageName;

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }


    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    #[Route('/fiche/produit/modify/{id}', name: 'app_produit_modify', requirements: ['id' => '\d+'])]
    public function modify(Request $request, EntityManagerInterface $em, Produits $produit = null): Response
    {
        if (!$produit) {
            throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
        }

        $form = $this->createForm(ProduitAddType::class, $produit);
        $form->handleRequest($request);

        // Sauvegarde de l'image existante
        $currentImage = $produit->getPhoto();

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le fichier image téléchargé
            $imageFile = $form->get('photo')->getData();

            if ($imageFile) {
                // Supprimer l'ancienne image si une nouvelle image est téléchargée
                if ($currentImage) {
                    $this->removeImage($currentImage);  // Suppression de l'ancienne image
                }

                // Gérer la nouvelle image (enregistrer le fichier sur le serveur et mettre à jour la base de données)
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                    $produit->setPhoto($newFilename);  // Mise à jour du nom de l'image dans la base de données
                } catch (FileException $e) {
                    return $this->redirectToRoute('app_produit');
                }
            }

            $em->flush();
            return $this->redirectToRoute('app_fiche_produit', ['id' => $produit->getId()]);
        }

        $this->addFlash('success', 'Le produit a été mis à jour avec succès.');

        return $this->render('fiche_produit/modify.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }
}
