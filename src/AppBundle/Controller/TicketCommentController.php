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

use AppBundle\Entity\TicketComment;

/**
 * TicketComment controller.
 *
 * @Route("/ticketcomment")
 */
class TicketCommentController extends Controller
{
     public function getDefaultCaption()
     {
        return 'TicketComment';
     }

     public function getModulCode()
     {
        return 'ticketcomment';
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
     * Lists all TicketComment entities.
     *
     * @Route("/", name="ticketcomment")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $queryBuilder = $em->getRepository('AppBundle:TicketComment')->createQueryBuilder('e');

        list($filterForm, $queryBuilder) = $this->filter($queryBuilder, $request);
        list($ticketComments, $pagerHtml) = $this->paginator($queryBuilder, $request);
        
        $totalOfRecordsString = $this->getTotalOfRecordsString($queryBuilder, $request);

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketcomment/index.html.twig', array(
                'ticketComments' => $ticketComments,
                'pagerHtml' => $pagerHtml,
                'filterForm' => $filterForm->createView(),
                'totalOfRecordsString' => $totalOfRecordsString,
                    'caption' => $this->getCaption(),

            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

            return $this->render('ticketcomment/index.html.twig', array(
                'ticketComments' => $ticketComments,
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
        $filterForm = $this->createForm('AppBundle\Form\TicketCommentFilterType');

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
            return $me->generateUrl('ticketcomment', $requestParams);
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
     * Displays a form to create a new TicketComment entity.
     *
     * @Route("/new", name="ticketcomment_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $ticketComment = new TicketComment();
        $form   = $this->createForm('AppBundle\Form\TicketCommentType', $ticketComment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticketComment);
                $em->flush();
                $em->getConnection()->commit();
                $editLink = $this->generateUrl('ticketcomment_edit', array('id' => $ticketComment->getId()));
                $this->get('session')->getFlashBag()->add('success', "<a href='$editLink'>New ticketComment was created successfully.</a>" );

                $nextAction=  $request->get('submit') == 'save' ? 'ticketcomment' : 'ticketcomment_new';
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
                $system_log->setLogObject("TicketComment");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving TicketComment, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticketcomment/new.html.twig', array(
                    'ticketComment' => $ticketComment,
                    'form'   => $form->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));


            }
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketcomment/new.html.twig', array(
                'ticketComment' => $ticketComment,
                'form'   => $form->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticketcomment/new.html.twig', array(
                'ticketComment' => $ticketComment,
                'form'   => $form->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
    }
    

    /**
     * Finds and displays a TicketComment entity.
     *
     * @Route("/{id}", name="ticketcomment_show")
     * @Method("GET")
     */
    public function showAction(Request $request, TicketComment $ticketComment)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $deleteForm = $this->createDeleteForm($ticketComment);
        if ($request->isXmlHttpRequest()) {
                return $this->render('ticketcomment/show.html.twig', array(
                    'ticketComment' => $ticketComment,
                    'delete_form' => $deleteForm->createView(),
                    'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');

                    return $this->render('ticketcomment/show.html.twig', array(
                        'ticketComment' => $ticketComment,
                            'delete_form' => $deleteForm->createView(),
                //             'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }

    }
    
    

    /**
     * Displays a form to edit an existing TicketComment entity.
     *
     * @Route("/{id}/edit", name="ticketcomment_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, TicketComment $ticketComment)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }
        $deleteForm = $this->createDeleteForm($ticketComment);
        $editForm = $this->createForm('AppBundle\Form\TicketCommentType', $ticketComment);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->persist($ticketComment);
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
                $system_log->setLogObject("TicketComment");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with saving TicketComment, please contact your Administrator ( ID ='.$system_log->getId().')');

                $session = $request->getSession();
                // $MENU_modulgroups = $session->get('MENU_modulgroups');
                // $MENU_moduls = $session->get('MENU_moduls');
                return $this->render('ticketcomment/edit.html.twig', array(
                    'ticketComment' => $ticketComment,
                    'edit_form'   => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
                    // 'MENU_modulgroups' => $MENU_modulgroups,
                    // 'MENU_moduls' => $MENU_moduls,
                    'caption' => $this->getCaption(),
                ));
            }
            
            return $this->redirectToRoute('ticketcomment_edit', array('id' => $ticketComment->getId()));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('ticketcomment/edit.html.twig', array(
                'ticketComment' => $ticketComment,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'caption' => $this->getCaption(),
            ));
        } else {
            $session = $request->getSession();
            // $MENU_modulgroups = $session->get('MENU_modulgroups');
            // $MENU_moduls = $session->get('MENU_moduls');
            return $this->render('ticketcomment/edit.html.twig', array(
                'ticketComment' => $ticketComment,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                // 'MENU_modulgroups' => $MENU_modulgroups,
                // 'MENU_moduls' => $MENU_moduls,
                'caption' => $this->getCaption(),
            ));
        }
    }
    
    

    /**
     * Deletes a TicketComment entity.
     *
     * @Route("/{id}", name="ticketcomment_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, TicketComment $ticketComment)
    {
        $em = $this->getDoctrine()->getManager();
        // if (!SnituserGuard::isAllowed($em, $this->getModulCode(), $this->getUser()->getId())) {
        //     return $this->redirectToRoute('homepage');
        // }

        $form = $this->createDeleteForm($ticketComment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $custom_error_message = false;
            try {
                $em->remove($ticketComment);

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'The TicketComment was deleted successfully');
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
                $system_log->setLogObject("TicketComment");
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
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the ticketComments ( ID ='.$system_log->getId().')');

                return $this->redirectToRoute('ticketcomment');
            }


        } else {
            $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the TicketComment');
        }
        
        return $this->redirectToRoute('ticketcomment');
    }
    
    /**
     * Creates a form to delete a TicketComment entity.
     *
     * @param TicketComment $ticketComment The TicketComment entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(TicketComment $ticketComment)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ticketcomment_delete', array('id' => $ticketComment->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
    
    /**
     * Delete TicketComment by id
     *
     * @Route("/delete/{id}", name="ticketcomment_by_id_delete")
     * @Method("GET")
     */
    public function deleteByIdAction(TicketComment $ticketComment){
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $custom_error_message = false;
        try {
            $em->remove($ticketComment);
            $em->flush();

            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->add('success', 'The TicketComment was deleted successfully');
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
            $system_log->setLogObject("TicketComment");
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
                $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the TicketComment ( ID ='.$system_log->getId().')');

            return $this->redirect($this->generateUrl('ticketcomment'));
        }

        return $this->redirect($this->generateUrl('ticketcomment'));

    }
    

    /**
    * Bulk Action
    * @Route("/bulk-action/", name="ticketcomment_bulk_action")
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
                $repository = $em->getRepository('AppBundle:TicketComment');

                foreach ($ids as $id) {
                    $ticketComment = $repository->find($id);
                    $em->remove($ticketComment);
                }

                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('success', 'ticketComments was deleted successfully!');
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
                $system_log->setLogObject("TicketComment");
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
                    $this->get('session')->getFlashBag()->add('error', 'Problem with deletion of the ticketComments ( ID ='.$system_log->getId().')');

                return $this->redirect($this->generateUrl('ticketcomment'));
            }



        }

        return $this->redirect($this->generateUrl('ticketcomment'));
    }
    

}
