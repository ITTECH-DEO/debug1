<?php
// src/AppBundle/Controller/TicketController.php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Ticket;
use AppBundle\Entity\User;
use AppBundle\Form\CommentType as FormCommentType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class TicketController extends Controller
{
    /**
     * @Route("/tickets", name="ticket_list")
     * @Method("GET")
     */
    public function listAction()
    {
        // Get the Doctrine EntityManager
        $em = $this->getDoctrine()->getManager();


        // Fetch all tickets
        $tickets = $em->getRepository(Ticket::class)->findAll();

        // die(var_dump($tickets));

        // Render the view and pass the tickets data
        return $this->render('ticket/list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * @Route("/tickets/assign", name="assign_tickets")
     * @Method({"GET", "POST"})
     */
    public function assignTickets(Request $request, EntityManagerInterface $em, SessionInterface $session)
    {
        // Check if user is an admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        // Retrieve form data from POST request
        $selectedTickets = $request->request->get('selected_tickets', []);
        $adminUsername = $request->request->get('admin_username', '');

        // Start the session to store flash messages
        $session = new Session();

        if (!empty($selectedTickets) && !empty($adminUsername)) {
            $em = $this->getDoctrine()->getManager();
            $userRepository = $em->getRepository('AppBundle:User');

            // Fetch the admin user entity by username
            $adminUser = $userRepository->findOneBy(['username' => $adminUsername]);

            if ($adminUser) {
                foreach ($selectedTickets as $ticketId) {
                    // Assign ticket to admin user
                    $ticketUserStmt = $em->getConnection()->prepare(
                        "INSERT INTO ticket_user (ticket_id, user_id) 
                        SELECT :ticket_id, u.id 
                        FROM users u 
                        WHERE u.username = :admin_username"
                    );
                    $ticketUserStmt->execute(['ticket_id' => $ticketId, 'admin_username' => $adminUsername]);

                    // Update the ticket status
                    $ticketStmt = $em->getConnection()->prepare(
                        "UPDATE tickets 
                        SET status = 'In Progress', assigned_to = :admin_username, reviewed_by = :admin_username 
                        WHERE id = :ticket_id"
                    );
                    $ticketStmt->execute([
                        'admin_username' => $adminUsername,
                        'ticket_id' => $ticketId
                    ]);
                }

                // Flash success message
                $session->getFlashBag()->add('success', "Tickets successfully assigned to $adminUsername!");
            } else {
                // Flash error message if admin user is not found
                $session->getFlashBag()->add('error', "Admin user not found.");
            }
        } else {
            // Flash error message if no tickets or admin username is provided
            $session->getFlashBag()->add('error', "No tickets selected or admin username is missing.");
        }

        // Redirect back to the ticket list page or some other page
        return $this->redirectToRoute('homepage'); 
    }

    /**
     * @Route("/ticket/create", name="ticket_create")
     */
    public function createAction(Request $request)
    {
        // Get POST parameters
        $department = $request->request->get('department', '');
        $type = $request->request->get('type', '');
        $priority = $request->request->get('priority', '');
        $title = $request->request->get('title', '');
        $content = $request->request->get('content', '');
        $createdBy = $this->getUser() ? $this->getUser()->getUsername() : 'Anonymous';
        $reviewedBy = '';
        $status = 'Created';

        // Handle image upload
        $imagePath = '';
        $file = $request->files->get('image');

        if ($file instanceof UploadedFile) {
        // die(dump($file));
        // die(dump($this->getParameter('uploads_directory')));

            try {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('uploads_directory'), $fileName);
                // $file->move($this->getParameter('kernel.project_dir').'/web/uploads', $fileName);
                $imagePath = $fileName;
            } catch (FileException $e) {
                // Handle the error (e.g., log it or set an error message)
                $this->addFlash('error', 'Failed to upload image.');
                die(dump($e));
                return $this->redirectToRoute('ticket_create');
            }
        }

        // Validation
        if (empty($title) || empty($content)) {
            $this->addFlash('error', 'Title and content cannot be empty!');
            return $this->redirectToRoute('ticket_create');
        }

        // Save to the database
        $em = $this->getDoctrine()->getManager();
        $ticket = new Ticket();
        $ticket->setDepartment($department);
        $ticket->setType($type);
        $ticket->setPriority($priority);
        $ticket->setTitle($title);
        $ticket->setContent($content);
        $ticket->setCreatedBy($createdBy);
        $ticket->setReviewedBy($reviewedBy);
        $ticket->setStatus($status);
        $ticket->setImage("uploads/".$imagePath);
        $ticket->setCreatedAt(new \DateTime());
        $ticket->setUpdatedAt(new \DateTime());


        $em->persist($ticket);
        $em->flush();

        // Redirect back to the form with a success message
        $this->addFlash('success', 'Ticket created successfully!');
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/ticket/{id}/detail", name="ticket_detail")
     */
    public function ticketDetailAction($id, Request $request)
    {
        // // Create the comment form
        // $commentForm = $this->createForm(FormCommentType::class);
        // $commentForm->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        // Mengambil data tiket berdasarkan ID
        $ticket = $em->getRepository(Ticket::class)->find($id);

        // die(dump($ticket));

        // if (!$ticket) {
        //     throw $this->createNotFoundException('Ticket not found.');
        // }

        // Handle image upload
        


        $isowner = false;
        if ($this->getUser() && $this->getUser()->getUsername() == $ticket->getCreatedBy()) {
            $isowner = true;
        }

        // Mengambil data komentar
        $comments = $em->getRepository(Comment::class)->findBy(
            ['ticket' => $ticket],
            ['createdAt' => 'DESC']
        );

        return $this->render('ticket/detail.html.twig', [
            'ticket' => $ticket,
            'comments' => $comments,
            // 'commentForm' => $commentForm->createView(),
            'isowner' => $isowner,
        ]);
    }

    /**
     * @Route("/ticket/update/{id}", name="update_ticket", methods={"POST"})
     */
    public function updateTicketAction($id,Request $request, EntityManagerInterface $em)
    {

        // dump($request->request->all());
        // die();
        // Get the form data from the request
        $id = $request->request->get('id', '');
        $department = $request->request->get('department', '');
        $type = $request->request->get('type', '');
        $priority = $request->request->get('priority', '');
        $title = trim($request->request->get('title', ''));
        $content = trim($request->request->get('content', ''));

        // Validate the data
        if (empty($title) || empty($content)) {
            $this->addFlash('error', 'Title and content cannot be empty!');
            return $this->redirectToRoute('ticket_edit', ['id' => $id]);
        }

        // Find the ticket entity by ID
        $ticket = $em->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            // throw $this->createNotFoundException('Ticket not found');
        }

        $imagePath = $ticket->getImage();
        $file = $request->files->get('image');

        if ($file instanceof UploadedFile) {
        // die(dump($file));
        // die(dump($this->getParameter('uploads_directory')));

            try {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('uploads_directory'), $fileName);
                // $file->move($this->getParameter('kernel.project_dir').'/web/uploads', $fileName);
                $imagePath = "uploads/".$fileName;
            } catch (FileException $e) {
                // Handle the error (e.g., log it or set an error message)
                $this->addFlash('error', 'Failed to upload image.');
                die(dump($e));
                return $this->redirectToRoute('ticket_create');
            }
        }

        // Update the ticket entity
        $ticket->setDepartment($department);
        $ticket->setType($type);
        $ticket->setPriority($priority);
        $ticket->setTitle($title);
        $ticket->setContent($content);
        $ticket->setImage($imagePath);

        // Persist changes to the database
        $em->flush();

        // Redirect to the ticket detail page
        $this->addFlash('success', 'Ticket created successfully!');
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/ticket/delete/{id}", name="delete_ticket", methods={"POST"})
     */
    public function deleteTicket(Request $request, EntityManagerInterface $em, $id)
    {
        // Find the ticket entity by ID
        $ticket = $em->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            $this->addFlash('error', 'Ticket not found.');
            return $this->redirectToRoute('ticket_list'); // Adjust the route name as needed
        }

        // Remove the ticket entity
        $em->remove($ticket);
        $em->flush();

        // Redirect to the tickets list page
        $this->addFlash('success', 'Ticket deleted successfully.');
        return $this->redirectToRoute('homepage'); // Adjust the route name as needed
    }

    /**
     * @Route("/ticket/comment/{id}", name="add_comment", methods={"POST"})
     */
    public function addComment(Request $request, EntityManagerInterface $em, $id)
    {
        $ticketId = $request->request->get('ticket_id');
        $name = $request->request->get('name');
        $commentContent = $request->request->get('comment');
        $imagePath = null;

        // Extract image path if present
        if (preg_match('/<img.*?src=["\'](.*?)["\'].*?>/i', $commentContent, $matches)) {
            $imagePath = $matches[1]; // Save the first image path found
            $commentContent = preg_replace('/<img.*?src=["\'](.*?)["\'].*?>/i', '', $commentContent); // Remove image from comment content
        }

        // Find the ticket entity by ID
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            $this->addFlash('error', 'Ticket not found.');
            return $this->redirect($request->headers->get('referer')); // Redirect back to the previous page
        }

        // Create and save the comment entity
        $comment = new Comment();
        $comment->setTicket($ticket);
        $comment->setName($name);
        $comment->setComment($commentContent);
        $comment->setImage($imagePath);
        $comment->setCreatedAt(new \DateTime());

        $em->persist($comment);
        $em->flush();

        // Redirect back to the previous page
        $this->addFlash('success', 'Comment added successfully.');
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/ticket/filter", name="filter_ticket", methods={"GET"})
     */
    public function filterTicketAction(Request $request, EntityManagerInterface $em)
    {
        $searchText = $request->query->get('search', '');
        $selectedDepartment = strtolower($request->query->get('department', 'all'));
        $selectedPriority = strtolower($request->query->get('priority', 'all'));
        $selectedStatus = strtolower($request->query->get('status', 'all'));
        $selectedType = strtolower($request->query->get('type', 'all'));
        $selectedResponsible = strtolower($request->query->get('responsible', 'all'));
        $selectedWeek = strtolower($request->query->get('week', 'all'));

        $qb = $em->createQueryBuilder();
        $qb->select('t')
            ->from('AppBundle:Ticket', 't')
            ->where('1 = 1');

        // Apply search filters
        if (!empty($searchText)) {
            $qb->andWhere('t.title LIKE :searchText')
               ->setParameter('searchText', '%' . $searchText . '%');
        }

        if ($selectedDepartment !== 'all') {
            $qb->andWhere('LOWER(t.department) = :department')
               ->setParameter('department', $selectedDepartment);
        }

        if ($selectedPriority !== 'all') {
            $qb->andWhere('LOWER(t.priority) = :priority')
               ->setParameter('priority', $selectedPriority);
        }

        if ($selectedStatus !== 'all') {
            $qb->andWhere('LOWER(t.status) = :status')
               ->setParameter('status', $selectedStatus);
        }

        if ($selectedType !== 'all') {
            $qb->andWhere('LOWER(t.type) = :type')
               ->setParameter('type', $selectedType);
        }

        if ($selectedResponsible !== 'all') {
            $qb->andWhere('LOWER(t.assignedTo) = :responsible')
               ->setParameter('responsible', $selectedResponsible);
        }

        if ($selectedWeek !== 'all') {
            switch ($selectedWeek) {
                case 'week-1':
                    $startOfWeek = (new \DateTime())->modify('first day of this month')->format('Y-m-d');
                    $endOfWeek = (new \DateTime($startOfWeek))->modify('+6 days')->format('Y-m-d');
                    break;
                case 'week-2':
                    $startOfWeek = (new \DateTime())->modify('first day of this month')->modify('+7 days')->format('Y-m-d');
                    $endOfWeek = (new \DateTime($startOfWeek))->modify('+6 days')->format('Y-m-d');
                    break;
                case 'week-3':
                    $startOfWeek = (new \DateTime())->modify('first day of this month')->modify('+14 days')->format('Y-m-d');
                    $endOfWeek = (new \DateTime($startOfWeek))->modify('+6 days')->format('Y-m-d');
                    break;
                case 'week-4':
                    $startOfWeek = (new \DateTime())->modify('first day of this month')->modify('+21 days')->format('Y-m-d');
                    $endOfWeek = (new \DateTime())->format('Y-m-t'); // Last day of the month
                    break;
            }
            $qb->andWhere('t.createdAt BETWEEN :startOfWeek AND :endOfWeek')
               ->setParameter('startOfWeek', $startOfWeek)
               ->setParameter('endOfWeek', $endOfWeek);
        }

        $qb->orderBy('t.createdAt', 'DESC');
        $tickets = $qb->getQuery()->getResult();

        if (count($tickets) > 0) {
            // Render the ticket partials
            $responseContent = $this->renderView('ticket/_ticket_list.html.twig', [
                'tickets' => $tickets,
            ]);
        } else {
            $responseContent = "<p>No tickets found.</p>";
        }

        return new Response($responseContent);
    }
}
