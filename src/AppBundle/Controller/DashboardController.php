<?php
// src/Controller/DashboardController.php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index()
    {
        // Check if the user is logged in
        if (!$this->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Access Denied.');
        }

        // Render the dashboard view
        return $this->render('dashboard/index.html.twig');
    }
}
