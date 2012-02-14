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

    static function new_query_builder()
    {
        return midcom::get('dbfactory')->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return midcom::get('dbfactory')->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return midcom::get('dbfactory')->get_cached(__CLASS__, $src);
    }

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

        $result = $qb->execute();
        if (count($result) > 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Helper to make an array usable with DM2 select datatype for selecting product groups
     *
     * @param mixed $up            Either the ID or GUID of the product group
     * @param string $prefix       Prefix for the code
     * @param string $keyproperty  Property to use as the key of the resulting array
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
                $ret[0] = midcom::get('i18n')->get_string('toplevel', 'org.openpsa.products');
            }
            else
            {
                $ret[''] = midcom::get('i18n')->get_string('toplevel', 'org.openpsa.products');
            }
        }
        if (mgd_is_guid($up))
        {
            $group = new org_openpsa_products_product_group_dba($up);
            $up = $group->id;
        }

        $mc = org_openpsa_products_product_group_dba::new_collector('up', (int)$up);

        $mc->add_value_property('title');
        $mc->add_value_property('code');
        $mc->add_value_property('id');
        if ($keyproperty !== 'id')
        {
            $mc->add_value_property($keyproperty);
        }
        foreach ($label_fields as $fieldname)
        {
            if (   $fieldname == 'id'
                || $fieldname == $keyproperty)
            {
                continue;
            }
            $mc->add_value_property($fieldname);
        }
        unset($fieldname);

        // Order by score if required
        if ($order_by_score)
        {
            $mc->add_order('metadata.score', 'DESC');
        }
        $mc->add_order('code');
        $mc->add_order('title');
        $mc->execute();
        $mc_keys = $mc->list_keys();
        foreach ($mc_keys as $mc_key => $dummy)
        {
            $id = $mc->get_subkey($mc_key, 'id');
            $key = $mc->get_subkey($mc_key, $keyproperty);
            $ret[$key] = $prefix;
            foreach ($label_fields as $fieldname)
            {
                $field_val = $mc->get_subkey($mc_key, $fieldname);
                $ret[$key] .= "{$field_val} ";
            }
            unset($fieldname, $field_val);
            $ret = $ret + org_openpsa_products_product_group_dba::list_groups($id, "{$prefix} > ", $keyproperty, $label_fields);
            unset($id, $key);
        }
        unset($mc, $mc_keys, $dummy, $mc_key);

        return $ret;
    }

    function list_groups_by_up($up = 0)
    {
        //FIXME rewrite to use collector, rewrite to use (per request) result caching
        static $group_list = array();

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $up);
        $qb->add_order('code');
        $qb->add_order('title');
        $groups = $qb->execute();

        foreach ($groups as $group)
        {
            if (   !isset($group_list['id'])
                || !is_array($group_list['id']))
            {
                $group_list['id'] = array();
            }
            $group_list['id'][$group->id] = "{$group->title}";
        }

        return $group_list['id'];
    }

    function list_groups_parent($up = 0)
    {
        //FIXME rewrite to use collector, rewrite to use (per request) result caching
        static $group_list = array();

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        if (midcom_connection::is_admin())
        {
            $qb->add_constraint('up', '=', $up);
        }
        else
        {
            $qb->add_constraint('id', '=', $up);
        }
        $qb->add_order('code');
        $qb->add_order('title');
        $groups = $qb->execute();

        foreach ($groups as $group)
        {
            $group_list[$group->code] = "{$group->title}";
        }

        return $group_list;
    }
}
?>