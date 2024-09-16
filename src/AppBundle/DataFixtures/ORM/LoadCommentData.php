<?php
// src/AppBundle/DataFixtures/ORM/LoadCommentData.php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use AppBundle\Entity\Comment;
use Doctrine\Common\Persistence\ObjectManager as PersistenceObjectManager;

class LoadCommentData extends Fixture
{
    public function load(PersistenceObjectManager $manager)
    {
        // Create an example comment
        $comment = new Comment();
        $ticket = $this->getReference('ticket-1');
        $comment->setTicket($ticket); // Assuming a ticket with ID 1 exists
        $comment->setName('John Doe');
        $comment->setComment('This is a sample comment.');
        $comment->setImage(null);
        $comment->setCreatedAt(new \DateTime());

        // Persist the comment entity
        $manager->persist($comment);

        // Flush the changes to the database
        $manager->flush();
    }
}
