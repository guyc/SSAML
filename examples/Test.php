<?php

require_once "../SsamlConfig.php";

date_default_timezone_set('Australia/Brisbane');

// debugging preformatted recursive dump
function Dump($Var)
{
    print "<pre>";
    print_r($Var);
    print "</pre>";
}

$args = array('title'=>'Jumping Jupiters');
SSaml::RenderAttachment('Test.ssaml', 'Test.xlsx', $args);
