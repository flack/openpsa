<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_link_log_dba extends __org_openpsa_directmarketing_link_log_dba
{
    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        return parent::__construct($id);
    }

    function _on_creating()
    {
        if (!$this->timestamp)
        {
            $this->timestamp = time();
        }
        if (   !$this->referrer
            && array_key_exists('HTTP_REFERER', $_SERVER)
            && !empty($_SERVER['HTTP_REFERER']))
        {
            $this->referrer = $_SERVER['HTTP_REFERER'];
        }
        return true;
    }

}

?>