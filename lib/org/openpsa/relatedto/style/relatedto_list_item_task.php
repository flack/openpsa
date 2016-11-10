<?php
$link = $data['link'];
$task = $data['other_obj'];
?>

<li class="&(data['type']);" id="org_openpsa_relatedto_line_&(link['guid']);">
    <span class="icon">&(data['icon']:h);</span>
    <span class="title">&(data['title']:h);</span>
    <ul class="metadata">
    <?php
    // Deadline
    echo "<li>" . midcom::get()->i18n->get_string('deadline', 'org.openpsa.projects') . ": " . $data['l10n']->get_formatter()->date($task->end) . "</li>";

    // Resources
    echo "<li>" . midcom::get()->i18n->get_string('resources', 'org.openpsa.projects') . ": ";
    $task->get_members();
    foreach ($task->resources as $resource_id => $confirmed) {
        $resource_card = org_openpsa_widgets_contact::get($resource_id);
        echo $resource_card->show_inline() . " ";
    }
    echo "</li>\n";
    ?>
 </ul>
 <div id="org_openpsa_relatedto_details_&(task.guid);" class="details hidden" style="display: none;">
 </div>
<?php
    //TODO: necessary JS stuff to load details (which should in turn include the tasks own relatedtos) via AHAH
    org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']);
?>
</li>
