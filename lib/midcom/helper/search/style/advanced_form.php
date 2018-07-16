<?php
$action = $data['router']->generate('result');

// Map, timestamps => text
// default is 1, 3, 6 and a year
$lastmod_content = [
    0 => $data['l10n']->get('no limit'),
    strtotime('-1 month') => $data['l10n']->get('since 1 month'),
    strtotime('-3 month') => $data['l10n']->get('since 3 months'),
    strtotime('-6 month') => $data['l10n']->get('since 6 months'),
    strtotime('-1 year') => $data['l10n']->get('since 1 year')
];

$query = midcom_helper_xsspreventer::escape_attribute($data['query']);
?>
<form method='get' name='midcom_helper_search_form' action='&(action);' class='midcom.helper.search'>
<input type='hidden' name='type' value='advanced' />
<input type='hidden' name='page' value='1' />

<table cellspacing="0" cellpadding="3" border="0">
    <tr>
        <td><?php echo $data['l10n']->get('query');?>:</td>
        <td><input type='text' style="width: 20em;" name='query' value=&(query:h); /></td>
    </tr>
    <tr>
        <td><?php echo $data['l10n']->get('limit search by topic tree');?>:</td>
        <td>
            <select name="topic" size="1" style="width: 20em;">
<?php
foreach ($data['topics'] as $url => $name) {
    $selected = ($data['request_topic'] == $url) ? ' selected="selected"' : ''; ?>
                <option&(selected:h); value='&(url);'>&(name:h);</option>
<?php

}
?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo $data['l10n']->get('limit search by content type');?>:</td>
        <td>
            <select name="component" size="1" style="width: 20em;">
<?php
foreach ($data['components'] as $id => $name) {
    $selected = ($data['component'] == $id) ? ' selected="selected"' : ''; ?>
                <option&(selected:h); value='&(id);'>&(name:h);</option>
<?php

}
?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo $data['l10n']->get('limit search by last modified');?>:</td>
        <td>
            <select name="lastmodified" size="1" style="width: 20em;">
<?php
foreach ($lastmod_content as $timestamp => $name) {
    $selected = (abs($data['lastmodified'] - $timestamp) < 10000) ? ' selected="selected"' : ''; ?>
                <option&(selected:h); value='&(timestamp);'>&(name:h);</option>
<?php

}
?>
            </select>
        </td>
    </tr>
    <tr>
        <td colspan="2"><input type='submit' name='submit' value='<?php echo $data['l10n']->get('search');?>' /></td>
    </tr>
</table>
</form>
