<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obtention du mot de passe en clair
            $plainPassword = $form->get('plainPassword')->getData();

            // Hacher le mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Définir le rôle par défaut pour l'utilisateur
            $user->setRoles(['ROLE_USER']);

            // Persister l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Connecter l'utilisateur après l'inscription
            $this->loginUser($user, $request, $security);

            // Rediriger vers une page sécurisée
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('success', 'Le compte a été créé avec succès.');

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get login errors, if any
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    private function loginUser(User $user, Request $request, Security $security)
    {
        // Récupérer le token actuel (peut être vide pour un nouvel utilisateur)
        $token = $security->getToken();

        // Stocker le token dans la session via la requête
        $session = $request->getSession();
        $session->set('_security_main', serialize($token));
    }
}
