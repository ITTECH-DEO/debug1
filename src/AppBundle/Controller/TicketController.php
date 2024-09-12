<?php

namespace AppBundle\Controller;

use AppBundle\Entity\SystemLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrap3View;

use AppBundle\Core\SnituserGuard;

use AppBundle\Entity\Ticket;

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

     public function getCaption()
     {
        $caption = $this->getDefaultCaption();
        $em = $this->getDoctrine()->getManager();
        // $modul = $em->getRepository('AppBundle:Modul')
        //             ->findOneBy(array('code' => $this->getModulCode()));
        // if ($modul)
        //     $caption = $modul->getDescription();
        // return $caption;
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
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

	$myVar = "Hello World 1";

	$arr = array(
		'satu' => 1,
		'dua' => 2,
		'tiga' => 3,
	);

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticket/index.html.twig', array(
		'arr' => $arr,
		'myVar' => $myVar,
	        'caption' => $this->getCaption(),

            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

            return $this->render('ticket/index.html.twig', array(
		'arr' => $arr,
		'myVar' => $myVar,
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }



/*
        $queryBuilder = $em->getRepository('AppBundle:Ticket')->createQueryBuilder('e');

        list($filterForm, $queryBuilder) = $this->filter($queryBuilder, $request);
        list($tickets, $pagerHtml) = $this->paginator($queryBuilder, $request);
        
        $totalOfRecordsString = $this->getTotalOfRecordsString($queryBuilder, $request);

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticket/index.html.twig', array(
                'tickets' => $tickets,
                'pagerHtml' => $pagerHtml,
                'filterForm' => $filterForm->createView(),
                'totalOfRecordsString' => $totalOfRecordsString,
                    'caption' => $this->getCaption(),

            ));
        } else {
            $session = $request->getSession();
            $MENU_modulgroups = $session->get('MENU_modulgroups');
            $MENU_moduls = $session->get('MENU_moduls');

            return $this->render('ticket/index.html.twig', array(
                'tickets' => $tickets,
                'pagerHtml' => $pagerHtml,
                            'filterForm' => $filterForm->createView(),
                'totalOfRecordsString' => $totalOfRecordsString,
            
                'MENU_modulgroups' => $MENU_modulgroups,
                'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
*/
    }


    /**
    * Create filter form and process filter request.
    *
    */
    protected function filter($queryBuilder, $request)
    {
        $filterForm = $this->createForm('AppBundle\Form\TicketFilterType');

        // Bind values from the request
        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            // Build the query from the given form object
            $this->get('petkopara_multi_search.builder')->searchForm( $queryBuilder, $filterForm->get('search'));
        }

        return array($filterForm, $queryBuilder);
    }

    /**
    * Get results from paginator and get paginator view.
    *
    */
    protected function paginator($queryBuilder, Request $request)
    {
        //sorting
        $sortCol = $queryBuilder->getRootAlias().'.'.$request->get('pcg_sort_col', 'id');
        $queryBuilder->orderBy($sortCol, $request->get('pcg_sort_order', 'desc'));
        // Paginator
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($request->get('pcg_show' , 10));

        try {
            $pagerfanta->setCurrentPage($request->get('pcg_page', 1));
        } catch (\Pagerfanta\Exception\OutOfRangeCurrentPageException $ex) {
            $pagerfanta->setCurrentPage(1);
        }
        
        $entities = $pagerfanta->getCurrentPageResults();

        // Paginator - route generator
        $me = $this;
        $routeGenerator = function($page) use ($me, $request)
        {
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
    protected function getTotalOfRecordsString($queryBuilder, $request) {
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
     * Displays a form to create a new Ticket entity.
     *
     * @Route("/new", name="ticket_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $ticket = new Ticket();
        $form   = $this->createForm('AppBundle\Form\TicketType', $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticket);
                $em->flush();
                $em->getConnection()->commit();
                $editLink = $this->generateUrl('ticket_edit', array('id' => $ticket->getId()));
                $this->get('session')->getFlashBag()->add('success', "<a href='$editLink'>New ticket was created successfully.</a>" );

                $nextAction=  $request->get('submit') == 'save' ? 'ticket' : 'ticket_new';
                return $this->redirectToRoute($nextAction);
            } catch (\Exception $e)
            {
                $em->getConnection()->rollback();
                $em->clear();

                if (!$em->isOpen()) {
                    $em = $em->create(
                    $em->getConnection(), $em->getConfiguration());
                }

                $system_log = new SystemLog();
                $system_log->setLogDatetime(new \DateTime());
                $system_log->setLogDescription("['".$e->getCode()."'] F=".$e->getFile().", L= ".$e->getLine().", M=".$e->getMessage());
                $system_log->setLogObject("Ticket");
                $system_log->setLogUser($this->getUser()->getUsername());
                $system_log->setLogUserAddress($this->getUser()->getAddress());
                $serializer = $this->container->get('jms_serializer');
                $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
                $system_log->setLogUserCredential($data);
                $em->persist($system_log);
                $em->flush();

                if ($custom_error_message)
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                else
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving Ticket, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticket/new.html.twig', array(
                    'ticket' => $ticket,
                    'form'   => $form->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));


            }
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('ticket/new.html.twig', array(
                'ticket' => $ticket,
                'form'   => $form->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticket/new.html.twig', array(
                'ticket' => $ticket,
                'form'   => $form->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
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
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

                    return $this->render('ticket/show.html.twig', array(
                        'ticket' => $ticket,
                            'delete_form' => $deleteForm->createView(),
                //             'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
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
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $deleteForm = $this->createDeleteForm($ticket);
        $editForm = $this->createForm('AppBundle\Form\TicketType', $ticket);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticket);
                $em->flush();

                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'Edited Successfully!');
            } catch (\Exception $e)
            {
                $em->getConnection()->rollback();
                $em->clear();

                if (!$em->isOpen()) {
                    $em = $em->create(
                    $em->getConnection(), $em->getConfiguration());
                }

                $system_log = new SystemLog();
                $system_log->setLogDatetime(new \DateTime());
                $system_log->setLogDescription("['".$e->getCode()."'] F=".$e->getFile().", L= ".$e->getLine().", M=".$e->getMessage());
                $system_log->setLogObject("Ticket");
                $system_log->setLogUser($this->getUser()->getUsername());
                $system_log->setLogUserAddress($this->getUser()->getAddress());
                $serializer = $this->container->get('jms_serializer');
                $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
                $system_log->setLogUserCredential($data);
                $em->persist($system_log);
                $em->flush();

                if ($custom_error_message)
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                else
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving Ticket, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticket/edit.html.twig', array(
                    'ticket' => $ticket,
                    'edit_form'   => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));
            }
            
            return $this->redirectToRoute('ticket_edit', array('id' => $ticket->getId()));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticket/edit.html.twig', array(
                'ticket' => $ticket,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticket/edit.html.twig', array(
                'ticket' => $ticket,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
    }
    
    

    /**
     * Deletes a Ticket entity.
     *
     * @Route("/{id}", name="ticket_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Ticket $ticket)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $form = $this->createDeleteForm($ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->remove($ticket);

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'The Ticket was deleted successfully');
            } catch (\Exception $e)
            {
                $em->getConnection()->rollback();
                $em->clear();

                if (!$em->isOpen()) {
                    $em = $em->create(
                    $em->getConnection(), $em->getConfiguration());
                }

                $system_log = new SystemLog();
                $system_log->setLogDatetime(new \DateTime());
                $system_log->setLogDescription("['".$e->getCode()."'] F=".$e->getFile().", L= ".$e->getLine().", M=".$e->getMessage());
                $system_log->setLogObject("Ticket");
                $system_log->setLogUser($this->getUser()->getUsername());
                $system_log->setLogUserAddress($this->getUser()->getAddress());
                $serializer = $this->container->get('jms_serializer');
                $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
                $system_log->setLogUserCredential($data);
                $em->persist($system_log);
                $em->flush();
                if ($custom_error_message)
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                else
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the tickets ( ID ='.$system_log->getId().')');

                return $this->redirectToRoute('ticket');
            }


        } else {
            $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the Ticket');
        }
        
        return $this->redirectToRoute('ticket');
    }
    
    /**
     * Creates a form to delete a Ticket entity.
     *
     * @param Ticket $ticket The Ticket entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Ticket $ticket)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ticket_delete', array('id' => $ticket->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
    
    /**
     * Delete Ticket by id
     *
     * @Route("/delete/{id}", name="ticket_by_id_delete")
     * @Method("GET")
     */
    public function deleteByIdAction(Ticket $ticket){
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $custom_error_message = false;
        try {
            $em->remove($ticket);
            $em->flush();

            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->add('success', 'The Ticket was deleted successfully');
        } catch (\Exception $e)
        {
            $em->getConnection()->rollback();
            $em->clear();

            if (!$em->isOpen()) {
                $em = $em->create(
                $em->getConnection(), $em->getConfiguration());
            }

            $system_log = new SystemLog();
            $system_log->setLogDatetime(new \DateTime());
            $system_log->setLogDescription("['".$e->getCode()."'] F=".$e->getFile().", L= ".$e->getLine().", M=".$e->getMessage());
            $system_log->setLogObject("Ticket");
            $system_log->setLogUser($this->getUser()->getUsername());
            $system_log->setLogUserAddress($this->getUser()->getAddress());
            $serializer = $this->container->get('jms_serializer');
            $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
            $system_log->setLogUserCredential($data);
            $em->persist($system_log);
            $em->flush();
            if ($custom_error_message)
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            else
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the Ticket ( ID ='.$system_log->getId().')');

            return $this->redirect($this->generateUrl('ticket'));
        }

        return $this->redirect($this->generateUrl('ticket'));

    }
    

    /**
    * Bulk Action
    * @Route("/bulk-action/", name="ticket_bulk_action")
    * @Method("POST")
    */
    public function bulkAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $ids = $request->get("ids", array());
        $action = $request->get("bulk_action", "delete");

        if ($action == "delete") {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $repository = $em->getRepository('AppBundle:Ticket');

                foreach ($ids as $id) {
                    $ticket = $repository->find($id);
                    $em->remove($ticket);
                }

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'tickets was deleted successfully!');
            } catch (\Exception $e)
            {
                $em->getConnection()->rollback();
                $em->clear();

                if (!$em->isOpen()) {
                    $em = $em->create(
                    $em->getConnection(), $em->getConfiguration());
                }

                $system_log = new SystemLog();
                $system_log->setLogDatetime(new \DateTime());
                $system_log->setLogDescription("['".$e->getCode()."'] F=".$e->getFile().", L= ".$e->getLine().", M=".$e->getMessage());
                $system_log->setLogObject("Ticket");
                $system_log->setLogUser($this->getUser()->getUsername());
                $system_log->setLogUserAddress($this->getUser()->getAddress());
                $serializer = $this->container->get('jms_serializer');
                $data = $serializer->serialize($this->getUser()->getRoles(), 'json');
                $system_log->setLogUserCredential($data);
                $em->persist($system_log);
                $em->flush();
                if ($custom_error_message)
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                else
                    $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the tickets ( ID ='.$system_log->getId().')');

                return $this->redirect($this->generateUrl('ticket'));
            }



        }

        return $this->redirect($this->generateUrl('ticket'));
    }
    

}
