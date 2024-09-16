<?php
// src/AppBundle/DataFixtures/ORM/LoadTicketData.php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use AppBundle\Entity\Ticket;
use Doctrine\Common\Persistence\ObjectManager as PersistenceObjectManager;

class LoadTicketData extends Fixture
{
    public function load(PersistenceObjectManager $manager)
    {
        // Create an example ticket
        $ticket = new Ticket();
        $ticket->setProject('Project A');
        $ticket->setType('Bug');
        $ticket->setPriority('high');
        $ticket->setTitle('Sample Ticket');
        $ticket->setContent('This is a sample ticket for demonstration purposes.');
        $ticket->setImage(null);
        $ticket->setCreatedBy('admin');
        $ticket->setReviewedBy(null);
        $ticket->setStatus('Created');
        $ticket->setAssignedTo(null);
        $ticket->setCreatedAt(new \DateTime());
        $ticket->setUpdatedAt(new \DateTime());
        $ticket->setDepartment('Support');

        // Persist the ticket entity
        $manager->persist($ticket);

        $this->addReference('ticket-1', $ticket);

        // Flush the changes to the database
        $manager->flush();
    }
}
