<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales viewer interface class.
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * Generic request startup work:
     *
     * - Add the LINK HTML HEAD elements
     */
    public function _on_handle($handler, array $args)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.sales/sales.css");

        midcom::get()->auth->require_valid_user();
    }

    public static function get_unit_options()
    {
        $unit_options = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options');
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.products');
        $options = [];
        foreach ($unit_options as $key => $name) {
            $options[$key] = $l10n->get($name);
        }
        return $options;
    }

    public static function get_unit_option($unit)
    {
        $unit_options = self::get_unit_options();
        if (array_key_exists($unit, $unit_options)) {
            return $unit_options[$unit];
        }
        return '';
    }
}
