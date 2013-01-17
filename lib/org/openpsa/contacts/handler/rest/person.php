<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package midcom.baseclasses
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.contacts person rest handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_rest_person extends midcom_baseclasses_components_handler_rest
{

    public function get_object_classname()
    {
        return "org_openpsa_contacts_person_dba";
    }
    
    public function handle_create()
    {    
        parent::handle_create();
        
        // add to group
        if (isset($this->_request['params']['group_id']))
        {
            $group = new midcom_db_group(intval($this->_request['params']['group_id']));
            
            // if there is no such group, just skip it
            if ($group)
            {        
                $member = new midcom_db_member();
                $member->uid = $this->_object->id;
                $member->gid = $group->id;
        
                // deactivating activitystream and RCS entries generation (performance)
                $member->_use_activitystream = false;
                $member->_use_rcs = false;
                $member->create();
            }
        }
        
        // create salesproject
        $salesproject = new org_openpsa_sales_salesproject_dba();
        $salesproject->customerContact = $this->_object->id;
        
        // add logged in user as salesproject owner
        $salesproject->owner = midcom::get('auth')->user->get_storage()->id;
        
        $salesproject->title = "";
        if (isset($this->_request['params']['salesproject_title']))
        {
            $salesproject->title = $this->_request['params']['salesproject_title'];
        }
        // add username to salesproject title
        $salesproject->title .= $this->_object->username; 
        $stat = $salesproject->create();
        if (!$stat)
        {
            $this->_stop("Failed creating salesproject: " . midcom_connection::get_error_string());
        }
        
        // ..and add a deliverable to the salesproject
        if (isset($this->_request['params']['product_id']))
        {
            // get the product we want to add
            $product = new org_openpsa_products_product_dba(intval($this->_request['params']['product_id']));
            if ($product)
            {
                $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
                $deliverable->salesproject = $salesproject->id;
                $deliverable->product = $product->id;
                $deliverable->title = $product->title;
                if (isset($this->_request['params']['deliverable_units']))
                {
                    $num_units = intval($this->_request['params']['deliverable_units']);
                    $deliverable->units = $num_units;
                }
                
                $deliverable->unit = $product->unit;
                $deliverable->costPerUnit = $product->cost;
                $deliverable->costType = $product->costType;
                if ($product->costType == 'm')
                {
                    $deliverable->invoiceByActualUnits = true;
                }
                
                $deliverable->orgOpenpsaObtype = $product->delivery;
                $deliverable->description = $product->description;
                $deliverable->supplier = $product->supplier;
                
                $deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_NEW;
                $deliverable->start = gmmktime(0, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'));
                
                // causes "Failed to create the privilege. See debug level log for details."                
                $deliverable->pricePerUnit = $product->price;
                
                $stat = $deliverable->create();
                if (!$stat)
                {
                    $this->_stop("Failed creating deliverable: " . midcom_connection::get_error_string());
                }
                
                // is a subscription?
                if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
                {
                    $deliverable->continuous = true;
                    // setting schema parameter to subscription
                    $deliverable->set_parameter('midcom.helper.datamanager2', 'schema_name', 'subscription');
                }

                // order the product
                $stat = $deliverable->order();
                
                if (!$stat)
                {
                    $this->_stop("Failed ordering deliverable: " . midcom_connection::get_error_string());
                }
            }
        }
    }
}
?>