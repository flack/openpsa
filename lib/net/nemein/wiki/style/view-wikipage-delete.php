<?php
$view = $data['wikipage_view'];
?>

<h1>&(view['title']:h);</h1>

<form method="post" class="datamanager" action="<?php echo $_MIDGARD['uri']; ?>">
    <label for="net_nemein_wiki_deleteok">
        <span class="field_text"><?php echo $data['l10n']->get('really delete page'); ?></span>
        <input type="submit" id="net_nemein_wiki_deleteok" name="net_nemein_wiki_deleteok" value="<?php echo $data['l10n_midcom']->get('yes'); ?>" />
    </label>
</form>
<?php
if (array_key_exists('content', $view))
{
    if ($view['content'] != '')
    {
        ?>
        &(view["content"]:h);
        <?php
    }
    else
    {
        echo "<p class=\"stub\">" . $data['l10n']->get('this page is stub') . "</p>";
    }
}
?>