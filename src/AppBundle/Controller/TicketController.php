<?php

namespace AppBundle\Controller;

use AppBundle\Entity\SystemLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pagerfanta\Pagerfanta;
use AppBundle\Form\TicketType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrap3View;
use AppBundle\Core\SnituserGuard;
use AppBundle\Entity\Ticket;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Ticket controller.
 *
 * @Route("/ticket")
 */
class TicketController extends Controller
{
    public function getDefaultCaption()
    {
        return 'Ticket';
    }

    public function getModulCode()
    {
        return 'ticket';
    }

/**
 * @Route("/assign_tickets", name="logic_assign_tickets", methods={"POST"})
 */
public function assignTickets(Request $request)
{
    $selectedTickets = $request->request->get('selected_tickets', []);
    $adminUsername = $request->request->get('admin_username', '');

    if (!empty($selectedTickets) && !empty($adminUsername)) {
        $em = $this->getDoctrine()->getManager();
        $userRepo = $em->getRepository(User::class);
        $ticketRepo = $em->getRepository(Ticket::class);

        // Ambil User yang sesuai dengan adminUsername
        $adminUser = $userRepo->findOneBy(['username' => $adminUsername]);

        if (!$adminUser) {
            $this->addFlash('error', 'Admin username not found.');
            return $this->redirectToRoute('/ticket'); // Ganti 'ticket_index' dengan nama rute untuk halaman utama tiket
        }

        foreach ($selectedTickets as $ticketId) {
            // Dapatkan tiket berdasarkan id
            $ticket = $ticketRepo->find($ticketId);

            if ($ticket) {
                // Assign tiket ke admin
                $ticket->setStatus('In Progress');
                $ticket->setAssignedTo($adminUser);
                $ticket->setReviewedBy($adminUser->getUsername());

                // Buat entri di ticket_user
                $ticket->addUser($adminUser);

                $em->persist($ticket);
            }
        }

        $em->flush();

        $this->addFlash('success', "Tickets successfully assigned to $adminUsername!");
    } else {
        $this->addFlash('error', "No tickets selected or admin username is missing.");
    }

    return $this->redirectToRoute('/ticket'); // Ganti 'ticket_index' dengan nama rute yang sesuai
}

   /**
 * Filter Tickets.
 *
 * @Route("/filter_tickets", name="filter_tickets", methods={"GET"})
 */
public function filterTicketsAction(Request $request)
{
    $em = $this->getDoctrine()->getManager();
    $queryBuilder = $em->getRepository(Ticket::class)->createQueryBuilder('t');

    // Filter berdasarkan query yang diterima dari AJAX
    if ($request->query->get('priority') !== 'all') {
        $queryBuilder->andWhere('t.priority = :priority')
            ->setParameter('priority', $request->query->get('priority'));
    }

    if ($request->query->get('status') !== 'all') {
        $queryBuilder->andWhere('t.status = :status')
            ->setParameter('status', $request->query->get('status'));
    }

    if ($request->query->get('type') !== 'all') {
        $queryBuilder->andWhere('t.type = :type')
            ->setParameter('type', $request->query->get('type'));
    }

    if ($request->query->get('department') !== 'all') {
        $queryBuilder->andWhere('t.department = :department')
            ->setParameter('department', $request->query->get('department'));
    }

    // Eksekusi query dan dapatkan hasil
    $tickets = $queryBuilder->getQuery()->getResult();

    // Render partial view 'ticket/_list.html.twig' untuk menampilkan daftar tiket yang difilter
    return $this->render('ticket/_list.html.twig', [
        'tickets' => $tickets,
    ]);
}



    public function getCaption()
    {
        $caption = $this->getDefaultCaption();
        $em = $this->getDoctrine()->getManager();
        // Uncomment jika modul description diperlukan
        // $modul = $em->getRepository('AppBundle:Modul')
        //             ->findOneBy(array('code' => $this->getModulCode()));
        // if ($modul)
        //     $caption = $modul->getDescription();
        return $caption;
    }
    /**
 * @Route("/ticket/{id}", name="ticket_detail", methods={"GET"})
 */
public function ticketDetailAction($id)
{
    $em = $this->getDoctrine()->getManager();
    
    // Mengambil data tiket berdasarkan ID
    $ticket = $em->getRepository(Ticket::class)->find($id);
    
    if (!$ticket) {
        throw $this->createNotFoundException('Ticket not found.');
    }

    // Mengambil data komentar
    $comments = $em->getRepository(Comment::class)->findBy(
        ['ticket' => $ticket],
        ['createdAt' => 'DESC']
    );

    return $this->render('ticket/detail.html.twig', [
        'ticket' => $ticket,
        'comments' => $comments,
    ]);
}


    /**
     * Lists all Ticket entities.
     *
     * @Route("/", name="ticket")
     * @Method("GET")
     */
    public function indexAction(Request $request)
{
    $em = $this->getDoctrine()->getManager();
    $searchText = $request->query->get('search', '');
    
    // Mengambil data dari dropdown (priority, status, etc)
    $selectedPriority = $request->query->get('priority', 'all');
    $selectedStatus = $request->query->get('status', 'all');
    $selectedType = $request->query->get('type', 'all');
    $selectedDepartment = $request->query->get('department', 'all');
    $selectedWeek = $request->query->get('week', 'all'); // Week selection
    $selectedResponsible = $request->query->get('responsible', 'all');

    // Mengambil data tiket dari database
    $queryBuilder = $em->getRepository(Ticket::class)->createQueryBuilder('e');

    // Filter jika ada
    if ($selectedPriority !== 'all') {
        $queryBuilder->andWhere('e.priority = :priority')
                     ->setParameter('priority', $selectedPriority);
    }
    if ($selectedStatus !== 'all') {
        $queryBuilder->andWhere('e.status = :status')
                     ->setParameter('status', $selectedStatus);
    }
    if ($selectedType !== 'all') {
        $queryBuilder->andWhere('e.type = :type')
                     ->setParameter('type', $selectedType);
    }
    if ($selectedDepartment !== 'all') {
        $queryBuilder->andWhere('e.department = :department')
                     ->setParameter('department', $selectedDepartment);
    }
    // Filter berdasarkan minggu
    if ($selectedWeek !== 'all') {
        $startOfWeek = null;
        $endOfWeek = null;

        switch ($selectedWeek) {
            case 'week-1':
                $startOfWeek = new \DateTime(date('Y-m-01'));
                $endOfWeek = new \DateTime(date('Y-m-07 23:59:59'));
                break;
            case 'week-2':
                $startOfWeek = new \DateTime(date('Y-m-08'));
                $endOfWeek = new \DateTime(date('Y-m-14 23:59:59'));
                break;
            case 'week-3':
                $startOfWeek = new \DateTime(date('Y-m-15'));
                $endOfWeek = new \DateTime(date('Y-m-21 23:59:59'));
                break;
            case 'week-4':
                $startOfWeek = new \DateTime(date('Y-m-22'));
                $endOfWeek = new \DateTime(date('Y-m-t 23:59:59'));
                break;
        }

        if ($startOfWeek && $endOfWeek) {
            $queryBuilder->andWhere('e.createdAt BETWEEN :startOfWeek AND :endOfWeek')
                         ->setParameter('startOfWeek', $startOfWeek)
                         ->setParameter('endOfWeek', $endOfWeek);
        }
    }
     // Filter berdasarkan responsible
     if ($selectedResponsible !== 'all') {
        $queryBuilder->andWhere('e.assignedTo = :responsible')
                     ->setParameter('responsible', $selectedResponsible);
    }

    // Ambil daftar responsible dari database
    $responsibles = $em->getRepository(Ticket::class)->createQueryBuilder('t')
        ->select('DISTINCT t.assignedTo')
        ->where('t.assignedTo IS NOT NULL')
        ->getQuery()
        ->getResult();

    // Proses pagination dan form filtering
    list($filterForm, $queryBuilder) = $this->filter($queryBuilder, $request);
    list($tickets, $pagerHtml) = $this->paginator($queryBuilder, $request);

    // Variabel untuk dropdown di view
    $departments = ['HR', 'IT', 'Finance', 'Marketing', 'Sales', 'Customer Service', 'Operations', 'Legal'];
    $types = ['Bug', 'Feature', 'Task'];
    $priorities = ['low', 'medium', 'high'];
    $statuses = ['Created', 'In Progress', 'Validated', 'Rejected'];

    // Render halaman view
    if ($request->isXmlHttpRequest()) {
        return $this->render('ticket/index.html.twig', array(
            'tickets' => $tickets,
            'filterForm' => $filterForm->createView(),
            'pagerHtml' => $pagerHtml,
            'caption' => $this->getCaption(),
            'departments' => $departments,
            'types' => $types,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'responsibles' => $responsibles,
            'search_text' => $searchText,
            'selected_priority' => $selectedPriority, // Ditambahkan
            'selected_status' => $selectedStatus, // Ditambahkan
            'selected_type' => $selectedType, // Ditambahkan
            'selected_department' => $selectedDepartment,
            'selected_week' => $selectedWeek,
            'selected_responsible' => $selectedResponsible // Ditambahkan
            
        ));
    } else {
        $session = $request->getSession();
        return $this->render('ticket/index.html.twig', array(
            'tickets' => $tickets,
            'filterForm' => $filterForm->createView(),
            'pagerHtml' => $pagerHtml,
            'caption' => $this->getCaption(),
            'departments' => $departments,
            'types' => $types,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'responsibles' => $responsibles,
            'search_text' => $searchText,
            'selected_priority' => $selectedPriority, // Ditambahkan
            'selected_status' => $selectedStatus, // Ditambahkan
            'selected_type' => $selectedType, // Ditambahkan
            'selected_department' => $selectedDepartment,
            'selected_week' => $selectedWeek,
            'selected_responsible' => $selectedResponsible // Ditambahkan
        ));
    }
}



    /**
    * Create filter form and process filter request.
    *
    */
    protected function filter($queryBuilder, $request)
{
    $filterForm = $this->createForm(\AppBundle\Form\TicketFilterType::class);


    // Bind values from the request
    $filterForm->handleRequest($request);

    if ($filterForm->isSubmitted() && $filterForm->isValid()) {
        // Implementasikan logika filter Anda di sini
        $data = $filterForm->getData();
        if (!empty($data['department'])) {
            $queryBuilder->andWhere('e.department = :department')
                         ->setParameter('department', $data['department']);
        }
        if (!empty($data['status'])) {
            $queryBuilder->andWhere('e.status = :status')
                         ->setParameter('status', $data['status']);
        }
        if (!empty($data['title'])) {
            $queryBuilder->andWhere('e.title LIKE :title')
                         ->setParameter('title', '%' . $data['title'] . '%');
        }
    }

    return [$filterForm, $queryBuilder];
}


    /**
    * Get results from paginator and get paginator view.
    *
    */
    protected function paginator($queryBuilder, Request $request)
    {
        // Sorting
        $sortCol = $queryBuilder->getRootAlias() . '.' . $request->get('pcg_sort_col', 'id');
        $queryBuilder->orderBy($sortCol, $request->get('pcg_sort_order', 'desc'));

        // Paginator
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($request->get('pcg_show', 10));

        try {
            $pagerfanta->setCurrentPage($request->get('pcg_page', 1));
        } catch (\Pagerfanta\Exception\OutOfRangeCurrentPageException $ex) {
            $pagerfanta->setCurrentPage(1);
        }

        $entities = $pagerfanta->getCurrentPageResults();

        // Paginator - route generator
        $me = $this;
        $routeGenerator = function ($page) use ($me, $request) {
            $requestParams = $request->query->all();
            $requestParams['pcg_page'] = $page;
            return $me->generateUrl('ticket', $requestParams);
        };

        // Paginator - view
        $view = new TwitterBootstrap3View();
        $pagerHtml = $view->render($pagerfanta, $routeGenerator, array(
            'proximity' => 3,
            'prev_message' => 'previous',
            'next_message' => 'next',
        ));

        return array($entities, $pagerHtml);
    }

    /*
     * Calculates the total of records string
     */
    protected function getTotalOfRecordsString($queryBuilder, $request)
    {
        $totalOfRecords = $queryBuilder->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();
        $show = $request->get('pcg_show', 10);
        $page = $request->get('pcg_page', 1);

        $startRecord = ($show * ($page - 1)) + 1;
        $endRecord = $show * $page;

        if ($endRecord > $totalOfRecords) {
            $endRecord = $totalOfRecords;
        }
        return "Showing $startRecord - $endRecord of $totalOfRecords Records.";
    }
/**
 * @Route("/new", name="ticket_new", methods={"POST"})
 */
public function newAction(Request $request)
{
    $ticket = new Ticket();
    
    // Create form
    $form = $this->createForm(TicketType::class, $ticket);
    
    // Handle the request
    $form->handleRequest($request);
    dump($form->getData());
    dump($request->request->all());

    if ($form->isSubmitted() && $form->isValid()) {

        // if ($user) {
        //     // Set the created_by with username or any field you want from the User entity
        //     $ticket->setCreatedBy($user->getUsername()); 
        // }
        // Get the content from TinyMCE and clean it up
        $content = $form->get('content')->getData();

        // Remove <p> and </p> tags if present
        $cleanContent = preg_replace('/<\/?p>/', '', $content);
        
        // Set the cleaned content to the ticket entity
        $ticket->setContent($cleanContent);
        // Handle file upload
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('uploads_directory'), // Ensure this parameter is set in config.yml
                $newFilename
            );
            $ticket->setImage($newFilename);
        }

        // Save the ticket in the database
        $em = $this->getDoctrine()->getManager();
        $em->persist($ticket);
        $em->flush();

        return new JsonResponse(['status' => 'success'], 200);
    }

    // If form is invalid, return error messages
    if (!$form->isValid()) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Form not valid',
            'errors' => (string) $form->getErrors(true, false)
        ], 400);
    }

    return new JsonResponse(['status' => 'error', 'message' => 'Invalid request'], 400);
}




    /**
     * Finds and displays a Ticket entity.
     *
     * @Route("/{id}", name="ticket_show")
     * @Method("GET")
     */
    public function showAction(Request $request, Ticket $ticket)
    {
        $em = $this->getDoctrine()->getManager();
        // Uncomment jika ada akses kontrol (user authorization)
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $deleteForm = $this->createDeleteForm($ticket);
        if ($request->isXmlHttpRequest()) {
            return $this->render('ticket/show.html.twig', array(
                'ticket' => $ticket,
                'delete_form' => $deleteForm->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            return $this->render('ticket/show.html.twig', array(
                'ticket' => $ticket,
                'delete_form' => $deleteForm->createView(),
                'caption' => $this->getCaption(),
            ));
        }
    }

    /**
     * Displays a form to edit an existing Ticket entity.
     *
     * @Route("/{id}/edit", name="ticket_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Ticket $ticket)
    {
        $deleteForm = $this->createDeleteForm($ticket);
        $editForm = $this->createForm('AppBundle\Form\TicketType', $ticket);

        dump($form->getErrors(true));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            try {
                $em->flush();
                $em->getConnection()->commit();
                $this->addFlash('success', 'Ticket updated successfully.');

                return $this->redirectToRoute('ticket_edit', array('id' => $ticket->getId()));
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $this->logError($e, 'Ticket');
                $this->addFlash('error', 'Problem with updating ticket.');
            }
        }

        // Data for dropdowns
        $departments = ['HR', 'IT', 'Finance', 'Marketing', 'Sales', 'Customer Service', 'Operations', 'Legal'];
        $types = ['Bug', 'Feature', 'Task'];
        $priorities = ['low', 'medium', 'high'];

        return $this->render('ticket/edit.html.twig', array(
            'ticket' => $ticket,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'caption' => $this->getCaption(),
            'departments' => $departments,
            'types' => $types,
            'priorities' => $priorities
        ));
    }
    

    /**
     * Deletes a Ticket entity.
     *
     * @Route("/{id}", name="ticket_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Ticket $ticket)
    {
        $form = $this->createDeleteForm($ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            try {
                $em->remove($ticket);
                $em->flush();
                $em->getConnection()->commit();
                $this->addFlash('success', 'Ticket deleted successfully.');
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $this->logError($e, 'Ticket');
                $this->addFlash('error', 'Problem with deleting ticket.');
            }
        }

        return $this->redirectToRoute('ticket');
    }

    /**
     * Creates a form to delete a Ticket entity.
     *
     * @param Ticket $ticket The Ticket entity
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Ticket $ticket)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ticket_delete', array('id' => $ticket->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Logs errors into the system log.
     */
    private function logError(\Exception $e, $object)
    {
        $em = $this->getDoctrine()->getManager();
        $systemLog = new SystemLog();
        $systemLog->setLogDatetime(new \DateTime());
        $systemLog->setLogDescription(sprintf("['%s'] File: %s, Line: %s, Message: %s", $e->getCode(), $e->getFile(), $e->getLine(), $e->getMessage()));
        $systemLog->setLogObject($object);
        $systemLog->setLogUser($this->getUser()->getUsername());
        $systemLog->setLogUserAddress($this->getUser()->getAddress());
        $serializer = $this->container->get('jms_serializer');
        $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
        $systemLog->setLogUserCredential($data);
        $em->persist($systemLog);
        $em->flush();
    }

    /**
     * Bulk Action (Delete Multiple Tickets).
     *
     * @Route("/bulk-action/", name="ticket_bulk_action")
     * @Method("POST")
     */
    public function bulkAction(Request $request)
    {
        $ids = $request->get("ids", array());
        $action = $request->get("bulk_action", "delete");

        if ($action == "delete") {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            try {
                $repository = $em->getRepository('AppBundle:Ticket');
                foreach ($ids as $id) {
                    $ticket = $repository->find($id);
                    $em->remove($ticket);
                }

                $em->flush();
                $em->getConnection()->commit();
                $this->addFlash('success', 'Tickets were deleted successfully!');
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $this->logError($e, 'Ticket');
                $this->addFlash('error', 'Problem with deleting the tickets.');
            }
        }

        return $this->redirectToRoute('ticket');
    }
}
