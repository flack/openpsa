<?php
function openpsa_test_create_dir(string $dir = '')
{
    if ($dir) {
        $dir = OPENPSA2_UNITTEST_OUTPUT_DIR . '/' . $dir;
    } else {
        $dir = OPENPSA2_UNITTEST_OUTPUT_DIR;
    }
    if (!is_dir($dir) && !mkdir($dir)) {
        throw new Exception('could not create directory ' . $dir);
    }
}

function openpsa_prepare_directories() {
    if (file_exists(OPENPSA2_UNITTEST_OUTPUT_DIR)) {
        $ret = false;
        $output = system('rm -Rf ' . OPENPSA2_UNITTEST_OUTPUT_DIR, $ret);

        if ($ret) {
            throw new Exception('Could not remove old output dir: ' . $output);
        }
    }

    openpsa_test_create_dir();
    openpsa_test_create_dir('rcs');
    openpsa_test_create_dir('themes');
    openpsa_test_create_dir('cache');
    openpsa_test_create_dir('cache/blobs');
    openpsa_test_create_dir('tmp');
    openpsa_test_create_dir('blobs');

    $subdirs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F'];
    foreach ($subdirs as $dir) {
        openpsa_test_create_dir('blobs/' . $dir);
        foreach ($subdirs as $subdir) {
            openpsa_test_create_dir('blobs/' . $dir . '/' . $subdir);
        }
    }
}