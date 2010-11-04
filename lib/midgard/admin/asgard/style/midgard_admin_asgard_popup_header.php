<?php echo "<?xml version=\"1.0\"?>\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <title><(title)> - <?php echo $_MIDCOM->get_context_data(MIDCOM_CONTEXT_PAGETITLE); ?></title>
        <?php $_MIDCOM->print_head_elements(); ?>
    </head>
    <body <?php $_MIDCOM->print_jsonload(); ?>>
