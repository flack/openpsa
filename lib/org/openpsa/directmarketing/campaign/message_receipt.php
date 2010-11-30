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
class org_openpsa_directmarketing_campaign_message_receipt_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_campaign_message_receipt';

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    public function _on_creating()
    {
        if (!$this->timestamp)
        {
            $this->timestamp = time();
        }
        return true;
    }

    /**
     * Check whether given token has already been used in the database
     * @param string $token
     * @return boolean indicating whether token is free or not (true for free == not present)
     */
    function token_is_free($token, $type = ORG_OPENPSA_MESSAGERECEIPT_SENT)
    {
        $qb = new midgard_query_builder('org_openpsa_campaign_message_receipt');
        $qb->add_constraint('token', '=', $token);
        if ($type)
        {
            $qb->add_constraint('orgOpenpsaObtype', '=', $type);
        }
        $ret = @$qb->execute();
        if (empty($ret))
        {
            return true;
        }
        return false;
    }
}
?>