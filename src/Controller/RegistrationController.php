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
use Symfony\Component\Security\Core\Security;
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
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Set the default role for the user
            $user->setRoles(['ROLE_USER']);

            // Persist the new user into the database
            $entityManager->persist($user);
            $entityManager->flush();

            // Log the user in immediately after registration
            $this->loginUser($user, $security);

            // Redirect to a secure page (like home or dashboard)
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function loginUser(User $user, Security $security)
    {
        // Log the user in after registration
        $token = $security->getToken();
        $tokenStorage = $this->get('security.token_storage');
        $tokenStorage->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
    }
}
