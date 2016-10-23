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
 * @package org.openpsa.products
 */
class org_openpsa_products_product_group_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_products_product_group';

    const TYPE_SMART = 1000;

    public function _on_creating()
    {
        if ($this->_check_duplicates($this->code))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function _on_updating()
    {
        if ($this->_check_duplicates($this->code))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    private function _check_duplicates($code)
    {
        if (!$code)
        {
            return false;
        }

        // Check for duplicates
        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('code', '=', $code);

        if ($this->id)
        {
            $qb->add_constraint('id', '<>', $this->id);
        }

        if ($this->up)
        {
            $qb->add_constraint('up', '=', $this->up);
        }

        return ($qb->count() > 0);
    }

    public function get_path($parent_category = null)
    {
        if ($this->code)
        {
            if ($parent_category)
            {
                return $parent_category . '/' . $this->code . '/';
            }
            return $this->code . '/';
        }
        return $this->guid . '/';
    }

    /**
     * Make an array usable with DM2 select datatype for selecting product groups
     *
     * @param mixed $up            Either the ID or GUID of the product group
     * @param string $prefix       Prefix for the code
     * @param string $keyproperty  Property to use as the key of the resulting array
     * @param boolean $order_by_score Set to true to sort by metadata score
     * @param array $label_fields  Object properties to show in the label (will be shown space separated)
     * @return array
     */
    public static function list_groups($up = 0, $prefix = '', $keyproperty = 'id', $order_by_score = false, $label_fields = array('code', 'title'))
    {
        static $result_cache = array();

        $cache_key = md5($up . $keyproperty . $prefix . $order_by_score . implode('', $label_fields));
        if (isset($result_cache[$cache_key]))
        {
            return $result_cache[$cache_key];
        }

        $result_cache[$cache_key] = array();
        $ret =& $result_cache[$cache_key];

        if (empty($up))
        {
            // TODO: use reflection to see what kind of property this is ?
            if ($keyproperty == 'id')
            {
                $ret[0] = midcom::get()->i18n->get_string('toplevel', 'org.openpsa.products');
            }
            else
            {
                $ret[''] = midcom::get()->i18n->get_string('toplevel', 'org.openpsa.products');
            }
        }
        if (mgd_is_guid($up))
        {
            $group = new org_openpsa_products_product_group_dba($up);
            $up = $group->id;
        }

        $value_properties = array('title', 'code', 'id');
        if ($keyproperty !== 'id')
        {
            $value_properties[] = $keyproperty;
        }
        foreach ($label_fields as $fieldname)
        {
            if (   $fieldname != 'id'
                && $fieldname != $keyproperty)
            {
                $value_properties[] = $fieldname;
                continue;
            }
        }

        $mc = org_openpsa_products_product_group_dba::new_collector('up', (int)$up);
        if ($order_by_score)
        {
            $mc->add_order('metadata.score', 'DESC');
        }
        $mc->add_order('code');
        $mc->add_order('title');
        $results = $mc->get_rows($value_properties);

        foreach ($results as $result)
        {
            $key = $result[$keyproperty];
            $ret[$key] = $prefix;
            foreach ($label_fields as $fieldname)
            {
                $field_val = $result[$fieldname];
                $ret[$key] .= "{$field_val} ";
            }

            $ret = $ret + self::list_groups($result['id'], "{$prefix} > ", $keyproperty, $order_by_score, $label_fields);
        }

        return $ret;
    }

    public function get_root()
    {
        $root = $this;
        while ($root->up != 0)
        {
            $root = self::get_cached($root->up);
        }
        return $root;
    }
}
