<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{
    /**
     * Method used in order to allow a user to register on our website
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em)
    {
        // First of all, we check that the user who wants to register is not already registered and logged
        // Otherwise, we redirect him on the homepage
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // We create a new User Object
        $user = new User();
        
        // We use our UserType form
        $form = $this->createForm(UserType::class, $user);

        // The form will inspect the request
        $form->handleRequest($request);

        // We check if the form is well submitted and valid
        // If it's the case, we encode the user's password and flush the user into the database
        // After the successfull registration, we had a flash message with a link to the login page and a redirection to the homepage
        if ($form->isSubmitted() && $form->isValid()) {

            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            $loginUrl = $this->generateUrl('app_login');

            $this->addFlash('success', sprintf('Your account has been well registered, you can now <a href="%s">log in</a>.', $loginUrl));

            return $this->redirectToRoute('home');
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
