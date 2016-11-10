<?php
midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.services.auth/style.css');

$title = 'MidCOM Developers';

midcom::get()->auth->require_valid_user();

$developers = array();

foreach (midcom::get()->componentloader->manifests as $name => $manifest) {
    if (!array_key_exists('package.xml', $manifest->_raw_data)) {
        // This component is not yet packaged, skip
        continue;
    }

    $package_type = 'component';
    if ($manifest->purecode) {
        $package_type = 'library';
    }

    $maintainers = $manifest->_raw_data['package.xml']['maintainers'];
    if (is_array($maintainers)) {
        foreach ($maintainers as $person => $details) {
            $name_parts = explode(' ', trim($details['name']));
            $lastname = $name_parts[count($name_parts) - 1];
            $identifier = "{$lastname} {$person}";
            $developers[$identifier]['username'] = $person;
            $developers[$identifier]['name'] = $details['name'];

            if (!isset($details['email'])) {
                $developers[$identifier]['email'] = '';
            } else {
                $developers[$identifier]['email'] = $details['email'];
            }
            if (!isset($details['role'])) {
                $details['role'] = 'developer';
            }

            if (   array_key_exists('active', $details)
                && $details['active'] == 'no') {
                $details['role'] = sprintf(midcom::get()->i18n->get_string('not active %s', 'midcom'), midcom::get()->i18n->get_string($details['role'], 'midcom'));
            }

            $developers[$identifier]['roles'][$details['role']][$package_type][$name] = $manifest->get_name_translated($name);
        }
    }
}

ksort($developers);
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>Midgard CMS - <?php echo $title; ?></title>
        <?php echo midcom::get()->head->print_head_elements(); ?>
        <style type="text/css">
            <!--
            table.dev
            {
            }
            th.name
            {
                padding: 0 0 2 0;
                margin:0px;
                font-size:13px;
                border-bottom:1px solid #333333;
                border-right: 1px solid #333333;
            }
            th.role
            {
                padding-left: 4px;
                padding-bottom:2px;
                margin:0px;
                font-size:13px;
                border-bottom:1px solid #333333;
            }
            td.name
            {
                padding-right: 2px;
                padding-bottom:10px;
            }
            td.role
            {
                padding-left: 4px;
                padding-bottom:10px;
            }
            td.role dd img
            {
                border: none;
            }
            #content
            {
                font-size: 1.2em;
                text-align: left;
                width: 625px;
                margin: 0px 20px;
                height: 315px;
                overflow:auto;
            }
            #bottom #version
            {
                padding-top: 0px;
            }
            #bottom
            {
                font-size: 1.2em;
                height: 24px;
            }
            -->
        </style>
        <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/favicon.ico" />
    </head>

    <body>
    <div id="container">
        <div id="branding">
        <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
        <div id="grouplogo"><a href="http://midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.png" width="104" height="104" alt="Midgard" title="Midgard" /></a></div>
        </div>
        <div class="clear"></div>
        <div id="content">
            <table>
                <!--<thead>
                    <tr>
                        <th class="name">Name</th>
                        <th class="role">Components and roles</th>
                    </tr>
                </thead>-->
                <tbody>
                    <?php
                    foreach ($developers as $name => $details) {
                        $person_label  = "<div class=\"vcard\">\n";
                        $person_label .= "    <h2><a href=\"https://github.com/{$details['username']}\" class=\"url fn\">{$details['name']}</a></h2>\n";
                        $gravatar_url = "http://www.gravatar.com/avatar.php?gravatar_id=" . md5($details['email']) . "&amp;size=60";
                        $person_label .= "    <div><img class=\"photo\" src=\"{$gravatar_url}\" /></div>\n";
                        $person_label .= "    <div style=\"display: none;\"><a class=\"email\" href=\"mailto:{$details['email']}\">{$details['email']}</a></div>\n";
                        $person_label .= "</div>\n"; ?>
                        <tr>
                            <td class="name"><?php echo $person_label; ?></td>
                            <td class="role">
                                <dl>
                                <?php
                                foreach ($details['roles'] as $role => $packages_types) {
                                    ksort($packages_types);
                                    foreach ($packages_types as $package_type => $components) {
                                        ?>
                                        <dt>
                                            <?php
                                            printf(midcom::get()->i18n->get_string('%s of packages of type %s', 'midcom'), midcom::get()->i18n->get_string($role, 'midcom'), midcom::get()->i18n->get_string($package_type, 'midcom')); ?>
                                        </dt>
                                        <dd>
                                        <?php
                                        foreach ($components as $component => $component_name) {
                                            $icon = midcom::get()->componentloader->get_component_icon($component);
                                            echo "<a href=\"" . midcom::get()->get_host_prefix() . "__mfa/asgard/components/{$component}/\">";
                                            echo "<img src=\"" . MIDCOM_STATIC_URL . "/{$icon}\" alt=\"{$component_name} ({$component})\" title=\"{$component_name} ({$component})\" />";
                                            echo "</a>\n";
                                        } ?>
                                        </dd>
                                        <?php

                                    }
                                } ?>
                                </dl>
                            </td>
                        </tr>
                        <?php

                    }
                    ?>
                </tbody>
            </table>

            </div>
            <div id="bottom">
                <div id="version">Midgard <?php echo substr(mgd_version(), 0, 5); ?></div>
            </div>
            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2006 <a href="http://www.midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
        </div>
        </div>
    </body>
</html>
