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
         if (!empty($data['offers'])) {
             $wf = new midcom\workflow\datamanager;
             $formatter = $data['l10n']->get_formatter();
         ?>
         <div class="area org_openpsa_helper_box"><h3><?php echo $data['l10n']->get('offers'); ?></h3>
			<ul><?php
                 foreach ($data['offers'] as $offer) {
                     echo '<li><span class="org_openpsa_helpers_fileinfo">';
                     $attachment = $offer->get_file();
                     $delete_link = $data['router']->generate('delete_offer', ['guid' => $offer->guid]);
                     $edit_link = $data['router']->generate('edit_offer', ['guid' => $offer->guid]);
                     $person_card = org_openpsa_widgets_contact::get($attachment->metadata->revisor);

                     if (!empty($attachment)) {
                         $url = midcom_db_attachment::get_url($attachment);
                         echo '<a href="' . $url . '" class="icon"><i class="fa fa-file-text-o"></i></a>';
                     }
                     echo '<span class="filename">' . $offer->get_label();
                     echo ' <a class="actions" href="' . $delete_link . '"><i class="fa fa-trash" title="' . $data['l10n_midcom']->get('delete') . '"></i></a>';
                     echo ' <a class="actions" ' . $wf->render_attributes() . ' href="' . $edit_link . '"><i class="fa fa-pencil" title="' . $data['l10n_midcom']->get('edit') . '"></i></a>';
                     echo "</span>\n";
                     echo ' <span class="date">' . $formatter->datetime($attachment->metadata->revised) . '</span>';
                     echo $person_card->show_inline();
                     echo "</span></li>\n";
                 }
                 ?>
	         </ul>
            </div>
        <?php  } ?>

        <?php
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());

        //TODO: Configure whether to show in/both and reverse vs normal sorting ?
        midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['salesproject']->guid}/both/normal/");
        ?>
    </aside>
</div>