<?php
echo "<p>" . midcom::get()->i18n->get_string('recreating', 'midcom') . "</p>\n";

echo "<pre>\n";
foreach ($this->data['objects'] as $object) {
    $type = get_class($object);
    if (!isset($this->data['datamanagers'][$type])) {
        echo sprintf(midcom::get()->i18n->get_string('not recreating object %s %s, reason %s', 'midcom'), $type, $object->guid, 'No datamanager defined') . "\n";
        continue;
    }

    if (   !$object->can_do('midgard:update')
        || !$object->can_do('midgard:attachments')) {
        echo sprintf(midcom::get()->i18n->get_string('not recreating object %s %s, reason %s', 'midcom'), $type, $object->guid, 'Insufficient privileges') . "\n";
        continue;
    }

    echo sprintf(midcom::get()->i18n->get_string('recreating object %s %s', 'midcom'), $type, $object->guid) . ': ';
    $this->data['datamanagers'][$type]->set_storage($object);
    if (!$this->data['datamanagers'][$type]->recreate()) {
        echo "SKIPPED\n";
    } else {
        echo "OK\n";
    }
}
echo "</pre>\n";

echo "<p>" . midcom::get()->i18n->get_string('done', 'midcom') . "</p>\n";
