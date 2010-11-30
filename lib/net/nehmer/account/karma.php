<?php
/**
 * @package net.nehmer.account
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for per-module karma objects
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_karma_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nehmer_account_karma';
    
    public function __construct($src = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($src);
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
}
?>