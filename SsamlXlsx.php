<?php

class SsamlXlsx
{
    function __construct()
    {
    }

    function StartElement($Parser, $Name, $Attrib)
    {
        print "start {$Name}<br/>";
    }

    function EndElement($Parser, $Name)
    {
        print "end {$Name}<br/>";
    }

    function Parse($XmlString)
    {
        $xmlParser = xml_parser_create();
        xml_set_element_handler($xmlParser, 
                                array($this, "startElement"),
                                array($this, "endElement")
                                );
        if (!xml_parse($xmlParser, $XmlString, true)) {
            die(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xmlParser)),
                        xml_get_current_line_number($xmlParser)));
        }
        xml_parser_free($xmlParser);
    }

    static function XmlToXlsxFile($XmlString)
    {
        $xlsx = new SsamlXlsx();
        $filename = $xlsx->Parse($XmlString);
        return $filename;
    }
}

?>