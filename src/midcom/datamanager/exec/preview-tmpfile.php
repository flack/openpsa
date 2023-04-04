<?php
use Symfony\Component\Mime\FileBinaryMimeTypeGuesser;

if (empty($_GET['identifier'])) {
    throw new midcom_error_notfound('Identifier missing');
}
$path = midcom::get()->config->get('midcom_tempdir') . '/tmpfile-' . $_GET['identifier'];

if (!is_readable($path)) {
    throw new midcom_error_notfound('File not found');
}

$guesser = new FileBinaryMimeTypeGuesser();
$mimetype = $guesser->guessMimeType($path);

if (!in_array($mimetype, ['image/jpeg', 'image/gif', 'image/png'])) {
    throw new midcom_error_notfound('Unsupported mimetype');
}

midcom::get()->header('Content-Type: ' . $mimetype);
readfile($path);
