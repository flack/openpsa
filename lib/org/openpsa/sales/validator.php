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
        // check channels
        if (   (   empty($fields['end_date'])
                || $fields['end_date'] == '0000-00-00')
            && empty($fields['continuous']))
        {
            $result['end'] = midcom::get()->i18n->get_string('select either end date or continuous', 'org.openpsa.sales');
            return $result;
        }

        return true;
    }
}
?>