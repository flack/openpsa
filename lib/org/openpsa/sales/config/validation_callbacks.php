<?php
function validate_subscription($fields)
{
    // check channels
    if (   (   empty($fields['end_date'])
            || $fields['end_date'] == '0000-00-00')
        && empty($fields['continuous']))
    {
        $result['end'] = midcom::get('i18n')->get_string('select either end date or continuous', 'org.openpsa.sales');
        return $result;
    }

    return true;
}
?>