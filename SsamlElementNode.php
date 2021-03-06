<?php
class SsamlElementNode extends SsamlNode
{
    // matches namespace:element[/]args
    const MATCH_RE = "/^%([-a-zA-Z0-9]+)\s*(.*?)(\/?)\s*$/";
    
    // %element(opts) 
    public $tag;
    public $selfClose;
    public $args;

    // REVISIT - should not allow children if it is autoclosed

    function __construct($Matches)
    {
        $tag       = $Matches[1];
        $args      = $Matches[2];
        $close     = $Matches[3];  // '/' if tag should self-close, '' otherwise

        $this->selfClose = $close=='/';
        $this->tag = $tag;
        $extra = $this->ParseArgs($args);
        $this->ParseExtra($extra);
    }

    // replace instances of #{...} with [php] print($1);[/php]
    function ExpandExpressions($Text)
    {
        $text = $Text;
        $expansion = '';
        while (preg_match("/\#\{(.*?)\}/", $text, $matches, PREG_OFFSET_CAPTURE)) {
            $matchLength = $matches[0][1];  // including length of markup
            $expression = $matches[1][0];
            $expansion .= substr($text, 0, $matchLength);
            $text = substr($text, $matchLength+strlen($matches[0][0]));
            $expansion .= '<?';
            $expansion .= 'php ';
            $expansion .= "print({$expression});";
            $expansion .= '?>';
        }
        $expansion.=$text;
        return $expansion;
    }

    function ParseArgs($Args)
    {
        // REVISIT - this is a piss-poor
        // hack for now until I write a real arg parser.
        // By making the paren matches non-hungry it will now support lines like this:
        // %fo:block(border-after-style="solid") = join(" ",array("Thao","Vang","Lor"))
        // but will not correctly match all possible uses of brackets and does not support
        // expansion of inline evaluation contexts #{$variable}
        // For now switch to hungry because it is harder to work around that deficiency
        if (preg_match("/^\((.*)\)(.*)/", $Args, $matches)) {
            //print "<pre>"; print_r($matches); print "</pre>";
            // REVISIT - this offers only minimal support for #{<phpexpression>}
            $this->args = $this->ExpandExpressions($matches[1]);
            $Args = $matches[2];
        }
        return $Args;
    }

    // The $Extra string may include a shorthand for certain other node types.
    // -.*  : execution context
    // =.*  : evaluation context
    // .*   : text node
    function ParseExtra($Extra)
    {
        $extNodes = array('SsamlExecNode', 'SsamlEvalNode', 'SsamlTextNode');

        // SsamlTextNode matches everything

        $Extra = trim($Extra);
        if ($Extra != "") {
            foreach ($extNodes as $nodeClass) {
                if (preg_match($nodeClass::MATCH_RE, $Extra, $matches)) {
                    $this->children[] = new $nodeClass($matches);
                    break;
                }
            }
        }
    }

    function Render()
    {
        parent::Render();
    }

    function RenderPrefix()
    {
        print "<{$this->tag} {$this->args}";
        if ($this->selfClose) {
            print "/>\n"; 
        } else {
            print ">\n"; 
        }
    }

    function RenderSuffix()
    {
        if (!$this->selfClose) {
            print "</{$this->tag}>\n";
        }
    }

}
?>