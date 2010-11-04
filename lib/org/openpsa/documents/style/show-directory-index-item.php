<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view =& $data['document_dm'];
$document =& $data['document'];
$att =& $data['document_attachment'];
// MIME type
$icon = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
if ($view['document'])
{
    $icon = midcom_helper_get_mime_icon($att['mimetype']);
}
$class = 'visited';
if (!$data['visited'])
{
    $class = 'new';
}
?>
<div class="&(class); list_item"><?php
?>

<a id="document_&(document.guid);" style="text-decoration: none;" href="&(data['prefix']);document/&(document.guid);/">
    <?php
    if ($icon)
    {
        ?>
        <span class="icon"><img src="&(icon);" <?php
        if ($view['document'])
        {
            echo 'title="' . sprintf($data['l10n']->get("%s %s document"), midcom_helper_filesize_to_string($att['filesize']), $data['l10n']->get($att['mimetype'])).'" alt="' . $view['title'] . '" ';
        }
        ?>style="border: 0px;"/></span>
        <?php
    }

    if ($att)
    {?>
    <script type="text/javascript">
        jQuery('#document_&(document.guid);').bind('contextmenu', function(e){
            jQuery.ajax
            ({
                type: "POST",
                url: "&(_MIDGARD['prefix']);/midcom-exec-org.openpsa.documents/mark_visited.php",
                data: "guid=&(document.guid);"
            });
            window.location.href = "&(att['url']);";
            jQuery('#document_&(document.guid);').css('fontWeight', 'normal');
            return false;
        });
    </script>
    <?php }
    ?>
    <br />
    &(view['title']);
</a>
</div>