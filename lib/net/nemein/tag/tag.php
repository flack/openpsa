<?php
/**
 * @package net.nemein.tag
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to tags
 *
 * @property string $tag The tag itself
 * @property string $url A URI or URL pointing to information about the tag
 * @package net.nemein.tag
 */
class net_nemein_tag_tag_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_tag';

    public $_use_rcs = false;

    public function get_label()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     * @return net_nemein_tag_tag_dba|boolean
     */
    public static function get_by_tag($tag)
    {
        if (!empty($tag)) {
            $qb = self::new_query_builder();
            $qb->add_constraint('tag', '=', $tag);
            $results = $qb->execute();
            if (!empty($results)) {
                return $results[0];
            }
        }
        return false;
    }

    public function _on_creating()
    {
        return (   $this->validate_tag($this->tag)
                && $this->_check_duplicates() == 0);
    }

    /**
     * Ensure validity of given tag
     *
     * @param string $tag Tag to validate
     * @return boolean Whether tag is valid
     */
    private function validate_tag($tag) : bool
    {
        if (empty($tag)) {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('net.nemein.tag', 'net.nemein.tag'), sprintf(midcom::get()->i18n->get_string('tag "%s" is not valid. tags may not be empty', 'net.nemein.tag'), $tag), 'info');
            return false;
        }
        if (is_numeric($tag)) {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('net.nemein.tag', 'net.nemein.tag'), sprintf(midcom::get()->i18n->get_string('tag "%s" is not valid. tags may not be numeric', 'net.nemein.tag'), $tag), 'info');
            return false;
        }
        if (   strstr($tag, '"')
            || strstr($tag, "'")) {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('net.nemein.tag', 'net.nemein.tag'), sprintf(midcom::get()->i18n->get_string('tag "%s" is not valid. tags may not contain quotes', 'net.nemein.tag'), $tag), 'info');
            return false;
        }

        return true;
    }

    public function _on_updating()
    {
        return (   $this->validate_tag($this->tag)
                && $this->_check_duplicates() == 0);
    }

    private function _check_duplicates() : int
    {
        $qb = self::new_query_builder();
        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }
        $qb->add_constraint('tag', '=', $this->tag);
        return $qb->count_unchecked();
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
