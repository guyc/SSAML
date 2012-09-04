<?php
class SsamlXlsx 
{
    static public $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; //'application/msexcel';
    static public $tags = array(
                                'workbook',
                                'sheet',
                                'row',
                                'cell'
                                );

    public $workbook;
    public $sheet;
    public $cell;
    public $cdata;
    public $col;
    public $row;
    public $cellRowSpan;
    public $cellColSpan;

    function __construct($XmlString=null)
    {
        $this->workbook = new PHPExcel();
        $this->sheet = $this->workbook->getActiveSheet();
        $this->row = 0;
        $this->col = 0;
        $this->cdata = null;  // becomes an array of cdata strings
        $this->headers = array('Content-Type'=>self::$mimeType);

        if ($XmlString!==null) {
            $this->Parse($XmlString);
        }
    }

    // by default writes to php:://output
    function WriteToFile($FileName='php://output')
    {
        $writer = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel2007');
        $writer->save($FileName);
    }

    function StartWorkbook($Attrib)
    {
        $cell = $this->sheet->getCellByColumnAndRow(5,5);
        $cell->setValue('this is 5,5');
    }
    
    function EndWorkbook()
    {
    }

    //----------------------------------------------------------------------
    // Sheet

    function StartSheet($Attrib)
    {
        if ($this->sheet == null) {
            $this->sheet = $this->workbook->createSheet();
        }

        $this->SetAttributes('Sheet', $Attrib);
        $this->row = 0;
        $this->col = 0;
    }

    function EndSheet()
    {
        $this->sheet = null;
    }

    function SetSheetName($Value)
    {
        $this->sheet->setTitle($Value);
    }

    //----------------------------------------------------------------------
    // Row

    function StartRow($Attrib)
    {
        $this->col = 0;
    }

    function EndRow()
    {
        $this->row++;
    }

    //----------------------------------------------------------------------
    // Cell

    function StartCell($Attrib)
    {
        $this->cdata = array();
        $this->SetAttributes('Cell', $Attrib);
        $this->cell = $this->sheet->getCellByColumnAndRow($this->col, $this->row+1);
    }

    function EndCell()
    {
        // oddly row numbers are 1-based and column numbers are 0-based
        if (count($this->cdata)) {
            $value = join(' ',$this->cdata);
            //print "setting cell at row {$this->row} col {$this->col} to {$value}</br>";
            $this->cell->setValue($value);
        }

        if ($this->cellColSpan || $this->cellRowSpan) {
            $colSpan = intval($this->cellColSpan);
            $rowSpan = intval($this->cellRowSpan);
            if ($colSpan<1) $colSpan=1;
            if ($rowSpan<1) $rowSpan=1;
            $coord0 = $this->sheet->getCellByColumnAndRow($this->col, $this->row+1)->getCoordinate();
            $coord1 = $this->sheet->getCellByColumnAndRow($this->col+$colSpan-1, $this->row+$rowSpan-1+1)->getCoordinate();
            $range = "{$coord0}:{$coord1}";
            $this->sheet->mergeCells($range);
            $this->cellColSpan = null;
            $this->cellRowSpan = null;
        }

        $this->cdata = null;
        $this->col++;
    }

    function SetCellRowspan($Value)
    {
        $this->cellColSpan = $Value;
    }

    function SetCellColspan($Value)
    {
        $this->cellRowSpan = $Value;
    }

    function SetCellFontWeight($Value)
    {
        switch (strtolower($Value)) {
            case 'bold':
                $this->Style()->getFont()->setBold(true);
                break;
            default:
                $this->error("Unknown cell font-weight value '${Value}'");
        }
    }

    function SetCellTextAlign($Value)
    {
        switch (strtolower($Value)) {
            case 'left':
                $alignment = PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
                break;
            case 'right':
                $alignment = PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
                break;
            default:
                $this->error("Unknown cell text-align value '${Value}'");
        }
        $this->Style()->getAlignment()->setHorizontal($alignment);
    }

    //----------------------------------------------------------------------

    function Style()
    {
        return $this->sheet->getStyleByColumnAndRow($this->col, $this->row+1);
    }

    // eg text-align => TextAlign
    function AttributeNameToMethod($Name)
    {
        $parts = array();
        foreach (explode('-',$Name) as $part) {
            $parts[] = ucfirst(strtolower($part));
        }
        return join('', $parts);
    }

    // Call $this->Set{$NodeType}{$AttribName}($Value) for each attrib
    function SetAttributes($NodeType, $Attrib)
    {
        foreach ($Attrib as $key=>$value) {
            $method = "Set{$NodeType}".$this->AttributeNameToMethod($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->error("Unknown attribute {$key} for tag SHEET");
            }
        }
    }

    function Error($Message)
    {
        $lineNo = xml_get_current_line_number($this->parser);
        trigger_error($Message." at line {$lineNo}", E_USER_ERROR);
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
        //print "start {$Name}<br/>";
        $tag = strtolower($Name);
        if (in_array($tag, self::$tags)) {
            $method = 'Start'.ucfirst($tag);
            $this->$method($Attrib);
        } else {
            $this->Error("Unknown tag {$Name}");
        }
    }

    function EndElement($Parser, $Name)
    {
        //print "end {$Name}<br/>";
        $tag = strtolower($Name);
        if (in_array($tag, self::$tags)) {
            $method = 'End'.ucfirst($tag);
            $this->$method();
        } else {
            $this->Error("Unknown tag {$Name}");
        }
    }

    //----------------------------------------------------------------------

    function Parse($XmlString)
    {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 
                                "StartElement",
                                "EndElement"
                                );
        xml_set_character_data_handler($this->parser,
                                       'CharacterData'
                                       );
        if (!xml_parse($this->parser, $XmlString, true)) {
            $this->error("XML error: ".xml_error_string(xml_get_error_code($this->parser)));
        }
        xml_parser_free($this->parser);
    }

    function Render($Disposition)
    {
        $fileName = tempnam("/tmp", "worksheet.xls");
        $this->WriteToFile($fileName);
        $size = filesize($fileName);
        $this->headers['Content-Length'] = $size;
        $this->headers['Content-disposition'] = $Disposition;

        foreach ($this->headers as $key=>$value) 
        {
            header("{$key}: {$value}");
        }
        $fileHandle = fopen($fileName, "rb");
        fpassthru($fileHandle);
        fclose($fileHandle);
        unlink($fileName);
        if (!Ssaml::$keepTempFiles) unlink($fileName);
    }

    static function RenderInline($XmlString)
    {
        $disposition = 'inline';
        $xlsx = new SsamlXlsx($XmlString);
        $xlsx->Render($disposition);
    }

    static function RenderAttachment($XmlString, $FileName)
    {
        $disposition = "attachment; filename=\"{$FileName}\"";
        $xlsx = new SsamlXlsx($XmlString);
        $xlsx->Render($disposition);
    }
}

?>