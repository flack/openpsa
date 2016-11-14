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

    public function _on_created()
    {
        if (!$this->orgOpenpsaObtype) {
            $this->orgOpenpsaObtype = self::EMAIL_TEXT;
            $this->update();
        }
    }

    public function _on_loaded()
    {
        $this->title = trim($this->title);
        if (empty($this->title)) {
            $this->title = 'untitled';
        }
    }

    public function get_css_class()
    {
        $class = 'email';
        switch ($this->orgOpenpsaObtype) {
            case self::SMS:
            case self::MMS:
                $class = 'mobile';
                break;
            case self::CALL:
            case self::FAX:
                $class = 'telephone';
                break;
            case self::SNAILMAIL:
                $class = 'postal';
                break;
        }
        if ($this->sendCompleted) {
            $class .= ' ' . $class . '-completed';
        } elseif ($this->sendStarted) {
            $class .= ' ' . $class . '-started';
        }
        return $class;
    }
}
