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
        
	public function handle_get() 
	{
        // not implemented
    }
        
    public function handle_create() 
    {
        $this->perform('org_openpsa_contacts_person_dba');
    }
    
    public function handle_update() 
    {
        $this->perform('org_openpsa_contacts_person_dba');
    }

    public function handle_delete() 
    {
        // not implemented
    }
}
?>