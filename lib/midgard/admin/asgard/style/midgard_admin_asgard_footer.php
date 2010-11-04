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
                            
                            echo sprintf($_MIDCOM->i18n->get_string('created by %s on %s', 'midgard.admin.asgard'), "<a href=\"{$_MIDGARD['self']}__mfa/asgard/object/view/{$creator->guid}/\">$creator->name</a>", strftime('%c', $created)) . "\n";
                            if ($edited != $created)
                            {
                                $revision = $view_metadata->get('revision');
                                echo sprintf($_MIDCOM->i18n->get_string('last edited by %s on %s (revision %s)', 'midgard.admin.asgard'), "<a href=\"{$_MIDGARD['self']}__mfa/asgard/object/view/{$editor->guid}/\">$editor->name</a>", strftime('%c', $edited), $revision) . "\n";
                            }
                        }

                        if (   isset($data['object'])
                            && $_MIDCOM->dbfactory->is_multilang($data['object']))
                        {
                            // FIXME: It would be better to reflect whether object is MultiLang
                            $object_langs = $data['object']->get_languages();
                            $object_lang_ids = array();
                            if (is_array($object_langs))
                            {
                                foreach ($object_langs as $object_lang)
                                {
                                    $object_lang_ids[] = $object_lang->id;
                                }
                            }

                            $lang_qb = midcom_baseclasses_database_language::new_query_builder();
                            $lang_qb->add_order('name');
                            $langs = $lang_qb->execute();
                            $default_mode = midgard_admin_asgard_plugin::get_default_mode($data);
                            
                            echo "<select class=\"language_chooser\" onchange=\"window.location='{$_MIDGARD['self']}__mfa/asgard/object/{$default_mode}/{$data['object']->guid}/' + this.options[this.selectedIndex].value;\">\n";
                            echo "    <option value=\"\">" . $_MIDCOM->i18n->get_string('default language', 'midgard.admin.asgard') . "</option>\n";
                            foreach ($langs as $lang)
                            {
                                $class_extra = '';
                                if (in_array($lang->id, $object_lang_ids))
                                {
                                    $class_extra = ' exists';
                                }

                                $selected = '';
                                if ($lang->code == $data['language_code'])
                                {
                                    $selected = ' selected="selected"';
                                }

                                echo "    <option value=\"{$lang->code}\" class=\"{$lang->code}{$class_extra}\"{$selected}>{$lang->name}</option>\n";
                            }
                            echo "</select>\n";
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
