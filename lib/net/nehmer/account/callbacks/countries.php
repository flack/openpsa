<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: validation.php 3210 2006-04-06 17:28:02Z torben $
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
 * @package net.nehmer.account
 */
class net_nehmer_account_callbacks_countries extends midcom_baseclasses_components_purecode
{
    /**
     * The array with the data we're working on.
     *
     * @var array
     * @access private
     */
    var $_data = null;

    /**
     * The callback class instance, a callback matching the signature required for the DM2 select
     * type callbacks.
     *
     * @var object
     * @access private
     */
    var $_callback = null;

    function __construct($args)
    {
        $_MIDCOM->componentloader->load_graceful('org.routamc.positioning');
        $this->_component = 'net.nehmer.account';
        parent::__construct();

        $this->_data = array
        (
            '' => $this->_l10n_midcom->get('select your country'),
        );

        $qb = org_routamc_positioning_country_dba::new_query_builder();
        $qb->add_constraint('code', '<>', '');
        $qb->add_order('name', 'ASC');
        $countries = $qb->execute_unchecked();

        if (count($countries) == 0)
        {
            debug_add('No countries found. You have to use org.routamc.positioning to import countries to database.');
        }

        $this->_populate_data($countries);
    }

    function _populate_data(&$countries)
    {
        foreach ($countries as $country)
        {
            $this->_data[$country->code] = $country->name;
        }
    }

    function get_name_for_key($key)
    {
        return $this->_data[$key];
    }

    function key_exists($key)
    {
        return array_key_exists($key, $this->_data);
    }

    function list_all()
    {
        return $this->_data;
    }

    /** Ignored. */
    function set_type(&$type) {}
}
?>