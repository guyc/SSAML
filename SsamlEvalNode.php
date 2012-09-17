<?php
class SsamlEvalNode extends SsamlNode
{
    public $mode = Ssaml::PHP_MODE;
    const MATCH_RE = "/^=\s*(.*)/";

    function __construct($Matches)
    {
        $this->code = $Matches[1];
    }

    function RenderPrefix()
    {
        print "print Ssaml::XmlEntities(";
        print $this->code;
        print ");\n";
    }
}