<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: event.php 19755 2008-12-12 11:11:58Z piotras $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * TinyURL abstraction class for generating short URLs
 *
 * @package net.nemein.calendar
 */
class net_nemein_redirector_tinyurl_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_redirector_tinyurl';

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
    
    /**
     * Check that everything is ok on creation event
     */
    public function _on_creating()
    {
        if ($this->duplicate_names())
        {
            return false;
        }

        return true;
    }

    /**
     * Check that everything is ok on creation event
     */
    public function _on_updating()
    {
        if ($this->duplicate_names())
        {
            return false;
        }

        return true;
    }

    /**
     * Check for duplicate names
     */
    private function duplicate_names()
    {
        $mc = net_nemein_redirector_tinyurl_dba::new_collector('name', $this->name);
        $mc->add_constraint('node', '=', $this->node);

        // This item already exists, exclude itself from duplicate name check
        if ($this->guid)
        {
            $mc->add_constraint('guid', '<>', $this->guid);
        }

        $mc->execute();

        if (count($mc->list_keys()) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Trim a tiny url
     */
    static function generate($chars = null, $length = 6)
    {
        if (!$chars)
        {
            $chars = '23456789abcdefghjkmnopqrstuvwxyz';
        }

        $url = '';
        $tmp = (int) strlen($chars);

        for ($i = 0; $i < $length; $i++)
        {
            $url .= substr($chars, rand(0, $tmp - 1), 1);
        }

        return $url;
    }
}
?>