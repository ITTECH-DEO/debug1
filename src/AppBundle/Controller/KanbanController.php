<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KanbanController extends Controller
{
    /**
     * @Route("/kanban", name="kanban")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        // Set items per page
        $itemsPerPage = 3;

        // Get current page numbers from request or default to 1
        $pageCreated = $request->query->getInt('created_page', 1);
        $pageInProgress = $request->query->getInt('in_progress_page', 1);
        $pageDone = $request->query->getInt('done_page', 1);
        $pageValidated = $request->query->getInt('validated_page', 1);
        $pageRejected = $request->query->getInt('rejected_page', 1);

        // Fetch tickets for each status with pagination
        $created = $this->fetchTickets('Created', $pageCreated, $itemsPerPage);
        $inProgress = $this->fetchTickets('In Progress', $pageInProgress, $itemsPerPage);
        $done = $this->fetchTickets('Done', $pageDone, $itemsPerPage);
        $validated = $this->fetchTickets('Validated', $pageValidated, $itemsPerPage);
        $rejected = $this->fetchTickets('Rejected', $pageRejected, $itemsPerPage);

        // Count total tickets for each status
        $totalCreated = $this->countTickets('Created');
        $totalInProgress = $this->countTickets('In Progress');
        $totalDone = $this->countTickets('Done');
        $totalValidated = $this->countTickets('Validated');
        $totalRejected = $this->countTickets('Rejected');

        // Calculate total pages for each status
        $totalPagesCreated = ceil($totalCreated / $itemsPerPage);
        $totalPagesInProgress = ceil($totalInProgress / $itemsPerPage);
        $totalPagesDone = ceil($totalDone / $itemsPerPage);
        $totalPagesValidated = ceil($totalValidated / $itemsPerPage);
        $totalPagesRejected = ceil($totalRejected / $itemsPerPage);

        // Pass data to the view
        return $this->render('kanban/index.html.twig', [
            'columns' => [
                'created' => $created,
                'in_progress' => $inProgress,
                'done' => $done,
                'validated' => $validated,
                'rejected' => $rejected,
            ],
            'total_pages_created' => $totalPagesCreated,
            'total_pages_in_progress' => $totalPagesInProgress,
            'total_pages_done' => $totalPagesDone,
            'total_pages_validated' => $totalPagesValidated,
            'total_pages_rejected' => $totalPagesRejected,
            'current_page_created' => $pageCreated,
            'current_page_in_progress' => $pageInProgress,
            'current_page_done' => $pageDone,
            'current_page_validated' => $pageValidated,
            'current_page_rejected' => $pageRejected,
        ]);
    }


    /**
     * @Route("/kanban/update-status", name="update_ticket_status")
     * @Method("POST")
     */
    public function updateTicketStatus(Request $request, EntityManagerInterface $entityManager)
    {
        // Get data from the POST request
        $id = $request->request->get('id');
        $status = $request->request->get('status');

        // Validate the inputs
        if (empty($id) || empty($status)) {
            return new Response("Error: Invalid ticket ID or status!", Response::HTTP_BAD_REQUEST);
        }

        // Fetch the ticket by ID
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            return new Response("Error: Ticket not found!", Response::HTTP_NOT_FOUND);
        }

        // Update the ticket status
        $ticket->setStatus($status);

        // Persist the changes to the database
        try {
            $entityManager->persist($ticket);
            $entityManager->flush();
            return new Response("Status updated successfully", Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response("Error updating status: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function fetchTickets($status, $page, $itemsPerPage)
    {
        $offset = ($page - 1) * $itemsPerPage;

        // Use Doctrine's query builder to fetch paginated tickets with comments count
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('t, COUNT(c.id) as comments_count')
            ->from('AppBundle:Ticket', 't')
            ->leftJoin('t.comments', 'c')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->groupBy('t.id')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    private function countTickets($status)
    {
        // Use Doctrine's query builder to count the total tickets with a specific status
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from('AppBundle:Ticket', 't')
            ->where('t.status = :status')
            ->setParameter('status', $status);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
