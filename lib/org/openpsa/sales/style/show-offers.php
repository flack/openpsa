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

             if (!empty($attachment)) {
                 $url = midcom_db_attachment::get_url($attachment);
                 echo '<a href="' . $url . '" class="icon"><i class="fa fa-file-text-o"></i></a>';
             }
             echo '<span class="info">';
             echo '<span class="filename">' . $offer->get_label();
             echo ' <a class="actions" href="' . $delete_link . '"><i class="fa fa-trash" title="' . $data['l10n_midcom']->get('delete') . '"></i></a>';
             echo ' <a class="actions" ' . $wf->render_attributes() . ' href="' . $edit_link . '"><i class="fa fa-pencil" title="' . $data['l10n_midcom']->get('edit') . '"></i></a>';
             echo "</span>\n";
             if (!empty($attachment)) {
                 echo ' <span class="date">' . $formatter->datetime($attachment->metadata->revised) . '</span> ';
                 $person_card = org_openpsa_widgets_contact::get($attachment->metadata->revisor);
                 echo $person_card->show_inline();
             }
             echo "</span></span></li>\n";
         }
         ?>
     </ul>
    </div>
<?php  } ?>