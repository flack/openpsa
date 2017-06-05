<?php
$view_types = [
    'today',
    'yesterday',
];
$formatter = $data['l10n']->get_formatter();
foreach (array_filter($view_types) as $type) {
    echo "<div class=\"area\">\n";
    echo "<h2>" . sprintf($data['l10n']->get("updated %s"), $data['l10n']->get($type)) . "</h2>\n";
    echo "<ul class=\"updated\">\n";
    foreach ($data[$type] as $document) {
        $class = explode('.', $document->component);
        $class = $class[count($class) - 1];

        $onclick = '';
        switch ($class) {
            case "calendar":
                $url = "#";
                $onclick = " onclick=\"javascript:window.open('{$document->document_url}', 'event', 'toolbar=0,location=0,status=0,height=600,width=300,resizable=1');\"";
                break;
            default:
                $url = $document->document_url;
                break;
        }

        try {
            if ($document->editor) {
                $editor = new midcom_db_person($document->editor);
            } else {
                $editor = new midcom_db_person($document->creator);
            }
            $contact = new org_openpsa_widgets_contact($editor);
            echo "<li class=\"updated-{$class}\"><a href=\"{$url}\"{$onclick}>{$document->title}</a> <div class=\"metadata\">" . $formatter->datetime($document->edited) . " (" . $contact->show_inline() . ")</div></li>\n";
        } catch (midcom_error $e) {
        }
    }
    echo "</ul></div>\n";
}
