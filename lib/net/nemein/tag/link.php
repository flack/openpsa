<?php
/**
 * @package net.nemein.tag
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property string $fromGuid Tagged object's GUID
 * @property string $fromComponent Tagged object's component
 * @property string $fromClass Tagged object's class
 * @property integer $tag Link to the tag object
 * @property string $context Context to be used with machine tags
 * @property string $value Value to be used with machine tags
 * @package net.nemein.tag
 */
class net_nemein_tag_link_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_tag_link';

    public $_use_rcs = false;

    public function get_parent_guid_uncached()
    {
        if (empty($this->fromGuid)) {
            return null;
        }
        $class = $this->fromClass;
        if (!class_exists($class)) {
            debug_add("Class '{$class}' is missing", MIDCOM_LOG_ERROR);
            return null;
        }
        $parent = new $class($this->fromGuid);
        if (empty($parent->guid)) {
            return null;
        }
        return $parent->guid;
    }

    public function get_label()
    {
        $mc = net_nemein_tag_tag_dba::new_collector('id', $this->tag);
        $tag_guids = $mc->get_values('tag');

        foreach ($tag_guids as $guid) {
            return net_nemein_tag_handler::tag_link2tagname($guid, $this->value, $this->context);
        }
        return $this->guid;
    }

    private function _sanity_check()
    {
        if (empty($this->fromGuid) || empty($this->fromClass) || empty($this->tag)) {
            debug_add("Sanity check failed with tag #{$this->tag}", MIDCOM_LOG_WARN);
            return false;
        }
        $qb = net_nemein_tag_link_dba::new_query_builder();
        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }
        $qb->add_constraint('fromGuid', '=', $this->fromGuid);
        $qb->add_constraint('tag', '=', $this->tag);
        $qb->add_constraint('context', '=', $this->context);

        if ($qb->count_unchecked() > 0) {
            debug_add("Duplicate check failed with tag #{$this->tag}", MIDCOM_LOG_WARN);
            return false;
        }
        return true;
    }

    public function _on_creating()
    {
        return $this->_sanity_check();
    }

    public function _on_updating()
    {
        return $this->_sanity_check();
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with tag objects, later we can add
     * restrictions on object level as necessary.
     */
    public function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:create']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:update']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:read']    = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }
}
