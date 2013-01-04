<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Message class, handles storage of various messages and sending them out.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_message_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_campaign_message';

    const EMAIL_TEXT = 8000;
    const SMS = 8001;
    const MMS = 8002;
    const CALL = 8003;
    const SNAILMAIL = 8004;
    const FAX = 8005;
    const EMAIL_HTML = 8006;

    function get_parent_guid_uncached()
    {
        if (empty($this->campaign))
        {
            return null;
        }
        return self::get_parent_guid_uncached_static($this->guid, __CLASS__);
    }

    public static function get_parent_guid_uncached_static($guid, $classname)
    {
        if (empty($guid))
        {
            return null;
        }

        $mc = org_openpsa_directmarketing_campaign_message_dba::new_collector('guid', $guid);
        $result = $mc->get_values('campaign');
        if (empty($result))
        {
            // error
            return null;
        }
        $campaign_id = array_shift($result);

        $mc2 = org_openpsa_directmarketing_campaign_dba::new_collector('id', $campaign_id);
        $result2 = $mc2->get_values('guid');
        if (empty($result2))
        {
            // error
            return null;
        }
        return array_shift($result2);
    }

    function get_dba_parent_class()
    {
        return 'org_openpsa_directmarketing_campaign_dba';
    }

    public function _on_created()
    {
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = self::EMAIL_TEXT;
            $this->update();
        }
    }

    public function _on_loaded()
    {
        $this->title = trim($this->title);
        if (   $this->id
            && empty($this->title))
        {
            $this->title = 'untitled';
        }
    }
}
?>