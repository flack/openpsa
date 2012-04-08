<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_link_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_wiki_link';

    function get_parent_guid_uncached()
    {
        // FIXME: Midgard Core should do this
        if ($this->frompage != 0)
        {
            try
            {
                $parent = new net_nemein_wiki_wikipage($this->frompage);
                return $parent->guid;
            }
            catch (midcom_error $e)
            {
                $e->log();
                return null;
            }
        }
        else
        {
            return null;
        }
    }
}
?>