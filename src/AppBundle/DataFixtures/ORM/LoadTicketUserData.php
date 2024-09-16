<?php
// src/AppBundle/DataFixtures/ORM/LoadTicketUserData.php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use AppBundle\Entity\TicketUser;
use Doctrine\Common\Persistence\ObjectManager as PersistenceObjectManager;

class LoadTicketUserData extends Fixture
{
    public function load(PersistenceObjectManager $manager)
    {
        // Create an example ticket user association
        $user = $this->getReference('user-1');
        $ticket = $this->getReference('ticket-1');
        $ticketUser = new TicketUser();
        $ticketUser->setUser($user); // Assuming a ticket with ID 1 exists
        $ticketUser->setTicket($ticket); // Assuming a user with ID 1 exists
        $ticketUser->setAssignedAt(new \DateTime());

        // Persist the ticket user entity
        $manager->persist($ticketUser);

        // Flush the changes to the database
        $manager->flush();
    }
}
