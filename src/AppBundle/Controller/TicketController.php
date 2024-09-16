<?php
// src/AppBundle/Controller/TicketController.php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Ticket;
use AppBundle\Form\CommentType as FormCommentType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

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
    public function assignTickets(Request $request)
    {
        // Check if user is an admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        // Handle the form submission logic
        $selectedTickets = $request->request->get('selected_tickets', []);
        $adminUsername = $request->request->get('admin_username');

        // Process the selected tickets...
        // Logic to assign the tickets

        return $this->redirectToRoute('ticket_list');
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
            try {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('uploads_directory'), $fileName);
                $imagePath = $fileName;
            } catch (FileException $e) {
                // Handle the error (e.g., log it or set an error message)
                $this->addFlash('error', 'Failed to upload image.');
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
        $ticket->setImage($imagePath);
        $ticket->setCreatedAt(new \DateTime());
        $ticket->setUpdatedAt(new \DateTime());


        $em->persist($ticket);
        $em->flush();

        // Redirect back to the form with a success message
        $this->addFlash('success', 'Ticket created successfully!');
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/ticket/{id}", name="ticket_detail")
     */
    public function ticketDetailAction($id, Request $request)
    {
        // // Create the comment form
        // $commentForm = $this->createForm(FormCommentType::class);
        // $commentForm->handleRequest($request);

        $previousUrl = $this->get('request_stack')->getMasterRequest()->headers->get('referer');

        $em = $this->getDoctrine()->getManager();

        // Mengambil data tiket berdasarkan ID
        $ticket = $em->getRepository(Ticket::class)->find($id);

        // die(dump($ticket));

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found.');
        }

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
            'previousUrl' => $previousUrl,
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

        // Update the ticket entity
        $ticket->setDepartment($department);
        $ticket->setType($type);
        $ticket->setPriority($priority);
        $ticket->setTitle($title);
        $ticket->setContent($content);

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
}
