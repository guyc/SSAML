<?php

/*
 * If you are NOT using a class autoloader 
 * you should require_once 'FomlConfig.php'
 * to pre-load all of the Foml classes.
 */

class Ssaml
{

    const PHP_MODE = 'php';
    const XML_MODE = 'xml';

    static $keepTempFiles = false;
    static $PHPExcel = "PHPExcel";             // path to PHPExcel relative to this directory
    static $xmlxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    static function SsamlToPhp($Ssaml)
    {
        $php = SsamlParser::ParseString();
        return $php;
    }

    static function SsamlFileToPhp($SsamlFile)
    {
        $php = SsamlParser::ParseFile($SsamlFile);
        return $php;
    }

    // returns Ssaml XML as a string
    static function PhpToXml($Php, $Args=null)
    {
        if ($Args) {
            foreach ($Args as $key=>$value) {
                $$key = $value;
            }
        }

        ob_start();
        eval("?".">".$Php);  // prefixed with ? > to exit implicit php mode
        $xml = ob_get_contents();
        ob_end_clean();
        return $xml;
    }

    // Write the template into an xslx file and render it as an upload
    static function Render($Template, $Args=null, $Disposition)
    {
        $php = self::SsamlFileToPhp($Template);
        $xml = self::PhpToXml($php, $Args);
        $xlsx = new SsamlXlsx($xml);
        $xlsx->Render($Disposition);
    }
    static function RenderInline($Template, $Args=null)
    {
        $disposition = 'inline';
        self::Render($Template, $Args, $disposition);
    }

    static function RenderAttachment($Template, $Filename, $Args=null)
    {
        $disposition = "attachment; filename=\"{$Filename}\"";
        self::Render($Template, $Args, $disposition);
    }

    static function XmlEntities($String)
    {
        return str_replace(array("&", "<", ">", "\"", "'"),
                           array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"),
                           $String);
    }
}

?>
