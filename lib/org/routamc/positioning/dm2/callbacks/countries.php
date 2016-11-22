<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Form Validation helper methods.
 *
 * This is a collection of static classes which are used in the more complex form validation
 * cycles, like username uniqueing and the like.
 *
 * All functions are statically callable. (Have to be for QuickForm to work.)
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_dm2_callbacks_countries extends midcom_baseclasses_components_purecode
 implements midcom_helper_datamanager2_callback_interface
{
    /**
     * The array with the data we're working on.
     *
     * @var array
     */
    private $_data = array();

    public function __construct($args)
    {
        parent::__construct();

        if (isset($args['start_message'])) {
            if ($args['start_message'] === true) {
                $this->_data[''] = $this->_l10n->get('select your country');
            } elseif (is_string($args['start_message'])) {
                $this->_data[''] = $args['start_message'];
            }
        }

        $qb = org_routamc_positioning_country_dba::new_query_builder();
        $qb->add_constraint('code', '<>', '');
        $qb->add_order('name', 'ASC');
        $countries = $qb->execute_unchecked();

        if (count($countries) == 0) {
            debug_add('No countries found. You have to use org.routamc.positioning to import countries to database.');
        }

        $this->_populate_data($countries);
    }

    private function _populate_data(array $countries)
    {
        foreach ($countries as $country) {
            $this->_data[$country->code] = $country->name;
        }
    }

    public function get_name_for_key($key)
    {
        return $this->_data[$key];
    }

    public function key_exists($key)
    {
        return array_key_exists($key, $this->_data);
    }

    public function list_all()
    {
        return $this->_data;
    }

    /** Ignored. */
    public function set_type(&$type)
    {
    }
}
