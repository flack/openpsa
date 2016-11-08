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
            <!--
            #content
            {
                font-size: 1.2em;
                text-align: left;
                width: 625px;
                margin: 0px 20px;
                padding: 0px 1em;
                height: 325px;
            }
            #bottom #version
            {
                padding-top: 50px;
            }
            table.apps td
            {
                padding-left: 5px;
            }
            -->
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
            <?php
            if (extension_loaded('midgard'))
            {
                ?>
                <p>
                        The <a href="http://www.midgard-project.org/midgard/8.09/">Ragnaroek LTS</a> generation of Midgard is supported until <strong>October 1st 2013</strong>.
                </p>
                <?php
            }
            ?>
            <p>
                    Copyright &copy;1999&ndash;<?php echo date('Y'); ?> <a href="http://www.midgard-project.org/community/">The Midgard Project</a>. <a href="http://www.gnu.org/licenses/lgpl.html">Free software</a>.
            </p>
            <table class="apps">
                <caption>Your installed applications</caption>
                <thead>

                </thead>
                <tbody>
                    <tr>
                        <td><?php
                            if (!extension_loaded('midgard'))
                            {
                                echo "<a href=\"http://midgard-project.org/midgard2/\">Midgard2</a>";
                            }
                            else
                            {
                                echo "<a href=\"http://midgard-project.org/midgard1/\">Midgard</a>";
                            }
                            ?></td>
                        <td><?php echo mgd_version(); ?></td>
                        <td>Content Repository</td>
                    </tr>
                    <tr>
                        <td><a href="http://midgard-project.org/midcom/">MidCOM</a></td>
                        <td><?php echo midcom::get_version(); ?></td>
                        <td>Component Framework for PHP</td>
                    </tr>
                    <tr>
                        <td><a href="http://www.php.net/">PHP</a></td>
                        <td><?php echo phpversion(); ?></td>
                        <td>Web programming language</td>
                    </tr>
                    <?php
                    // FIXME: IIRC, there was a function for getting this info
                    $server_software = explode(' ', $_SERVER['SERVER_SOFTWARE']);
                    $apache = explode('/', $server_software[0]);
                    if (!empty($apache))
                    {
                        switch ($apache[0])
                        {
                            case 'lighttpd':
                                $server_url = 'http://www.lighttpd.net/';
                                break;
                            case 'nginx';
                                $server_url = 'http://nginx.net/';
                                break;
                            default:
                                $server_url = 'http://httpd.apache.org/';
                        }
                        ?>
                    <tr>
                        <td><?php echo "<a href=\"{$server_url}\">" . ucfirst($apache[0]) . "</a>"; ?></td>
                        <td><?php echo $apache[1]; ?></td>
                        <td>Web server</td>
                    </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <p>See also list of <a href="<?php echo midcom_connection::get_url('self') . "midcom-exec-midcom/credits.php"; ?>">MidCOM Components and Developers</a> or <a href="<?php echo midcom_connection::get_url('self') . "__ais/help/midcom/"; ?>">read the documentation</a>.</p>
            <?php
            // TODO: Check if MidCOM is up to date
            ?>
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998&ndash;<?php echo date('Y'); ?> <a href="http://midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
    </div>
    </body>
</html>
