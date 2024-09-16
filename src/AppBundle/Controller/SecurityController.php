<?php

// src/AppBundle/Controller/SecurityController.php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if(isset($error)){
            dump($error);die;
            $this->addFlash('error', 'Invalid credentials');
        }

        return $this->render('security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

     /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheck()
    {
        // This method is only needed for the route to exist.
        // The form login handler will intercept the request.
        throw new \Exception('This should never be reached!');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {
        // The logout handler will be called automatically by Symfony
        // when a user accesses this route
        throw new \Exception('This should never be reached!');
    }
}
