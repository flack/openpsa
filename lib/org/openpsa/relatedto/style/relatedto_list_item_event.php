<?php
$link = $data['link'];
$event = $data['other_obj'];
?>

<li class="event" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title">&(data['title']:h);</span>
  <ul class="metadata">
    <li class="time location"><?php echo $data['l10n']->get_formatter()->timeframe($event->start, $event->end) . ($event->location ? ', ' . $event->location : ''); ?></li>
    <?php
    // Participants
    echo "<li class=\"members\">" . midcom::get()->i18n->get_string('participants', 'org.openpsa.calendar') . ": ";
    foreach ($event->participants as $person_id => $confirmed) {
        $participant_card = org_openpsa_widgets_contact::get($person_id);
        echo $participant_card->show_inline()." ";
    }
    echo "</li>\n";
    ?>
  </ul>

  <div id="org_openpsa_relatedto_details_url_&(event.guid);" style="display: none;" title="&(data['raw_url']);"></div>
    <div id="org_openpsa_relatedto_details_&(event.guid);" class="details hidden" style="display: none;">
    </div>
<?php
//TODO: necessary JS stuff to load details (which should in turn include the events own relatedtos) via AHAH
  org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']);
  ?>
</li>
