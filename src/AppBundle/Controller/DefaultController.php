<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Ticket;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;

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

        $em = $this->getDoctrine()->getManager();
        $ticketRepository = $em->getRepository('AppBundle:Ticket');

        // Get filter parameters from the request
        $selectedDepartment = $request->query->get('department', 'all');
        $selectedPriority = $request->query->get('priority', 'all');
        $selectedStatus = $request->query->get('status', 'all');
        $selectedType = $request->query->get('type', 'all');
        $selectedResponsible = $request->query->get('responsible', 'all');
        $searchText = $request->query->get('search', '');

        // Build the query with Doctrine QueryBuilder
        $qb = $ticketRepository->createQueryBuilder('t');

        if ($selectedDepartment !== 'all') {
            $qb->andWhere('t.department = :department')
               ->setParameter('department', $selectedDepartment);
        }
        if ($selectedPriority !== 'all') {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $selectedPriority);
        }
        if ($selectedStatus !== 'all') {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $selectedStatus);
        }
        if ($selectedType !== 'all') {
            $qb->andWhere('t.type = :type')
               ->setParameter('type', $selectedType);
        }
        if ($selectedResponsible !== 'all') {
            $qb->andWhere('t.assignedTo = :assigned_to')
               ->setParameter('responsible', $selectedResponsible);
        }
        if (!empty($searchText)) {
            $qb->andWhere('t.title LIKE :search OR t.content LIKE :search')
               ->setParameter('search', '%' . $searchText . '%');
        }

        // die(dump($qb->getQuery()->getSQL()));

        // Get the tickets based on the filters
        $tickets = $qb->getQuery()->getResult();

        $ticket_new = $qb->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $tickets_ongoing = $qb->andWhere('t.status = :status')
            ->setParameter('status', 'In Progress')
            ->getQuery()
            ->getResult();

        $ticket_resolved = $qb->andWhere('t.status = :status')
            ->setParameter('status', 'Validated')
            ->getQuery()
            ->getResult();

        $ticket_my = $qb->andWhere('t.assignedTo = :assignedTo')
            ->setParameter('assignedTo', $user->getUsername())
            ->getQuery()
            ->getResult();


        // Get filter data for dropdowns
        $departments = $ticketRepository->createQueryBuilder('t')
            ->select('DISTINCT t.department')
            ->getQuery()
            ->getResult();

        $priorities = $ticketRepository->createQueryBuilder('t')
            ->select('DISTINCT t.priority')
            ->getQuery()
            ->getResult();

        $statuses = $ticketRepository->createQueryBuilder('t')
            ->select('DISTINCT t.status')
            ->getQuery()
            ->getResult();

        $types = $ticketRepository->createQueryBuilder('t')
            ->select('DISTINCT t.type')
            ->getQuery()
            ->getResult();

        $responsibles = $ticketRepository->createQueryBuilder('t')
            ->select('DISTINCT t.assignedTo')
            ->where('t.assignedTo IS NOT NULL')
            ->getQuery()
            ->getResult();

        // Get the repository
        // $repository = $this->getDoctrine()->getRepository(Ticket::class);
        // $entityManager = $this->getDoctrine()->getManager();


        // Find all tickets
        // $tickets = $repository->findAll();

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
            'tickets_admin' => $tickets_admin,
            'tickets' => $tickets,
            'ticket_new' => $ticket_new,
            'tickets_ongoing' => $tickets_ongoing,
            'ticket_resolved' => $ticket_resolved,
            'ticket_my' => $ticket_my,
            'departments' => $departments,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'types' => $types,
            'responsibles' => $responsibles,
            'selected_department' => $selectedDepartment,
            'selected_priority' => $selectedPriority,
            'selected_status' => $selectedStatus,
            'selected_type' => $selectedType,
            'selected_responsible' => $selectedResponsible,
            'search_text' => $searchText,
        ]);
    }

    /**
     * @Route("/load-tab-content", name="load_tab_content")
     */
    public function loadTabContent(Request $request, EntityManagerInterface $em)
    {
        // Get the filters from the request
        $searchText = $request->query->get('search', '');
        $selectedDepartment = strtolower($request->query->get('department', 'all'));
        $selectedPriority = strtolower($request->query->get('priority', 'all'));
        $selectedStatus = strtolower($request->query->get('status', 'all'));
        $selectedType = strtolower($request->query->get('type', 'all'));
        $selectedResponsible = strtolower($request->query->get('responsible', 'all'));
        $selectedWeek = strtolower($request->query->get('week', 'all'));

        // Build the query
        $queryBuilder = $em->getRepository(Ticket::class)->createQueryBuilder('t');
        $queryBuilder->where('1=1');

        if (!empty($searchText)) {
            $queryBuilder->andWhere('t.title LIKE :search')
                ->setParameter('search', '%' . $searchText . '%');
        }

        if ($selectedDepartment !== 'all') {
            $queryBuilder->andWhere('LOWER(t.department) = :department')
                ->setParameter('department', $selectedDepartment);
        }

        if ($selectedPriority !== 'all') {
            $queryBuilder->andWhere('LOWER(t.priority) = :priority')
                ->setParameter('priority', $selectedPriority);
        }

        if ($selectedStatus !== 'all') {
            $queryBuilder->andWhere('LOWER(t.status) = :status')
                ->setParameter('status', $selectedStatus);
        }

        if ($selectedType !== 'all') {
            $queryBuilder->andWhere('LOWER(t.type) = :type')
                ->setParameter('type', $selectedType);
        }

        if ($selectedResponsible !== 'all') {
            $queryBuilder->andWhere('LOWER(t.assignedTo) = :responsible')
                ->setParameter('responsible', $selectedResponsible);
        }

        if ($selectedWeek !== 'all') {
            $startOfWeek = null;
            $endOfWeek = null;

            switch ($selectedWeek) {
                case 'week-1':
                    $startOfWeek = new \DateTime('first day of this month');
                    $endOfWeek = new \DateTime('first day of this month + 6 days');
                    break;
                case 'week-2':
                    $startOfWeek = new \DateTime('first day of this month + 7 days');
                    $endOfWeek = new \DateTime('first day of this month + 13 days');
                    break;
                case 'week-3':
                    $startOfWeek = new \DateTime('first day of this month + 14 days');
                    $endOfWeek = new \DateTime('first day of this month + 20 days');
                    break;
                case 'week-4':
                    $startOfWeek = new \DateTime('first day of this month + 21 days');
                    $endOfWeek = new \DateTime('last day of this month');
                    break;
            }

            if ($startOfWeek && $endOfWeek) {
                $queryBuilder->andWhere('t.createdAt BETWEEN :startOfWeek AND :endOfWeek')
                    ->setParameter('startOfWeek', $startOfWeek->format('Y-m-d'))
                    ->setParameter('endOfWeek', $endOfWeek->format('Y-m-d'));
            }
        }

        // Order by creation date
        $queryBuilder->orderBy('t.createdAt', 'DESC');

        // Get the result
        $tickets = $queryBuilder->getQuery()->getResult();

        // Render the view with tickets
        return $this->render('ticket/tabContent.html.twig', [
            'tickets' => $tickets,
        ]);
    }
}
