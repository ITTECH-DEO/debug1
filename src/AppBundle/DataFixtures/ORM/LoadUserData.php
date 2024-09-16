<?php
// src/AppBundle/DataFixtures/ORM/LoadUserData.php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadUserData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Example User 1
        $user1 = new User();
        $user1->setUsername('admin');
        $user1->setPassword(password_hash('admin123', PASSWORD_BCRYPT)); // hash the password
        $user1->setRole('admin');
        $user1->setCreatedAt(new \DateTime());
        
        $manager->persist($user1);

        $admin2 = new User();
        $admin2->setUsername('admin2');
        $admin2->setPassword(password_hash('admin123', PASSWORD_BCRYPT)); // hash the password
        $admin2->setRole('admin');
        $admin2->setCreatedAt(new \DateTime());

        $manager->persist($admin2);

        $user = new User();
        $user->setUsername('user');
        $user->setPassword(password_hash('user123', PASSWORD_BCRYPT)); // hash the password
        $user->setRole('user');
        $user->setCreatedAt(new \DateTime());

        $manager->persist($user);
        
        // Example User 2
        $user2 = new User();
        $user2->setUsername('john');
        $user2->setPassword(password_hash('john123', PASSWORD_BCRYPT));
        $user2->setRole('user');
        $user2->setCreatedAt(new \DateTime());
        
        $manager->persist($user2);

        $this->addReference('user-1', $admin2);
        
        // Flush the changes to the database
        $manager->flush();
    }

    public function getOrder()
    {
        return 1; // order in which fixtures will be loaded
    }
}
