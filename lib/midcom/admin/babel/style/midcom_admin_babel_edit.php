<?php
$prefix = $data['plugin_anchorprefix'];

// available languages
$languages = $data['l10n']->_language_db;
// available compoments

$components = array_keys($_MIDCOM->componentloader->manifests);
asort($components);

$fallback_language = $_MIDCOM->i18n->get_fallback_language();
$deflang = $data['l10n']->get($languages[$fallback_language]['enname']);
$editlang = $data['l10n']->get($languages[$data['view_lang']]['enname']);
?>
<h1><?php 
    echo sprintf($data['l10n']->get('edit strings for %s [%s]'), $data['component_translated'], $editlang);
?></h1>

<form method="post" action="&(prefix);save/&(data['view_component']);/&(data['view_lang']);/" class="datamanager2">

<div class="form_toolbar">
  <input type="submit" name="f_submit" accesskey="s" value="<?php echo $data['l10n_midcom']->get('save');?>" class="save" />
  <input type="submit" name="f_cancel" accesskey="c" value="<?php echo $data['l10n_midcom']->get('cancel');?>" class="cancel" />
</div>
<br />
<table class="midcom_admin_babel_languages">
    <thead>
        <tr class="header">
            <th><?php echo $data['l10n']->get('string-id')?></th>
            <th>&(deflang); (<?php echo $data['l10n']->get('default');?>)</th>
            <?php 
            if ($data['view_lang'] !== $fallback_language)
            { 
                ?>
                <th>&(editlang);</th>
                <?php 
            } 
            ?>
        </tr>
    </thead>
    
    <tbody>
    
        <tr class="newstring">
            <td><?php echo $data['l10n']->get('new string')?><br /><input type="text" name="new_stringid" size="35" /></td>
            <td><textarea type="text" name="new_fallback" cols="30" rows="3" wrap="virtual"></textarea></td>
            <?php 
            if ($data['view_lang'] !== $fallback_language)
            { 
                ?>
                <td><textarea type="text" name="new_loc" cols="30" rows="3" wrap="virtual"></textarea></td>
                <?php 
            } 
            ?>
        </tr>

        <?php
        $count = 0;
        foreach ($data['view_strings'] as $id => $str) 
        {
            $fallback = $str[$fallback_language];
            $loc = $str[$data['view_lang']];
            
            if ($count % 2 === 0)
            {
                $row = 'even';
            }
            else
            {
                $row = 'odd';
            }
            ?>
            <tr class="string-&(row);">
                <th>&(id);</th>
                <?php 
                if ($data['view_lang'] !== $fallback_language)
                { 
                    ?>
                    <td><span>&(fallback);</span></td>
                    <?php 
                } 
                ?>
                <td><input type="hidden" name="string_id[&(count);]" value="&(id);" /><textarea name="string_value[&(count);]" cols="30" rows="3" wrap="virtual"><?php echo htmlspecialchars($loc); ?></textarea></td>
            </tr>   
            <?php
            $count++;
        }
        ?>
    </tbody>
</table>
<br />
<div class="form_toolbar">
  <input type="submit" name="f_submit" value="<?php echo $data['l10n_midcom']->get('save');?>" class="save" />
  <input type="submit" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel');?>" class="cancel" />
</div>

</form>