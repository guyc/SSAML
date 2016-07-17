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
    static function Render($Template, $Args=null, $Disposition)
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

    static function XmlEntities($String)
    {
      // REVISIT - this is from http://lists.gnu.org/archive/html/help-gnu-emacs/2004-07/msg00049.html
      // Should use XML entities where they exist.
      $tr = array(
		  "\202" => ",",
		  "\203" => "f",
		  "\204" => ",,",
		  "\205" => "...",
		  "\213" => "<",
		  "\214" => "OE",
		  "\221" => "`",
		  "\222" => "'",
		  "\223" => "``",
		  "\224" => "\"",
		  "\225" => "*",
		  "\226" => "-",
		  "\227" => "--",
		  "\231" => "(TM)",
		  "\233" => ">",
		  "\234" => "oe",
		  "\264" => "'");

      

        $string = $String;
	foreach ($tr as $find=>$replace) {
	  $string = str_replace($find, $replace, $string);
	}

        $string = str_replace(array("&", "<", ">", "\"", "'"),
			      array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"),
			      $string);
        $string = utf8_encode($string);
	return $string;
    }
}

?>
