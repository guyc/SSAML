<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SsamlXlsx
{
    static public $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; //'application/msexcel';
    static public $tags = array(
                                'xlsx',
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
    public $headers;
    public $parser;
    public $data;

    function __construct($XmlString=null)
    {
        $this->workbook = new Spreadsheet();
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
        $writer = IOFactory::createWriter($this->workbook, 'Xlsx');
        $writer->save($FileName);
    }

    function StartWorkbook($Attrib)
    {
        //$cell = $this->sheet->getCellByColumnAndRow(5,5);
        //$cell->setValue('this is 5,5');
    }

    function EndWorkbook()
    {
    }

    //----------------------------------------------------------------------
    // Xlsx
    // This is just the outer container of the document because every XML
    // document needs a single outer element.  It does nothing.
    function StartXlsx($Attrib)
    {
    }
    function EndXlsx()
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
        $this->SetAttributes('Row', $Attrib);
    }

    function EndRow()
    {
        $this->row++;
    }

    function SetRowFillColor($Color)
    {
        if ($Color != '') {
            $style = $this->RowStyle();
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($Color);
        }
    }

    function RowStyle()
    {
        $row = ($this->row+1);
        return $this->sheet->getStyle($row);
    }

    //----------------------------------------------------------------------
    // Cell

    function StartCell($Attrib)
    {
        $this->cdata = array();
        $this->cell = $this->sheet->getCellByColumnAndRow($this->col, $this->row+1);
        $this->SetAttributes('Cell', $Attrib);
    }

    function EndCell()
    {
        // oddly row numbers are 1-based and column numbers are 0-based
        if (count($this->cdata)) {
            $value = join(' ', $this->cdata);

            // having trouble with duplicates per : http://phpexcel.codeplex.com/workitem/19388
            // line split characters do not seem to be the cause.
            // Problem seems to be limited to opening in
            // OpenOffice (not Excel or LibreOffice)
            // Will leave for now.

            //$lines = explode("\n",$value);
            //$value = join("\r\n",$lines);
            //print "setting cell at row {$this->row} col {$this->col} to {$value}</br>";
            $this->cell->setValue($value);
            //if (count($lines)>1) {
            //$style = $this->Style();
            //$style->getAlignment()->setWrapText(true);
            //}
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
        $this->cellRowSpan = $Value;
    }

    function SetCellColspan($Value)
    {
        $this->cellColSpan = $Value;
    }

    function SetCellFontWeight($Value)
    {
        switch (strtolower($Value)) {
            case 'bold':
                $this->Style()->getFont()->setBold(true);
                break;
            default:
                $this->error("Unknown cell font-weight value '{$Value}'");
        }
    }

    function SetCellFillColor($Value)
    {
        if ($Value != '') {
            $this->Style()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($Value);
        }
    }

    function SetCellTextAlign($Value)
    {
        switch (strtolower($Value)) {
            case 'left':
                $alignment = Alignment::HORIZONTAL_LEFT;
                break;
            case 'right':
                $alignment = Alignment::HORIZONTAL_RIGHT;
                break;
            case 'center':
                $alignment = Alignment::HORIZONTAL_CENTER;
                break;
            default:
                $this->error("Unknown cell text-align value '{$Value}'");
        }
        $this->Style()->getAlignment()->setHorizontal($alignment);
    }

    function SetCellUrl($Value)
    {
        $this->cell->getHyperlink()->setUrl($Value);
    }

    function SetCellWrap($Value) // value is ignored
    {
        $this->Style()->getAlignment()->setWrapText(true);
    }

    function SetCellFormat($Value)
    {
        switch (strtolower($Value)) {
            case 'text':
                $format = NumberFormat::FORMAT_TEXT;
                break;
            case 'date':
                $format = 'dd/mm/yyyy'; // PHPExcel_Style_NumberFormat::FORMAT_DATE_DMYSLASH; // 'd/m/y' doesn't work with openoffice
                break;
            case 'currency':
                $format = NumberFormat::FORMAT_CURRENCY_USD_SIMPLE; // '"$"#,##0.00_-'
                break;
            default:
                $this->error("Unknown cell format value '{$Value}'");
                $format = $Value;
        }
        $this->Style()->getNumberFormat()->setFormatCode($format);
    }

    function SetCellWidth($Value)
    {
        $column = $this->ColumnAddress();
        $this->sheet->getColumnDimension($column)->setWidth($Value);
    }

    //----------------------------------------------------------------------

    function Style()
    {
        return $this->sheet->getStyleByColumnAndRow($this->col, $this->row+1);
    }

    function ColumnAddress()
    {
        return Coordinate::stringFromColumnIndex($this->col);
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

        if ($Disposition == 'file') {
            return $fileName;
        }

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
