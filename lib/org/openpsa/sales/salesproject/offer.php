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
 * @property integer $salesproject
 * @property string $designation Official designation of the company
 * @property string $introduction Introduction sentences for further information
 * @property string $notice further information related to the salesproject
 * @property string $deliverables Deliverables (serialized)
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_offer_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_offer';

    public function get_file() : ?midcom_db_attachment
    {
        return $this->list_attachments()[0] ?? null;
    }

    public function get_label() : string
    {
        $label = $this->get_number();
        if ($this->designation) {
            $label .= ': ' . $this->designation;
        }
        return $label;
    }

    public function get_number() : string
    {
        return $this->get_parent()->code . '-' . $this->id;
    }
}