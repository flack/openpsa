<?php
//check if username is unique
function check_username($fields){
    $result = array();
    if(!empty($fields["username"])){
        $err = $_MIDCOM->i18n->get_string("username_already_exists","org.openpsa.contacts");
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('username','=',$fields["username"]);
        if($qb->count() > 0){
            $result["username"] = $err;
        }
    }

    if($result){
        return $result;
    }else{
        return true;
    }
}


//check password strength
function check_password_strength($fields){
    $result = array();
    $password_field = "org_openpsa_contacts_person_account_password";
    if(intval($fields[$password_field."_switch"])>0){
        $password = $fields[$password_field];
        $dummy = new org_openpsa_contacts_person_dba();
        if(!$dummy->check_password_strength($password)){
            $err = $_MIDCOM->i18n->get_string("password_too_weak","org.openpsa.contacts");
            $result[$password_field] = $err;
        }
    }

    if($result){
        return $result;
    }else{
        return true;
    }
}

?>