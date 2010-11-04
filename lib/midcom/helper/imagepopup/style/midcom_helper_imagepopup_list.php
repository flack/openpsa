<div class="midcom_helper_imagepopup">
    <h1><?php echo $data['list_title']; ?></h1>
    
    <?php midcom_show_style("midcom_helper_imagepopup_navigation"); ?>

    <div id="files">
        <?php 
        $data['form']->display_form();
        ?>
    </div>
    
</div>