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

    // use in ssaml file to import another file.
    static function Import($Template, $Args=null)
    {
        $php = self::SsamlFileToPhp($Template);
        $xml = self::PhpToXml($php, $Args);
        print $xml;
    }               

    // Write the template into an xslx file and render it as an upload
    static function Render($Template, $Args, $Disposition)
    {
        $php = self::SsamlFileToPhp($Template);
        $xml = self::PhpToXml($php, $Args);
        $xlsx = new SsamlXlsx($xml);
        return $xlsx->Render($Disposition);
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

    // Input is assumed to be UTF-8, so it is escaped as-is.
    // This used to fold CP1252 punctuation bytes down to ASCII and then call
    // utf8_encode() to convert Latin-1 to UTF-8.  Against UTF-8 input that byte
    // mapping corrupts multi-byte characters -- it rewrites continuation bytes,
    // so an em dash became &quot; -- and utf8_encode() double-encodes anything
    // non-ASCII that survives.  utf8_encode() is also deprecated as of PHP 8.2.
    static function XmlEntities($String)
    {
        return str_replace(array("&", "<", ">", "\"", "'"),
			   array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"),
			   (string)$String);
    }
}

?>
