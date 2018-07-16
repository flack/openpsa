<?php
$view = $data['view_salesproject'];
$salesproject = $data['salesproject'];
$formatter = $data['l10n']->get_formatter();
?>
<div class="content-with-sidebar">
    <div class="main salesproject">
        <h1>&(view['title']:h);</h1>
        <div class="midcom_helper_datamanager2_view">
            <div class="field">
             <div class="title"><?php echo $data['l10n']->get('code'); ?></div>
             <div class="value">&(view['code']:h);</div>
            </div>
            <div class="field">
             <div class="title"><?php echo $data['l10n']->get('state'); ?></div>
             <div class="value">&(view['state']:h);</div>
            </div>
            <div class="field">
             <div class="title"><?php echo $data['l10n_midcom']->get('description'); ?></div>
             <div class="value">&(view['description']:h);</div>
            </div>
            <?php if ($salesproject->state == org_openpsa_sales_salesproject_dba::STATE_ACTIVE) {
                ?>
                <div class="field">
                 <div class="title"><?php echo $data['l10n']->get('estimated closing date'); ?></div>
                 <div class="value">&(view['close_est']:h);</div>
                </div>
                <div class="field">
                 <div class="title"><?php echo $data['l10n']->get('probability'); ?></div>
                 <div class="value">&(view['probability']:h);</div>
                </div>
            <?php
            } ?>
            <div class="field">
             <div class="title"><?php echo $data['l10n']->get('value'); ?></div>
             <div class="value"><?php echo $formatter->number($salesproject->value); ?></div>
            </div>
            <div class="field">
             <div class="title"><?php echo $data['l10n']->get('profit'); ?></div>
             <div class="value"><?php echo $formatter->number($salesproject->profit); ?></div>
            </div>
            <?php
             $owner_card = org_openpsa_widgets_contact::get($salesproject->owner);
            ?>
            <div class="field">
             <div class="title"><?php echo $data['l10n']->get('owner'); ?></div>
             <div class="value"><?php echo $owner_card->show_inline(); ?></div>
            </div>
         <?php
         if (!empty($data['offers'])) {
             $wf = new midcom\workflow\datamanager;
         ?>
         <div class="field">
         <div class="title"><?php echo $data['l10n']->get('pdf file'); ?></div>
             <div class="value"><?php
                 foreach ($data['offers'] as $offer) {
                     echo '<span class="org_openpsa_helpers_fileinfo">';
                     $attachment = $offer->get_file();
                     $delete_link = $data['router']->generate('delete_offer', ['guid' => $offer->guid]);
                     $edit_link = $data['router']->generate('edit_offer', ['guid' => $offer->guid]);
                     if (!empty($attachment)) {
                         $url = midcom_db_attachment::get_url($attachment);
                         echo '<a href="' . $url . '" class="icon"><i class="fa fa-file-text-o"></i></a>';
                     }
                     echo '<span class="filename">' . $offer->get_number();
                     echo ' <a class="actions" href="' . $delete_link . '"><i class="fa fa-trash" title="' . $data['l10n_midcom']->get('delete') . '"></i></a>';
                     echo ' <a class="actions" ' . $wf->render_attributes() . ' href="' . $edit_link . '"><i class="fa fa-pencil" title="' . $data['l10n_midcom']->get('edit') . '"></i></a>';
                     echo "</span>\n";
                     echo ' <span class="updated">' . $formatter->datetime($offer->metadata->revised) . '</span>';
                     echo "</span>\n";
                 }
                 ?>
	         </div>
            </div>
            <?php  } ?>
        </div>
