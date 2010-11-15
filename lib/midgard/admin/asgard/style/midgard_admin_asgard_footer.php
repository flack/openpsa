                    </div>
                    <div id="object_metadata">
                        <?php
                        if (isset($data['object']->guid))
                        {
                            echo "GUID: {$data['object']->guid}, ID: {$data['object']->id}.\n";
                        }
                        $view_metadata = $_MIDCOM->metadata->get_view_metadata();
                        if ($view_metadata)
                        {
                            $editor = new midcom_db_person($view_metadata->get('editor'));
                            $edited = (int) $view_metadata->get('revised');
                            $creator = new midcom_db_person($view_metadata->get('creator'));
                            $created = (int) $view_metadata->get('created');

                            echo sprintf($_MIDCOM->i18n->get_string('created by %s on %s', 'midgard.admin.asgard'), "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$creator->guid}/\">$creator->name</a>", strftime('%c', $created)) . "\n";
                            if ($edited != $created)
                            {
                                $revision = $view_metadata->get('revision');
                                echo sprintf($_MIDCOM->i18n->get_string('last edited by %s on %s (revision %s)', 'midgard.admin.asgard'), "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$editor->guid}/\">$editor->name</a>", strftime('%c', $edited), $revision) . "\n";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div id="navigation">
                    <?php
                    $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                    echo "<a href=\"{$prefix}__mfa/asgard/\">";
                    echo "<img src=\"" . MIDCOM_STATIC_URL . "/midgard.admin.asgard/asgard2.png\" id=\"asgard_logo\" title=\"Asgard\" alt=\"Asgard\" />";
                    echo "</a>\n";

                    if (isset($data['object']))
                    {
                        $navigation = new midgard_admin_asgard_navigation($data['object'], $data);
                    }
                    else
                    {
                        $navigation = new midgard_admin_asgard_navigation(null, $data);
                    }
                    $navigation->draw();
                    ?>
                </div>
            </div>
        </div>
        <div id="siteinfo">
            <span class="copyrights">
                <img src="<?php echo MIDCOM_STATIC_URL; ?>/midcom.services.toolbars/images/midgard-logo.png" alt="(M)" />
                <strong><?php
                    echo $_MIDCOM->i18n->get_string('asgard for', 'midgard.admin.asgard');
                    if (extension_loaded('midgard2'))
                    {
                        echo " Midgard2 ";
                    }
                    else
                    {
                        echo " Midgard ";
                    }
                    echo substr(mgd_version(), 0, 4);
                ?></strong>.
                Copyright &copy; 1998 - <?php echo date('Y'); ?> <a href="http://www.midgard-project.org/" rel="powered">The Midgard Project</a>.
                Midgard is a <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under
                <a href="http://www.gnu.org/licenses/lgpl.html" rel="license" about="http://www.midgard-project.org/">GNU Lesser General Public License</a>.<br />&nbsp;
            </span>
        </div>
    </body>
</html>
