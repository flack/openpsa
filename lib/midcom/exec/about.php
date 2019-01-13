<?php
midcom::get()->head->add_stylesheet( MIDCOM_STATIC_URL . '/midcom.services.auth/style.css');
$title = 'About Midgard';
midcom::get()->auth->require_valid_user();
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>Midgard CMS - <?php echo $title; ?></title>
        <?php echo midcom::get()->head->print_head_elements(); ?>
        <style type="text/css">
            #content
            {
                font-size: 1.4em;
                line-height: 1.4;
                text-align: left;
                width: 607px;
                margin: 0 20px;
                padding: 0 1em 0 2.2em;
                height: 325px;
            }
            #content p
            {
                width: 45%;
            }
        </style>
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
    </head>

    <body>
    <div id="container">
        <div id="branding">
        <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
        <div id="grouplogo"><a href="http://www.midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.png" width="104" height="104" alt="Midgard" title="Midgard" /></a></div>
        </div>
        <div class="clear"></div>
        <div id="content">
            <img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/ragnaroek.gif" alt="Ragnaroek" style="float: right;" />
            <p>
                <a href="http://www.midgard-project.org/">Midgard CMS</a> is a Content management Toolkit. It is Free Software that can be used to construct interactive web applications. <a href="http://www.midgard-project.org/">Learn more &raquo;</a>
            </p>
            <p>See also list of <a href="https://github.com/flack/openpsa/graphs/contributors">developers</a> or <a href="<?php echo midcom_connection::get_url('self') . "__ais/help/midcom/"; ?>">read the documentation</a>.</p>
            <?php
            // TODO: Check if MidCOM is up to date
            ?>
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998&ndash;<?php echo date('Y'); ?> <a href="http://www.midgard-project.org/community/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
    </div>
    </body>
</html>
