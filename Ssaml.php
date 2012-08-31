<?php

/*
 * If you are NOT using a class autoloader 
 * you should require_once 'FomlConfig.php'
 * to pre-load all of the Foml classes.
 */

class Ssaml
{
    static $PHPExcel = "PHPExcel";             // path to PHPExcel relative to this directory
    static $tempDir = null;                    // defaults to system temp directory
    static $xmlxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    static function GeneratePhp($Template)
    {
        $ssmlParser = new SsamlParser();
        $php = $ssmlParser->ParseFile($Template);
        return $php;
    }

    // returns Ssaml XML as a string
    static function GenerateSsaml($Template, $Args=null)
    {
        ob_start();
        self::RenderSsaml($Template, $Args);
        $ssml = ob_get_contents();
        ob_end_clean();
        return $xslFo;
    }

    static function RenderSsaml($Template, $Args)
    {
        // import variables
        if ($Args) {
            foreach ($Args as $key=>$value) {
                $$key = $value;
            }
        }
        $_php = self::GeneratePhp($Template);
        //Dump(htmlspecialchars($_php)); return;
        eval("?".">".$_php);  // prefixed with ? > to exit implicit php mode
    }

    static function TempName($Prefix)
    {
        $tempDir = self::$tempDir;
        if ($tempDir == null) $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, $Prefix);
    }

    static function SsamlToXmlx($Ssaml)
    {
        $ssmlDir = dirname(__FILE__);
    }

    // Write the template into an xslx file and render it as an upload
    static function Render($Template, $Args=null, $Headers=null)
    {
        $xslFo = self::GenerateXslFo($Template, $Args);
        $pdfFileName = self::XslFoToPdf($xslFo);
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
