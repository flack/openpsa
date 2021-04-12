<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.products
 */
trait org_openpsa_products_handler
{
    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     */
    public function update_breadcrumb_line(midcom_core_dbaobject $object) : array
    {
        $tmp = [];
        $root_group = $this->_config->get('root_group');

        while ($object) {
            $parent = $object->get_parent();

            if ($object instanceof org_openpsa_products_product_dba) {
                $tmp[] = [
                    MIDCOM_NAV_URL => "product/{$object->guid}/",
                    MIDCOM_NAV_NAME => $object->title,
                    ];
            } else {
                if ($object->guid === $root_group) {
                    break;
                }

                $tmp[] = [
                    MIDCOM_NAV_URL => $object->guid . '/',
                    MIDCOM_NAV_NAME => $object->title,
                ];
            }
            $object = $parent;
        }
        return array_reverse($tmp);
    }
}
