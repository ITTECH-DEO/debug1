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

use AppBundle\Entity\TicketUser;

/**
 * TicketUser controller.
 *
 * @Route("/ticketuser")
 */
class TicketUserController extends Controller
{
     public function getDefaultCaption()
     {
        return 'TicketUser';
     }

     public function getModulCode()
     {
        return 'ticketuser';
     }

     public function getCaption()
     {
        $caption = $this->getDefaultCaption();
        $em = $this->getDoctrine()->getManager();
        // $modul = $em->getRepository('AppBundle:Modul')
        //             ->findOneBy(array('code' => $this->getModulCode()));
        // if ($modul)
        //     $caption = $modul->getDescription();
        return $caption;
     }


/**
     * Lists all TicketUser entities.
     *
     * @Route("/", name="ticketuser")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $queryBuilder = $em->getRepository('AppBundle:TicketUser')->createQueryBuilder('e');

        list($filterForm, $queryBuilder) = $this->filter($queryBuilder, $request);
        list($ticketUsers, $pagerHtml) = $this->paginator($queryBuilder, $request);
        
        $totalOfRecordsString = $this->getTotalOfRecordsString($queryBuilder, $request);

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketuser/index.html.twig', array(
                'ticketUsers' => $ticketUsers,
                'pagerHtml' => $pagerHtml,
                'filterForm' => $filterForm->createView(),
                'totalOfRecordsString' => $totalOfRecordsString,
                    'caption' => $this->getCaption(),

            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

            return $this->render('ticketuser/index.html.twig', array(
                'ticketUsers' => $ticketUsers,
                'pagerHtml' => $pagerHtml,
                            'filterForm' => $filterForm->createView(),
                'totalOfRecordsString' => $totalOfRecordsString,
            
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }

    }


    /**
    * Create filter form and process filter request.
    *
    */
    protected function filter($queryBuilder, $request)
    {
        $filterForm = $this->createForm('AppBundle\Form\TicketUserFilterType');

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
            return $me->generateUrl('ticketuser', $requestParams);
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
     * Displays a form to create a new TicketUser entity.
     *
     * @Route("/new", name="ticketuser_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $ticketUser = new TicketUser();
        $form   = $this->createForm('AppBundle\Form\TicketUserType', $ticketUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticketUser);
                $em->flush();
                $em->getConnection()->commit();
                $editLink = $this->generateUrl('ticketuser_edit', array('id' => $ticketUser->getId()));
                $this->get('session')->getFlashBag()->add('success', "<a href='$editLink'>New ticketUser was created successfully.</a>" );

                $nextAction=  $request->get('submit') == 'save' ? 'ticketuser' : 'ticketuser_new';
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
                $system_log->setLogObject("TicketUser");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving TicketUser, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticketuser/new.html.twig', array(
                    'ticketUser' => $ticketUser,
                    'form'   => $form->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));


            }
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketuser/new.html.twig', array(
                'ticketUser' => $ticketUser,
                'form'   => $form->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticketuser/new.html.twig', array(
                'ticketUser' => $ticketUser,
                'form'   => $form->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
    }
    

    /**
     * Finds and displays a TicketUser entity.
     *
     * @Route("/{id}", name="ticketuser_show")
     * @Method("GET")
     */
    public function showAction(Request $request, TicketUser $ticketUser)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $deleteForm = $this->createDeleteForm($ticketUser);
        if ($request->isXmlHttpRequest()) {
                return $this->render('ticketuser/show.html.twig', array(
                    'ticketUser' => $ticketUser,
                    'delete_form' => $deleteForm->createView(),
                    'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

                    return $this->render('ticketuser/show.html.twig', array(
                        'ticketUser' => $ticketUser,
                            'delete_form' => $deleteForm->createView(),
                //             'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }

    }
    
    

    /**
     * Displays a form to edit an existing TicketUser entity.
     *
     * @Route("/{id}/edit", name="ticketuser_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, TicketUser $ticketUser)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $deleteForm = $this->createDeleteForm($ticketUser);
        $editForm = $this->createForm('AppBundle\Form\TicketUserType', $ticketUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticketUser);
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
                $system_log->setLogObject("TicketUser");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving TicketUser, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticketuser/edit.html.twig', array(
                    'ticketUser' => $ticketUser,
                    'edit_form'   => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));
            }
            
            return $this->redirectToRoute('ticketuser_edit', array('id' => $ticketUser->getId()));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketuser/edit.html.twig', array(
                'ticketUser' => $ticketUser,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticketuser/edit.html.twig', array(
                'ticketUser' => $ticketUser,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
    }
    
    

    /**
     * Deletes a TicketUser entity.
     *
     * @Route("/{id}", name="ticketuser_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, TicketUser $ticketUser)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $form = $this->createDeleteForm($ticketUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->remove($ticketUser);

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'The TicketUser was deleted successfully');
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
                $system_log->setLogObject("TicketUser");
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
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the ticketUsers ( ID ='.$system_log->getId().')');

                return $this->redirectToRoute('ticketuser');
            }


        } else {
            $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the TicketUser');
        }
        
        return $this->redirectToRoute('ticketuser');
    }
    
    /**
     * Creates a form to delete a TicketUser entity.
     *
     * @param TicketUser $ticketUser The TicketUser entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(TicketUser $ticketUser)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ticketuser_delete', array('id' => $ticketUser->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
    
    /**
     * Delete TicketUser by id
     *
     * @Route("/delete/{id}", name="ticketuser_by_id_delete")
     * @Method("GET")
     */
    public function deleteByIdAction(TicketUser $ticketUser){
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $custom_error_message = false;
        try {
            $em->remove($ticketUser);
            $em->flush();

            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->add('success', 'The TicketUser was deleted successfully');
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
            $system_log->setLogObject("TicketUser");
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
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the TicketUser ( ID ='.$system_log->getId().')');

            return $this->redirect($this->generateUrl('ticketuser'));
        }

        return $this->redirect($this->generateUrl('ticketuser'));

    }
    

    /**
    * Bulk Action
    * @Route("/bulk-action/", name="ticketuser_bulk_action")
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
                $repository = $em->getRepository('AppBundle:TicketUser');

                foreach ($ids as $id) {
                    $ticketUser = $repository->find($id);
                    $em->remove($ticketUser);
                }

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'ticketUsers was deleted successfully!');
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
                $system_log->setLogObject("TicketUser");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the ticketUsers ( ID ='.$system_log->getId().')');

                return $this->redirect($this->generateUrl('ticketuser'));
            }



        }

        return $this->redirect($this->generateUrl('ticketuser'));
    }
    

}
