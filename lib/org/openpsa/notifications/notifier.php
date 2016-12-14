<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Interface for sending notices
 *
 * @package org.openpsa.notifications
 */
interface org_openpsa_notifications_notifier
{
    public function send(midcom_db_person $recipient, array $message);
}
