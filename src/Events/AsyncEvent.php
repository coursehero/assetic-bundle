<?php

namespace CourseHero\UtilsBundle\Events;

use CourseHero\UserBundle\Entity\User;
use CourseHero\PaymentBundle\Entity\Subscription;
use Symfony\Component\EventDispatcher\Event;

use JMS\Serializer\Annotation as Serializer;


/**
 * Class AsyncEvent
 *
 * @Serializer\ExclusionPolicy("all")
 */
class AsyncEvent extends Event {
    const ASYNC_EVENT = "course_hero.utils.events.async";

    /**
     * This needs to be here, and needs to be set to null to
     * allow the Event super class to be serialized
     *
     * @Serializer\Expose
     */
    private $propagationStopped = null;

    /**
     * This needs to be here, and needs to be set to null to
     * allow the Event super class to be serialized
     *
     * @Serializer\Expose
     */
    private $dispatcher = null;

    /**
     * This needs to be here, and needs to be set to null to
     * allow the Event super class to be serialized
     *
     * TODO: for some reason this property tries to deserialize.
     *
     * @Serializer\Type("string")
     * @Serializer\Expose
     */
    private $name = null;

    /**
     * @Serializer\Exclude
     * @var bool
     */
    protected $async;

    /**
     * @var /DateTime
     * @Serializer\Exclude
     * @var bool
     */
    protected $runAt;

    /**
     * @return mixed
     */
    public function getRunAt()
    {
        return $this->runAt;
    }

    /**
     * @param mixed $runAt
     */
    public function setRunAt($runAt)
    {
        $this->runAt = $runAt;
    }

    /**
     * @return boolean
     */
    public function isAsync()
    {
        return $this->async;
    }

    /**
     * @param boolean $async
     */
    public function setAsync($async)
    {
        $this->async = $async;
    }



}