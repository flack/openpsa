<?php
//check if username is unique
function check_username($fields)
{
    $result = array();
    if (!empty($fields["username"]))
    {
        $err = $_MIDCOM->i18n->get_string("username_already_exists","org.openpsa.contacts");
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('username', '=', $fields["username"]);
        if (isset($fields['person']))
        {
            $qb->add_constraint('guid', '<>', $fields["person"]);
        }
        if ($qb->count() > 0)
        {
            $result["username"] = $err;
        }
    }

    if ($result)
    {
        return $result;
    }
    else
    {
        return true;
    }
}
?>