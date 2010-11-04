<?php
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
           "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo $_MIDCOM->get_context_data(MIDCOM_CONTEXT_PAGETITLE); ?></title>
        <?php
        echo $_MIDCOM->print_head_elements();
        ?>
    </head>
    <body <?php $_MIDCOM->print_jsonload(); ?>>