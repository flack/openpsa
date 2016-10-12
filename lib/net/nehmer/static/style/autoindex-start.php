<h1><?php printf($data['l10n']->get('directory index for %s'), $data['topic']->extra); ?></h1>

<table cellpadding='3'>
  <thead>
    <tr>
      <td><?php $data['l10n']->show('filename');?></td>
      <td><?php $data['l10n']->show('filedescription');?></td>
      <td><?php $data['l10n']->show('filetype');?></td>
      <td><?php $data['l10n']->show('filesize');?></td>
      <td><?php $data['l10n']->show('file lastmodified');?></td>
    </tr>
  </thead>
