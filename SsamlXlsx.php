<?php

class SsamlXlsx
{
    static public $tags = array(
                                'workbook',
                                'sheet',
                                'row',
                                'cell'
                                );

    function __construct($Filename=null)
    {
        $this->fileName = $Filename ? $Filename : Ssaml::TempName('spreadsheet-');
        $this->workbook = new PHPExcel();
        $this->sheet = $this->workbook->getActiveSheet();
        $this->rowNo = 0;
        $this->colNo = 0;
        $this->cdata = null;  // becomes an array of cdata strings
        print "filename={$this->fileName}<br/>";
    }

    function WriteToFile()
    {
        $writer = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel2007');
        $writer->save($this->fileName);
    }

    function StartWorkbook($Parser, $Attrib)
    {
        $cell = $this->sheet->getCellByColumnAndRow(5,5);
        $cell->setValue('this is 5,5');
    }
    
    function EndWorkbook()
    {
    }

    function StartSheet($Parser, $Attrib)
    {
        if ($this->sheet == null) {
            $this->sheet = $this->workbook->createSheet();
        }
        $this->rowNo = 0;
        $this->colNo = 0;
    }

    function EndSheet()
    {
        $this->sheet = null;
    }

    function StartRow($Parser, $Attrib)
    {
        $this->colNo = 0;
    }

    function EndRow()
    {
        $this->rowNo++;
    }

    function StartCell($Parser, $Attrib)
    {
        $this->cdata = array();
    }

    function EndCell()
    {
        // oddly row numbers are 1-based and column numbers are 0-based
        if (count($this->cdata)) {
            $value = join(' ',$this->cdata);
            print "setting cell at row {$this->rowNo} col {$this->colNo} to {$value}</br>";
            $cell = $this->sheet->getCellByColumnAndRow($this->colNo, $this->rowNo+1);
            $cell->setValue($value);
        }
        $this->cdata = null;
        $this->colNo++;
    }

    //----------------------------------------------------------------------

    function CharacterData($Parser, $Data)
    {
        if (is_array($this->cdata)) {
            $this->cdata[] = trim($Data);
        }
    }

    function StartElement($Parser, $Name, $Attrib)
    {
        print "start {$Name}<br/>";
        $tag = strtolower($Name);
        if (in_array($tag, self::$tags)) {
            $method = 'Start'.ucfirst($tag);
            $this->$method($Parser, $Attrib);
        } else {
            $lineNo = xml_get_current_line_number($Parser);
            trigger_error("Unknown tag {$Name} at line {$lineNo}", E_USER_ERROR);
        }
    }

    function EndElement($Parser, $Name)
    {
        print "end {$Name}<br/>";
        $tag = strtolower($Name);
        if (in_array($tag, self::$tags)) {
            $method = 'End'.ucfirst($tag);
            $this->$method();
        } else {
            $lineNo = xml_get_current_line_number($Parser);
            trigger_error("Unknown tag {$Name} at line {$lineNo}", E_USER_ERROR);
        }

    }

    //----------------------------------------------------------------------

    function Parse($XmlString)
    {
        $xmlParser = xml_parser_create();
        xml_set_object($xmlParser, $this);
        xml_set_element_handler($xmlParser, 
                                "StartElement",
                                "EndElement"
                                );
        xml_set_character_data_handler($xmlParser,
                                       'CharacterData'
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
        $xlsx->Parse($XmlString);
        $xlsx->WriteToFile();
        return $xlsx->filename;
    }
}

?>