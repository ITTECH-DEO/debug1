<?php
// src/AppBundle/Repository/TicketRepository.php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TicketRepository extends EntityRepository
{
    /**
     * Get tickets not assigned to a specific user.
     *
     * @param string $username
     * @return array
     */

    public function findTicketsNotAssignedToUser($username)
    {
        $entityManager = $this->getEntityManager();

        // Define the DQL query
        $dql = '
        SELECT t, GROUP_CONCAT(u.username) AS assigned_to
        FROM AppBundle:Ticket t
        LEFT JOIN t.users u
        WHERE t.id NOT IN (
            SELECT t2.id
            FROM AppBundle:Ticket t2
            JOIN t2.users u2
            WHERE u2.username = :username
        )
        GROUP BY t.id
        ORDER BY t.createdAt DESC
    ';

        // Create the Query
        $query = $entityManager->createQuery($dql)
            ->setParameter('username', $username);

        // Execute the Query and return the result
        return $query->getResult();
    }
}
