<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

if ($data['max_pages'] > 1)
{
?>

<p class="midcom_helper_search_result_nav">
<?php
    $querystring = 'query=' . urlencode($data['query']);
    $querystring .= '&type=' . urlencode($data['type']);
    if ($data['type'] == 'advanced')
    {
        $querystring .= '&topic=' . urlencode($data['request_topic']);
        $querystring .= '&component=' . urlencode($data['component']);
        $querystring .= '&lastmodified=' . urlencode($data['lastmodified']);
    }

    echo $data['l10n']->get('pages') . ': ';

    if ($data['page'] > 1)
    {
        $page = urlencode($data['page'] - 1);
        $url = "{$prefix}result/?{$querystring}&page={$page}";
        $desc = $data['l10n']->get('previous page');
        echo "<a href='{$url}'>&lArr; {$desc}</a>&nbsp;&nbsp;&nbsp;";
    }

    for ($i = 1; $i <= $data['max_pages']; $i++)
    {
        if ($i == $data['page'])
        {
            echo "$i ";
        }
        else
        {
            $page = urlencode($i);
            $url = "{$prefix}result/?{$querystring}&page={$page}";
            echo "<a href='{$url}'>${i}</a> ";
        }
    }

    if ($data['page'] < $data['max_pages'])
    {
        $page = urlencode($data['page'] + 1);
        $url = "{$prefix}result/?{$querystring}&page={$page}";
        $desc = $data['l10n']->get('next page');
        echo "&nbsp;&nbsp;&nbsp;<a href='{$url}'>{$desc} &rArr;</a>";
    }

?>
</p>
<?php
}
?>