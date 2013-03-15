<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package midcom.baseclasses
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.sales rest order handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_rest_order extends midcom_baseclasses_components_handler_rest
{

    public function get_object_classname()
    {
        return "";
    }
    
    /**
     * searches for an salesproject the deliverable for the given person can be created for
     * will autogenerate one if none is found
     * 
     * @param int $person_id
     * @return org_openpsa_sales_salesproject_dba
     */
    private function _get_salesproject($person_id)
    {
        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb->add_constraint('customerContact', '=', $person_id);
        $results = $qb->execute();
        
        if (count($results) > 0)
        {
            return array_pop($results);
        }
        
        // create a new salesproject    
        $salesproject = new org_openpsa_sales_salesproject_dba();
        $salesproject->customerContact = $person_id;
        
        // add logged in user as salesproject owner
        $salesproject->owner = midcom::get('auth')->user->get_storage()->id;
        
        $salesproject->title = "";
        if (isset($this->_request['params']['salesproject_title']))
        {
            $salesproject->title = $this->_request['params']['salesproject_title'];
        }
        // add username to salesproject title
        $person = new org_openpsa_contacts_person_dba($person_id);
        $salesproject->title .= $person->lastname . ", " . $person->firstname;
        $stat = $salesproject->create();
        if (!$stat)
        {
            $this->_stop("Failed creating salesproject: " . midcom_connection::get_error_string());
        }
        
        return $salesproject;
    }
    
    /**
     * create an order 
     * this needs to get an person id and product id posted
     */
    public function handle_create()
    {    
        $person_id = isset($this->_request['params']['person_id']) ? intval($this->_request['params']['person_id']) : false;
        $product_id = isset($this->_request['params']['product_id']) ? intval($this->_request['params']['product_id']) : false;

        // check param
        if (!$person_id || !$product_id)
        {
            $this->_stop("missing param for creating the order");
        }
        $salesproject = $this->_get_salesproject($person_id);
        
        // create deliverable and add it to the salesproject
        // get the product we want to add
        $product = new org_openpsa_products_product_dba($product_id);

        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->salesproject = $salesproject->id;
        
        $deliverable->copyFromProduct($product);
                
        $deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_NEW;
        $deliverable->start = gmmktime(0, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'));
      
        $stat = $deliverable->create();
        if (!$stat)
        {
            $this->_stop("Failed creating deliverable: " . midcom_connection::get_error_string());
        }
        
        // is a subscription?
        if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
        {
            $continuous = isset($this->_request['params']['continuous']) ? ((bool) $this->_request['params']['continuous']) : false;
            $deliverable->continuous = $continuous;
            // setting schema parameter to subscription
            $deliverable->set_parameter('midcom.helper.datamanager2', 'schema_name', 'subscription');
        }

        // finally, order the product
        $stat = $deliverable->order();
        
        if (!$stat)
        {
            $this->_stop("Failed ordering deliverable: " . midcom_connection::get_error_string());
        }
        
        $this->_object = $deliverable;
        $this->_responseStatus = 200;
        $this->_response["id"] = $this->_object->id;
        $this->_response["message"] = "order created";
    }
}
?>