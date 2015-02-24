<?php

require 'Converter.php';
require 'vendor/autoload.php';

set_time_limit(0);
ini_set("auto_detect_line_endings", true);

$converter = new Converter($argv[1], isset($argv[2]) ? $argv[2] : null);
$converter->pdf();
