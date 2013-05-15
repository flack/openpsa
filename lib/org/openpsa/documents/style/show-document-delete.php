<div class="sidebar">
    <?php midcom_show_style("show-directory-navigation"); ?>
</div>
<div class="main">
    <div class="area">
        <h2><?php echo midcom::get('i18n')->get_string('confirm delete', 'org.openpsa.core'); ?></h2>
        <p><?php echo midcom::get('i18n')->get_string('use the buttons below or in toolbar', 'org.openpsa.core'); ?></p>
        <?php
        $data['document_dm']->display_view();
        $data['controller']->display_form();
        ?>
    </div>
</div>