<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\DAV\Auth\Backend\AbstractBasic;

/**
 * CalDAV authentication: HTTP Basic against MidCOM's auth backend, without starting a session
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_caldav_auth extends AbstractBasic
{
    public function __construct()
    {
        $this->principalPrefix = org_openpsa_calendar_caldav_principals::PREFIX . '/';
        $this->realm = midcom::get()->config->get('midcom_site_title') ?: 'OpenPSA';
    }

    protected function validateUserPass($username, $password)
    {
        return midcom::get()->auth->authenticate((string) $username, (string) $password);
    }
}
