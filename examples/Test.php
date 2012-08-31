<?php

require_once "../SsamlConfig.php";

// debugging preformatted recursive dump
function Dump($Var)
{
    print "<pre>";
    print_r($Var);
    print "</pre>";
}

print "<h3>PHP</h3>";
$php = Ssaml::SsamlFileToPhp('Test.ssaml');
Dump(htmlentities($php));

print "<h3>XML</h3>";
$xml = SSaml::PhpToXml($php);
Dump(htmlentities($xml));
$file = SsamlXlsx::XmlToXlsxFile($xml);

