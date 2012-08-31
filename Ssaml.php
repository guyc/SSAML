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

    static $PHPExcel = "PHPExcel";             // path to PHPExcel relative to this directory
    static $tempDir = null;                    // defaults to system temp directory
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

    static function TempName($Prefix)
    {
        $tempDir = self::$tempDir;
        if ($tempDir == null) $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, $Prefix);
    }

    // Write the template into an xslx file and render it as an upload
    static function Render($Template, $Args=null, $Headers=null)
    {
        $php = self::SsaslFileToPhp($Template);
        $xml = self::PhpToXlm($xml);
        $xlsxFileName = SsamlXlsx::XmlToXlsxFile($xml);

        $size = filesize($pdfFileName);
        $pdfMimeType = self::$pdfMimeType;

        if ($Headers) {
            foreach ($Headers as $header) {
                header($header);
            }
        }
        header("Content-Length: {$size}");
        header("Content-Type: {$xmlxMimeType}");

        $fileHandle = fopen($pdfFileName, "rb");
        fpassthru($fileHandle);
        fclose($fileHandle);

        if (!self::$keepTempFiles) unlink($pdfFileName);
    }
    static function RenderInline($Template, $Args=null)
    {
        $headers = array("Content-Disposition"=>"inline");
        self::Render($Template, $Args, $headers);
    }

    static function RenderAttachment($Template, $Filename, $Args=null)
    {
        $headers = array("Content-Disposition: attachment; filename=\"{$Filename}\"");
        self::Render($Template, $Args, $headers);
    }
}

?>
