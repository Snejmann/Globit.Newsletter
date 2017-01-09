<?php
namespace Globit\Newsletter\Domain\Model;

/*
 * This file is part of the Globit.Newsletter package.
 */

use Psmb\Newsletter\Domain\Model\Subscriber;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Utility\Now;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * @Flow\Entity
 */
class Newsletter
{
    const STATUS_WAITING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 2;

    /**
     * @var Subscriber
     * @ORM\ManyToOne
     */
    protected $subscriber;

    /**
     * @var string
     */
    protected $subscription;

    /**
     * @var NodeInterface
     */
    protected $nodePath;

    /**
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $status = self::STATUS_WAITING;

    /**
     * Newsletter constructor.
     * @param Subscriber $subscriber
     * @param NodeInterface $nodePath
     * @param string $subscription
     */
    public function __construct($subscriber, $nodePath, $subscription)
    {
        $this->subscriber = $subscriber;
        $this->nodePath = $nodePath;
        $this->subscription = $subscription;
        $this->creationDate = new Now();
    }

    /**
     * @return Subscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @param Subscriber $subscriber
     */
    public function setSubscriber($subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @return string
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param string $subscription
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return NodeInterface
     */
    public function getNodePath()
    {
        return $this->nodePath;
    }

    /**
     * @param NodeInterface $nodePath
     */
    public function setNodePath($nodePath)
    {
        $this->nodePath = $nodePath;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
