<?php
function validate_edit_form($fields)
{
    $result = is_username_available($fields);
    if (is_array($result))
    {
        return $result;
    }
    return verify_existing_password($fields);
}

function verify_existing_password($fields)
{
    if (midcom::get('auth')->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface'))
    {
        //User has the necessary rights, so we're good
        return true;
    }

    if (!midcom_connection::login($fields["username"], $fields["current_password"]))
    {
        return array
        (
            'current_password' => midcom::get('i18n')->get_string("wrong current password", "org.openpsa.user")
        );
    }
    return true;
}

function is_username_available($fields)
{
    $result = array();
    if (!empty($fields["username"]))
    {
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('username', '=', $fields["username"]);
        if (isset($fields['person']))
        {
            $qb->add_constraint('guid', '<>', $fields["person"]);
        }
        if ($qb->count() > 0)
        {
            $result["username"] = midcom::get('i18n')->get_string("username_already_exists", "org.openpsa.user");
        }
    }

    if (!empty($result))
    {
        return $result;
    }
    else
    {
        return true;
    }
}

function username_exists($fields)
{
    $result = array();
    $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
    $qb->add_constraint('username', '=', $fields["username"]);
    if ($qb->count() == 0)
    {
        $result["username"] = midcom::get('i18n')->get_string("unknown username", "org.openpsa.user");
    }

    if (!empty($result))
    {
        return $result;
    }
    else
    {
        return true;
    }
}

function email_exists($fields)
{
    $result = array();
    $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
    $qb->add_constraint('email', '=', $fields["email"]);
    $count = $qb->count();
    if ($count == 0)
    {
        $result["email"] = midcom::get('i18n')->get_string("unknown email address", "org.openpsa.user");
    }
    else if ($count > 1)
    {
        $result["email"] = midcom::get('i18n')->get_string("multiple entries found, cannot continue", "org.openpsa.user");
    }

    if (!empty($result))
    {
        return $result;
    }
    else
    {
        return true;
    }
}

function email_and_username_exist($fields)
{
    $result = array();
    $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
    $qb->add_constraint('email', '=', $fields["email"]);
    $qb->add_constraint('username', '=', $fields["username"]);
    $count = $qb->count();
    if ($count == 0)
    {
        $result["username"] = midcom::get('i18n')->get_string("no user found with this username and email address", "org.openpsa.user");
    }
    else if ($count > 1)
    {
        $result["username"] = midcom::get('i18n')->get_string("multiple entries found, cannot continue", "org.openpsa.user");
    }

    if (!empty($result))
    {
        return $result;
    }
    else
    {
        return true;
    }
}

?>