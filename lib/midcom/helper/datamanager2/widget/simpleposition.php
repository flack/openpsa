<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple positioning widget
 *
 * This widget enables user to input coordinates of an object
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_simpleposition extends midcom_helper_datamanager2_widget
{
    /**
     * The initialization event handler verifies the used type.
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (   !isset($this->_type->location)
            || !is_object($this->_type->location))
        {
            debug_add("Warning, the field {$this->name} does not have a location object as member, you cannot use the simpleposition widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        return true;
    }

    /**
     * Adds a pair of password input fields as a group to the form.
     */
    function add_elements_to_form($attributes)
    {
        $title = $this->_translate($this->_field['title']);
        $this->_form->addElement('text', "{$this->name}_latitude", $_MIDCOM->i18n->get_string('latitude', 'org.routamc.positioning'), Array('class' => 'shorttext'));
        $this->_form->addElement('text', "{$this->name}_longitude", $_MIDCOM->i18n->get_string('longitude', 'org.routamc.positioning'), Array('class' => 'shorttext'));
        $this->_form->addRule("{$this->name}_latitude", $this->_translate('validation failed: numeric'), 'regex', '/^-?[0-9]*([.,][0-9]*)?$/');
        $this->_form->addRule("{$this->name}_longitude", $this->_translate('validation failed: numeric'), 'regex', '/^-?[0-9]*([.,][0-9]*)?$/');
    }

    function freeze()
    {
        $latitude = $this->_form->getElement("{$this->name}_latitude");
        if (method_exists($latitude, 'freeze'))
        {
            $latitude->freeze();
        }
        $longitude = $this->_form->getElement("{$this->name}_longitude");
        if (method_exists($longitude, 'freeze'))
        {
            $longitude->freeze();
        }
    }

    function unfreeze()
    {
        $latitude = $this->_form->getElement("{$this->name}_latitude");
        if (method_exists($latitude, 'unfreeze'))
        {
            $latitude->unfreeze();
        }
        $longitude = $this->_form->getElement("{$this->name}_longitude");
        if (method_exists($longitude, 'unfreeze'))
        {
            $longitude->unfreeze();
        }
    }

    function is_frozen()
    {
        $latitude = $this->_form->getElement("{$this->name}_latitude");
        return $latitude->isFrozen();
    }

    function get_default()
    {
        return Array
        (
            "{$this->name}_latitude" => $this->_type->location->latitude,
            "{$this->name}_longitude" => $this->_type->location->longitude,
        );
    }

    function sync_type_with_widget($results)
    {
        if ($results["{$this->name}_latitude"] != '')
        {
            $this->_type->location->latitude = $results["{$this->name}_latitude"];
        }
        if ($results["{$this->name}_longitude"] != '')
        {
            $this->_type->location->longitude = $results["{$this->name}_longitude"];
        }
    }
}
?>