#!/usr/bin/php
<?php
$command = getenv('SSH_ORIGINAL_COMMAND');
if ($_SERVER['argc'] > 0) {
    $user = $_SERVER['argv'][1];
    passthru('git-shell -c "' . $command . '"');
    file_put_contents('tmp.txt', $user, FILE_APPEND);
}
