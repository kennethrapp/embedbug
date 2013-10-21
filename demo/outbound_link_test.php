<?php

require_once(realpath("../src/EmbedBug/EmbedBug.php"));

error_reporting(E_ALL | E_STRICT);

$filter = array('url'=>array('filter'=>FILTER_VALIDATE_URL));


$myinputs = filter_input_array(INPUT_GET, $filter);

var_dump($myinputs);