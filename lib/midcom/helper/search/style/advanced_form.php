<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

// Map, stimestamps => text
// default is 1, 3, 6 and a year
$lastmod_content = array
(
    0 => $data['l10n']->get('no limit'),
    strtotime('-1 month') => $data['l10n']->get('since 1 month'),
    strtotime('-3 month') => $data['l10n']->get('since 3 months'),
    strtotime('-6 month') => $data['l10n']->get('since 6 months'),
    strtotime('-1 year') => $data['l10n']->get('since 1 year')
);

// Prepare the topic and component listings, this is a bit work intensive though,
// we need to traverse everything.
function midcom_helper_search_process_node ($node_id, &$nap, &$topics, &$components, $prefix, &$data)
{
    $node = $nap->get_node($node_id);

    if (   ! array_key_exists($node[MIDCOM_NAV_COMPONENT], $components)
        && $node[MIDCOM_NAV_COMPONENT] != 'midcom.helper.search')
    {
        $i18n = $_MIDCOM->get_service('i18n');
        $l10n = $i18n->get_l10n($node[MIDCOM_NAV_COMPONENT]);
        $components[$node[MIDCOM_NAV_COMPONENT]] = $l10n->get($node[MIDCOM_NAV_COMPONENT]);
    }
    $topics[$node[MIDCOM_NAV_FULLURL]] = "{$prefix}{$node[MIDCOM_NAV_NAME]}";

    // Recurse
    $prefix .= "{$node[MIDCOM_NAV_NAME]} &rsaquo; ";
    $subnodes = $nap->list_nodes($node_id);
    foreach ($subnodes as $sub_id)
    {
        midcom_helper_search_process_node($sub_id, $nap, $topics, $components, $prefix, $data);
    }
}

$nap = new midcom_helper_nav();
$topics = Array();
$components = Array();

$topics[''] = $data['l10n']->get('search anywhere');
$components[''] = $data['l10n']->get('search all content types');

midcom_helper_search_process_node($nap->get_root_node(), $nap, $topics, $components, '', $data);
$_MIDCOM->load_library('midcom.helper.xsspreventer');
$query = midcom_helper_xsspreventer::escape_attribute($data['query']);

?>
<form method='get' name='midcom_helper_search_form' action='&(prefix);result/' class='midcom.helper.search'>
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
foreach ($topics as $url => $name)
{
    $selected = ($data['request_topic'] == $url) ? ' selected' : '';
?>
                <option&(selected); value='&(url);'>&(name:h);</option>
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
foreach ($components as $id => $name)
{
    $selected = ($data['component'] == $id) ? ' selected' : '';
?>
                <option&(selected); value='&(id);'>&(name:h);</option>
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


foreach ($lastmod_content as $timestamp => $name)
{
    $selected = (abs($data['lastmodified'] - $timestamp) < 10000) ? ' selected' : '';
?>
                <option&(selected); value='&(timestamp);'>&(name:h);</option>
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
