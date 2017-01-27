<?php

namespace Charcoal\Admin\Script\Notification;

use Charcoal\Admin\Script\Notification\AbstractNotificationScript;

/**
 * Process "minute" notifications
 */
class ProcessMinuteScript extends AbstractNotificationScript
{
    /**
     * Get the frequency type of this script.
     *
     * @return string
     */
    protected function frequency()
    {
        return 'minute';
    }

    /**
     * Retrieve the "minimal" date that the revisions should have been made for this script.
     * @return string
     */
    protected function startDate()
    {
        $d = new DateTime('1 minute ago');
        $d->setTime(0, 0, 0);
        return $d->format('Y-m-d H:i:s');
    }

    /**
     * Retrieve the "maximal" date that the revisions should have been made for this script.
     * @return string
     */
    protected function endDate()
    {
        $d = new DateTime($this->starDate().' +1 minute');
        return $d->format('Y-m-d H:i:s');
    }
}
