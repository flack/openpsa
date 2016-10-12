                    </div>
                    <div id="object_metadata">
                        <?php
                        if (!empty($data['object']->guid))
                        {
                            echo "GUID: {$data['object']->guid}, ID: {$data['object']->id}.\n";
                        }
                        $view_metadata = midcom::get()->metadata->get_view_metadata();
                        if ($view_metadata)
                        {
                            try
                            {
                                $creator = new midcom_db_person($view_metadata->get('creator'));
                                $creator_string = "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$creator->guid}/\">$creator->name</a>";
                            }
                            catch (midcom_error $e)
                            {
                                $creator_string = $data['l10n']->get('unknown person');
                            }
                            $created = (int) $view_metadata->get('created');
                            printf($data['l10n']->get('created by %s on %s'), $creator_string, strftime('%c', $created)) . "\n";

                            $edited = (int) $view_metadata->get('revised');
                            $revision = $view_metadata->get('revision');
                            if (   $revision > 1
                                && $edited != $created)
                            {
                                try
                                {
                                    $editor = new midcom_db_person($view_metadata->get('revisor'));
                                    $editor_string = "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$editor->guid}/\">$editor->name</a>";
                                }
                                catch (midcom_error $e)
                                {
                                    $editor_string = $data['l10n']->get('unknown person');
                                }

                                printf($data['l10n']->get('last edited by %s on %s (revision %s)'), $editor_string, strftime('%c', $edited), $revision) . "\n";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div id="navigation">
                    <?php
                    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
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
                    echo $data['l10n']->get('asgard for');
                    if (!extension_loaded('midgard'))
                    {
                        echo " Midgard2 ";
                    }
                    else
                    {
                        echo " Midgard ";
                    }
                    echo mgd_version();
                ?></strong>.
                Copyright &copy; 1998 - <?php echo date('Y'); ?> <a href="http://www.midgard-project.org/" rel="powered">The Midgard Project</a>.
                Midgard is a <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under
                <a href="http://www.gnu.org/licenses/lgpl.html" rel="license" about="http://www.midgard-project.org/">GNU Lesser General Public License</a>.<br />&nbsp;
            </span>
        </div>
    </body>
</html>
