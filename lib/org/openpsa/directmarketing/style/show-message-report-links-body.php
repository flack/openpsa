<?php
$link_data = $data['use_link_data'];
if (!isset($data['body_title'])) {
    $data['body_title'] = '';
}
$body_title = $data['body_title'];
if (!isset($data['body_class'])) {
    $data['body_class'] = '';
}
$body_class = " class='{$data['body_class']}'";
?>
            <tbody&(body_class);>
<?php       if (!empty($body_title)) {
    ?>
                <tr>
                    <th colspan=6 class="title">&(body_title);</th>
                </tr>
<?php
}
            $total = 0;

            foreach ($link_data['counts'] as $target => $count) {
                $total += $count['total'];
                $of_clicks = $link_data['percentages']['of_links'][$target]['total'];
                $of_recipients = $link_data['percentages']['of_recipients'][$target]['total'];
                $rule_ser = var_export($link_data['rules'][$target], true);
                $visual_width = round($of_clicks * 5);
                $target_label = $target;
                // Fetch target url and look for a heading to use
                $is_html = false;
                $url = trim(urldecode($target));
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    foreach (get_headers($url) as $header) {
                        if (preg_match('/^Content-Type: .*\/.*html/', $header)) {
                            $is_html = true;
                            break;
                        }
                    }
                }
                if ($is_html) {
                    debug_add("Trying to fetch '{$target}' and read title from there");
                    $remote_data = file_get_contents($url);
                    if ($remote_data) {
                        $regexs = [
                            /* The parentheses are funny because we need to always have the same key for the label */
                            "/(<h([1-3])>)(.*?)(<\/h\\2>)/msi",
                            "/(<meta name=['\"].*?title['\"] content=(['\"]))(.*?)\\2(\/>)/msi",
                            "/((<title>))(.*?)(<\/title>)/msi",
                        ];
                        foreach ($regexs as $regex) {
                            if (preg_match($regex, $remote_data, $title_matches)) {
                                debug_print_r("Got title_matches:", $title_matches);

                                if (!empty($title_matches[3])) {
                                    $target_label = strip_tags($title_matches[3]);
                                    break;
                                }
                            }
                        }
                        unset($remote_data);
                    }
                }
                $target_label_parts = array_filter(preg_split("/\s+/", $target_label));
                $target_label_new = '';
                // Mangle long words to avoid them blowing up the report
                foreach ($target_label_parts as $part) {
                    if (strlen($part) > 30) {
                        $part = "<span title='{$part}'>" . substr($part, 0, 12) . '...' . substr($part, -12) . '</span>';
                    }
                    $target_label_new .= $part . ' ';
                }
                $target_label = trim($target_label_new); ?>
                <tr>
                    <td><textarea name="oo_dirmar_rule_<?php echo md5($target); ?>" style="display: none;"><?php echo $rule_ser; ?></textarea>
                    <input type="hidden" name="oo_dirmar_label_<?php echo md5($target); ?>" value="<?php echo $target_label; ?>" />
                    <input type="radio" name="oo_dirmar_userule" value="<?php echo md5($target); ?>" /></td>
                    <td><a href="<?php echo trim(urldecode($target)); ?>" target="_blank" title="<?php echo $target; ?>"><?php echo $target_label; ?></a></td>
                    <td class="bargraph"><div style="width: <?php echo $visual_width; ?>px;" class="link_count_visualization">&nbsp;</div></td>
                    <td class="numeric"><?php echo $count['total']; ?></td>
                    <td class="numeric"><?php echo round($of_clicks, 2); ?></td>
                    <td class="numeric"><?php echo count($count) - 1; ?></td>
                    <td class="numeric"><?php echo round($of_recipients, 2); ?></td>
                </tr>
                <?php

            }
        ?>
                <tr class="totals">
                    <td colspan=3>&nbsp;</td>
                    <td class="numeric"><?php echo $total; ?></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="numeric"><?php echo round($link_data['percentages']['of_recipients']['total'], 2); ?></td>
                </tr>
            </tbody>
