<?php

namespace Charcoal\Admin\Script\Notification;

use DateTime;

// Module `charcoal-core` dependencies
use Charcoal\Model\CollectionInterface;

// Intra-module (`charcoal-admin`) dependencies
use Charcoal\Admin\Object\Notification;
use Charcoal\Admin\Script\Notification\AbstractNotificationScript;

/**
 * Process "hourly" notifications
 */
class ProcessWeeklyScript extends AbstractNotificationScript
{
    /**
     * Get the frequency type of this script.
     *
     * @return string
     */
    protected function frequency()
    {
        return 'weekly';
    }

    /**
     * Retrieve the "minimal" date that the revisions should have been made for this script.
     * @return DateTime
     */
    protected function startDate()
    {
        $d = new DateTime('last monday -1 week');
        $d->setTime(0, 0, 0);
        return $d;
    }

    /**
     * Retrieve the "maximal" date that the revisions should have been made for this script.
     * @return DateTime
     */
    protected function endDate()
    {
        $d = new DateTime('last monday');
        $d->setTime(0, 0, 0);
        return $d;
    }

    /**
     * @param  Notification        $notification The notification object.
     * @param  CollectionInterface $objects      The objects that were modified.
     * @return array
     */
    protected function emailData(Notification $notification, CollectionInterface $objects)
    {
        $subject = sprintf(
            'Weekly Charcoal Notification - %s to %s',
            $this->startDate()->format('Y-m-d'),
            $this->endDate()->format('Y-m-d')
        );

        return [
            'subject'         => $subject,
            'template_ident'  => 'charcoal/admin/email/notification.weekly',
            'template_data'   => [
                'startString' => $this->startDate()->format('Y-m-d'),
                'endString'   => $this->startDate()->format('Y-m-d')
            ]
        ];
    }
}