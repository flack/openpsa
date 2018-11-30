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
 * @property integer $campaign
 * @property string $title
 * @property string $description
 * @property integer $sendStarted
 * @property integer $sendCompleted
 * @property integer $orgOpenpsaAccesstype
 * @property integer $orgOpenpsaObtype
 * @property string $orgOpenpsaOwnerWg
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_message_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_campaign_message';

    public $autodelete_dependents = [
        org_openpsa_directmarketing_campaign_messagereceipt_dba::class => 'message'
    ];

    const EMAIL_TEXT = 8000;
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
        if ($this->sendCompleted) {
            $class .= ' ' . $class . '-completed';
        } elseif ($this->sendStarted) {
            $class .= ' ' . $class . '-started';
        }
        return $class;
    }
}
