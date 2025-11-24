<?php
$lines = file('config.php');
foreach ($lines as $i => $line) {
    printf("%4d: %s", $i + 1, $line);
}
