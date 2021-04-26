<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property integer $up
 * @property string $code
 * @property string $title
 * @property string $description
 * @property integer $orgOpenpsaObtype
 * @package org.openpsa.products
 */
class org_openpsa_products_product_group_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_products_product_group';

    const TYPE_SMART = 1000;

    public function _on_creating()
    {
        if ($this->_check_duplicates($this->code)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function _on_updating()
    {
        if ($this->_check_duplicates($this->code)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    private function _check_duplicates(string $code) : bool
    {
        if (!$code) {
            return false;
        }

        // Check for duplicates
        $qb = self::new_query_builder();
        $qb->add_constraint('code', '=', $code);
        $qb->add_constraint('up', '=', $this->up);

        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }

        return $qb->count() > 0;
    }

    /**
     * Make an array usable with datamanager select type for selecting product groups
     *
     * @param array $label_fields  Object properties to show in the label (will be shown space separated)
     */
    public static function list_groups(int $up, string $prefix, string $keyproperty, bool $order_by_score = false, array $label_fields = ['code', 'title']) : array
    {
        static $result_cache = [];

        $cache_key = md5($up . $keyproperty . $prefix . $order_by_score . implode('', $label_fields));
        if (isset($result_cache[$cache_key])) {
            return $result_cache[$cache_key];
        }

        $result_cache[$cache_key] = [];
        $ret =& $result_cache[$cache_key];

        if (empty($up)) {
            // TODO: use reflection to see what kind of property this is ?
            if ($keyproperty == 'id') {
                $ret[0] = midcom::get()->i18n->get_string('toplevel', 'org.openpsa.products');
            } else {
                $ret[''] = midcom::get()->i18n->get_string('toplevel', 'org.openpsa.products');
            }
        }

        $value_properties = array_unique(array_merge($label_fields, [$keyproperty, 'id']));

        $mc = self::new_collector('up', $up);
        if ($order_by_score) {
            $mc->add_order('metadata.score', 'DESC');
        }
        $mc->add_order('code');
        $mc->add_order('title');

        foreach ($mc->get_rows($value_properties) as $result) {
            $key = $result[$keyproperty];
            $ret[$key] = $prefix;
            foreach ($label_fields as $fieldname) {
                $field_val = $result[$fieldname];
                $ret[$key] .= "{$field_val} ";
            }

            $ret += self::list_groups($result['id'], "{$prefix} > ", $keyproperty, $order_by_score, $label_fields);
        }

        return $ret;
    }

    public function get_root() : self
    {
        $root = $this;
        while ($root->up != 0) {
            $root = self::get_cached($root->up);
        }
        return $root;
    }
}
