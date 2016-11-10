<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
<head>
<meta charset="UTF-8">
<style type="text/css">
body
{
    padding: .4em 1em;
    margin: 0;
}
body .shell-error
{
    background-color: red;
    color: white;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, .1);
}

#midcom_services_uimessages_wrapper div.midcom_services_uimessages_message
{
    background-color: #3465a4;
    color: #ffffff;
    font-family: sans-serif;
    padding: 2px 4px !important;
    border-radius: 4px;
    margin-bottom: 4px;
}

#midcom_services_uimessages_wrapper div.midcom_services_uimessages_message div.midcom_services_uimessages_message_type,
#midcom_services_uimessages_wrapper div.midcom_services_uimessages_message div.midcom_services_uimessages_message_title
{
    display: none;
}

#midcom_services_uimessages_wrapper div.midcom_services_uimessages_message div.midcom_services_uimessages_message_msg
{
    display: inline;
}

#midcom_services_uimessages_wrapper div.msu_ok
{
    background-color: #73d216;
}

#midcom_services_uimessages_wrapper div.msu_warning
{
    background-color: #f57900;
}

#midcom_services_uimessages_wrapper div.msu_error
{
    background-color: #cc0000;
}

</style>
</head>
<body>
<pre>
<?php
if (!empty($data['code'])) {
    try {
        ?>&(data['code']:p);<?php

    } catch (Exception $e) {
        echo '<div class="shell-error">' . $e->getMessage() . '</div>';
        echo htmlentities($e->getTraceAsString());
    }
} else {
    midcom::get()->uimessages->show_simple();
}
?>
</pre>
</body>
</html>