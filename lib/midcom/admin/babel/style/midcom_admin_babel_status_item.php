<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$languages = $data['l10n']->_language_db;

if ($data['string_counts']['total'] > 0)
{
    $percentage = round(100 / $data['string_counts']['total'] * $data['string_counts']['translated']);

    if ($percentage >= 96)
    {
        $status = 'ok';
    }
    elseif ($percentage >= 75)
    {
        $status = 'acceptable';
    }
    else
    {
        $status = 'bad';
    }
}
else
{
    $status = 'ok';
}

echo "<tr class=\"{$status}\">\n";
echo "    <th class=\"component\"><a href=\"{$prefix}__mfa/asgard_midcom.admin.babel/edit/{$data['component']}/{$data['language']}/\"><img src=\"". MIDCOM_STATIC_URL . "/{$data['icon']}\" alt=\"\" />{$data['component']}</a></th>\n";
echo "    <td>{$data['string_counts']['translated']}</td>\n";
echo "    <td>{$data['string_counts']['total']}</td>\n";
if ($data['string_counts']['total'] > 0)
{
    echo "    <td>{$percentage}%</td>\n";
}
else
{
    echo "    <td>n/a</td>\n";
}
echo "</tr>\n";
?>