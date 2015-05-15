<?php

namespace CourseHero\UtilsBundle\Events;

use CourseHero\UserBundle\Entity\User;
use CourseHero\PaymentBundle\Entity\Subscription;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class SlackMessageEvent
 *
 *
 */
class SlackMessageEvent extends Event {
    const EVENT_NAME = "course_hero.utils.slack_message";

    protected $text;
    protected $channel;
    protected $slackbotname;
    protected $emojiIcon;
    protected $attachments;

    public function __construct($text,
                                $channel = '#debug',
                                $slackbotname = 'debug',
                                $emojiIcon = ':interrobang:',
                                $attachments = null)
    {
        $this->text = $text;
        $this->channel = $channel;
        $this->slackbotname = $slackbotname;
        $this->emojiIcon = $emojiIcon;
        $this->attachments = $attachments;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getSlackbotname()
    {
        return $this->slackbotname;
    }

    /**
     * @return string
     */
    public function getEmojiIcon()
    {
        return $this->emojiIcon;
    }

    /**
     * @return null
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}