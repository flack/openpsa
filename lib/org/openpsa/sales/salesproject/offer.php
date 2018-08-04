<?php
/**
 * @package org.openpsa.sales
 * @author Robert Jannig, http://www.content-control-berlin.de/
 * @copyright Content Control, http://www.content-control-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to offers
 *
 * @property integer $id Local non-replication-safe database identifier
 * @property integer $salesproject
 * @property string $designation Official designation of the company
 * @property string $introduction Introduction sentences for further information
 * @property string $notice further information related to the salesproject
 * @property string $offer_number Number of the offer
 * @property string $guid
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_offer_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_offer';

    public function get_file()
    {
        foreach ($this->list_attachments() as $att) {
            return $att;
        }
        return null;
    }

    public function get_label()
    {
        $label = $this->get_number();
        if ($this->designation) {
            $label .= ': ' . $this->designation;
        }
        return $label;
    }

    public function get_number()
    {
        return $this->get_parent()->code . '-' . $this->id;
    }
}