<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ticket
 *
 * @ORM\Table(name="ticket",)
 * @ORM\Entity
 */

class Ticket
{
    const TYPE_BUG = 'Bug';
    const TYPE_FEATURE = 'Feature';
    const TYPE_TASK = 'Task';
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const STATUS_CREATED = 'Created';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_DONE = 'Done';
    const STATUS_VALIDATED = 'Validated';
    const STATUS_REJECTED = 'Rejected';

    public static function getArrayType() {
	return array(
		self::TYPE_BUG => self::TYPE_BUG,
	        self::TYPE_FEATURE => self::TYPE_FEATURE,
	        self::TYPE_TASK => self::TYPE_TASK,
	);
    }

    public static function getArrayPriority() {
	return array(
		self::PRIORITY_LOW => self::PRIORITY_LOW,
	        self::PRIORITY_MEDIUM => self::PRIORITY_MEDIUM,
	        self::PRIORITY_HIGH => self::PRIORITY_HIGH,
	);
    }

    public static function getArrayStatus() {
	return array(
		self::STATUS_CREATED => self::STATUS_CREATED,
	        self::STATUS_IN_PROGRESS => self::STATUS_IN_PROGRESS,
	        self::STATUS_DONE => self::STATUS_DONE,
	        self::STATUS_VALIDATED=> self::STATUS_VALIDATED,
	        self::STATUS_REJECTED=> self::STATUS_REJECTED,
	);
    }

    /**
     * @var string
     *
     * @ORM\Column(name="project", type="string", length=100, nullable=false)
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    private $type;
    

    /**
     * @var string
     *
     * @ORM\Column(name="priority", type="string", nullable=false)
     */
    private $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="reviewed_by", type="string", length=50, nullable=true)
     */
    private $reviewedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=15, nullable=false, options={"default": "Created"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="assigned_to", type="string", length=255, nullable=true)
     */
    private $assignedTo;

    /**
     * @var string
     *
     * @ORM\Column(name="department", type="string", length=255, nullable=false)
     */
    private $department;

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

    // Getters and Setters

    /**
     * Set project
     *
     * @param string $project
     *
     * @return Tickets
     */
    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Tickets
     */
    public function setType(string $type)
    {
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException("Invalid type");
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set priority
     *
     * @param string $priority
     *
     * @return Tickets
     */
    public function setPriority(string $priority)
    {
        if (!in_array($priority, self::getArrayPriority())) {
            throw new \InvalidArgumentException("Invalid priority");
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Tickets
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Tickets
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return Tickets
     */
    public function setImage(string $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return Tickets
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
     * Set reviewedBy
     *
     * @param $reviewedBy
     *
     * @return Tickets
     */
    public function setReviewedBy($reviewedBy)
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    /**
     * Get reviewedBy
     *
     * @return string
     */
    public function getReviewedBy()
    {
        return $this->reviewedBy;
    }

    /**
     * Set status
     *
     * @param $status
     *
     * @return Tickets
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::getArrayStatus())) {
            throw new \InvalidArgumentException("Invalid status");
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set assignedTo
     *
     * @param string $assignedTo
     *
     * @return Tickets
     */
    public function setAssignedTo($assignedTo)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * Get assignedTo
     *
     * @return string
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Tickets
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Tickets
     */
    public function setUpdatedAt(\DateTime $updatedAt)
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
     * @return Tickets
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
    public function getUpdatedBy()    {
        return $this->updatedBy;
    }

    /**
     * Set department
     *
     * @param string $department
     *
     * @return Tickets
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get department
     *
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
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

    // Overriding __toString() to return project and type
    public function __toString()
    {
        return $this->project . ' - ' . $this->type;
    }
}
