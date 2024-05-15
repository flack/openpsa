<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Form validation functionality
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_validator
{
    public function validate_subscription(array $fields)
    {
        $result = $this->validate_units($fields);

        if (   empty($fields['end'])
            && empty($fields['continuous'])) {
            if ($result === true) {
                $result = [];
            }
            $result['end'] = midcom::get()->i18n->get_string('select either end date or continuous', 'org.openpsa.sales');
        }
        return $result ?: true;
    }

    public function validate_units(array $fields)
    {
        if (   empty($fields['invoiceByActualUnits'])
            && empty($fields['plannedUnits'])) {
            return [
                'plannedUnits' => midcom::get()->i18n->get_string('select either planned units or invoice by actual units', 'org.openpsa.sales')
            ];
        }

        return true;
    }
}
