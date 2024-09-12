<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TicketUser
 *
 * @ORM\Table(name="ticket_user")
 * @ORM\Entity
 */
class TicketUser
{
    /**
     * @var \Ticket
     *
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ticket_id", referencedColumnName="id")
     * })
     */
    private $ticket;

    /**
     * @var \Ticket
     *
     * @ORM\ManyToOne(targetEntity="Snituser")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="snituser_id", referencedColumnName="id")
     * })
     */
    private $snituser;

    /**
     * @var string
     *
     * @ORM\Column(name="assigned_at", type="datetime", nullable=true)
     */
    private $assignedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="created_by", type="string", length=45, nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="updated_by", type="string", length=45, nullable=true)
     */
    private $updatedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set ticket
     *
     * @param \AppBundle\Entity\Ticket $ticket
     *
     * @return TicketUser
     */
    public function setTicket(\AppBundle\Entity\Ticket $ticket = null)
    {
        $this->ticket= $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return \AppBundle\Entity\Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set snituser
     *
     * @param \AppBundle\Entity\Snituser $snituser
     *
     * @return TicketUser
     */
    public function setSnituser(\AppBundle\Entity\Snituser $snituser= null)
    {
        $this->snituser= $snituser;

        return $this;
    }

    /**
     * Get snituser
     *
     * @return \AppBundle\Entity\Snituser
     */
    public function getSnituser()
    {
        return $this->snituser;
    }


    /**
     * Set assignedAt
     *
     * @param string $assignedAt
     *
     * @return assignedAt
     */
    public function setAssignedAt($assignedAt)
    {
        $this->assignedAt = $assignedAt;

        return $this;
    }

    /**
     * Get assignedAt
     *
     * @return string
     */
    public function getassignedAt()
    {
        return $this->assignedAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return TicketUser
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return TicketUser
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return TicketUser
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedBy
     *
     * @param string $updatedBy
     *
     * @return TicketUser
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return string
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}
