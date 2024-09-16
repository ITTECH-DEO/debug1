<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Ticket;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Repository\TicketRepository;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

        // Retrieve filter parameters from the request
        $search = $request->query->get('search', '');
        $priority = $request->query->get('priority', 'all');
        $week = $request->query->get('week', 'all');
        $status = $request->query->get('status', 'all');

        $user = $this->getUser();


        // Get the repository
        $repository = $this->getDoctrine()->getRepository(Ticket::class);
        $entityManager = $this->getDoctrine()->getManager();


        // Find all tickets
        $tickets = $repository->findAll();

        // Get the EntityManager

        // Get the Doctrine connection
        $connection = $this->getDoctrine()->getConnection();

        // Define your raw SQL query
        $sql = '
            SELECT t.*, GROUP_CONCAT(u.username) AS assigned_to
            FROM tickets t
            LEFT JOIN ticket_user tu ON t.id = tu.ticket_id
            LEFT JOIN users u ON tu.user_id = u.id
            WHERE t.id NOT IN (
                SELECT ticket_id
                FROM ticket_user
                WHERE user_id = (
                    SELECT id FROM users WHERE username = ?
                )
            )
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ';

        // Prepare and execute the query
        $stmt = $connection->prepare($sql);
        $stmt->execute([$user->getUsername()]);

        // Fetch all results
        $tickets_admin = $stmt->fetchAll();


        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'tickets' => $tickets,
            'tickets_admin' => $tickets_admin
        ]);
    }
}
