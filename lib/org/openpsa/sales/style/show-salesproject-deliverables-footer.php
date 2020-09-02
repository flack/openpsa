</div>

    </div>
    <aside>
        <div class="contacts area">
            <?php
            if ($customer = $data['salesproject']->get_customer()) {
                echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";
                echo $customer->render_link();
            }
            if ($data['salesproject']->contacts) {
                echo "<h2>" . midcom::get()->i18n->get_string('contacts', 'org.openpsa.projects') . "</h2>\n";
                foreach (array_keys($data['salesproject']->contacts) as $contact_id) {
                    $person_card = org_openpsa_widgets_contact::get($contact_id);
                    $person_card->show();
                }
            } ?>
        </div>

        <?php
        midcom_show_style('show-offers');

        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());

        //TODO: Configure whether to show in/both and reverse vs normal sorting ?
        midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['salesproject']->guid}/both/normal/");
        ?>
    </aside>
</div>