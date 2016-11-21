<?php
echo "<rows>";
echo "<page>" . $data['page'] . "</page>";
echo "<total>" . count($data['entries']) ."</total>";
echo "<records>" . count($data['entries']) . "</records>";

//jqgrid need nodes in special form like following:
/*
  <row>
      <cell> $id </cell>
      <cell> $title </cell> (for sorting)
      <cell> $title/link to entry </cell>
      <cell> $description </cell>
      <cell> $index_followUp </cell> (timestamp for sorting)
      <cell> $followUp </cell> (shown html)
      <cell> $index_linked_object </cell>
      <cell> label of linked_object with a href etc. </cell>
      <cell> $closed </cell>
  </row>
*/
$workflow = new midcom\workflow\datamanager2;
foreach ($data['entries'] as $entry) {
    echo "<row>";
    echo "<cell>" . $entry->id . "</cell>"; ?>
        <cell><![CDATA[&(entry.title:h)]]></cell>
        <?php
        $link_html = "<![CDATA[";
    $link_html .= '<a href="' . $data['url_prefix'] . 'edit/' . $entry->guid . '" ' . $workflow->render_attributes() . '>';
    $link_html .= "<span>" . $entry->title . "</span></a>";
    $link_html .= "]]>";
    echo "<cell>" . $link_html . "</cell>"; ?>
        <cell><![CDATA[&(entry.text:h);]]></cell>
        <?php
        if ($entry->followUp == 0) {
            echo "<cell>none</cell>";
        } else {
            echo "<cell>" . date('Y-m-d', $entry->followUp) . "</cell>";
        }
    if ($data['show_object']) {
        echo "<cell>" . $data['linked_raw_objects'][$entry->linkGuid] . "</cell>";
        echo "<cell><![CDATA[" . $data['linked_objects'][$entry->linkGuid] . "]]></cell>";
    }
    if ($data['show_closed']) {
        echo "<cell>" . $data['l10n']->get(($entry->closed) ? 'finished' : 'open') . "</cell>";
    }

    echo "</row>";
}
echo "</rows>";
?>
