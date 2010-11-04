<?php
$_MIDCOM->auth->require_admin_user();
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$_MIDCOM->componentloader->load('org.openpsa.invoices');
$billing_attributes = array('invoiceStreet' => 'street', 'invoiceCity' => 'city' , 'invoicePostcode' => 'postcode' ,
    'invoiceCountry' => 'country' , 'vatNo' => 'vatNo' , 'invoiceDue' =>'due' ,
    'invoiceVat' => 'vat' , 'invoiceDistribution' => 'delivery' , 'official' => 'recipient');

$mc = new midgard_collector('org_openpsa_billing_data' , 'sitegroup' , 1);
$mc->set_key_property('id');
$mc->add_value_property('linkGuid');
$mc->execute();

$keys = $mc->list_keys();
foreach ($keys as $key => $empty)
{
    $keys[$key] = $mc->get_subkey($key ,'linkGuid');
}

$qb = org_openpsa_contacts_group_dba::new_query_builder();
if (!empty($keys))
{
    $qb->add_constraint('guid', 'NOT IN', $keys);
}

$qb->begin_group('OR');
foreach ($billing_attributes as $org_attribute_name => $billingData_attribute_name)
{
    $qb->add_constraint($org_attribute_name, '<>', '');
}
$qb->end_group();

$organizations = $qb->execute();

foreach ($organizations as $org)
{
    echo "Starting Migration of Organization: " . $org->official . " (ID: " . $org->id . ")\n";

    $new_invoice_data = new org_openpsa_invoices_billing_data_dba();
    foreach ($billing_attributes as $org_attribute_name => $billingData_attribute_name)
    {
        echo "Copying " . $org_attribute_name ." to " . $billingData_attribute_name . " \n";
        $new_invoice_data->$billingData_attribute_name = $org->$org_attribute_name;
    }

    //use contact address is empty
    if (empty($org->invoiceStreet))
    {
        $new_invoice_data->useContactAddress = true;
    }
    else
    {
        $new_invoice_data->useContactAddress = false;
    }

    $new_invoice_data->linkGuid = $org->guid;

    if ($new_invoice_data->create())
    {
        echo "Created billing_data for Organization \n\n";
        flush();
    }
    else
    {
        echo "Couldn't create billing_data for Organization !!!\n\n";
        echo "Aborting \n";
        flush();
        break;
    }
}

echo "Done.\n";
echo "</pre>";
flush();
ob_start();
?>