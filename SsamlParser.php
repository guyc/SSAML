<?php
class SsamlParser 
{
    static $NODE_CLASSES = array(
                                 'SsamlExecNode',
                                 'SsamlEvalNode',
                                 'SsamlElementNode',
                                 'SsamlCommentNode',
                                 'SsamlTextNode'  // this must be last because it matches everything
                                 );

    static $FILTER_CLASSES = array(
                                   'include' => 'SsamlIncludeFilter',
                                   'namespace' => 'SsamlNamespaceFilter'
                                   );

    // returns a SsamlDocument instance
    static function ParseFile($FileName, $State=null)
    {
        $foml = file_get_contents($FileName);
        return SsamlParser::ParseString($foml, $State);
    }

    static function ParseString($Ssaml, $State=null)
    {
        $tree = SsamlParseTree::Parse($Ssaml);
        $doc = $tree->Generate();
        if ($State) $doc->state = $State;  // for subdocuments, pass the parent document state along
        return $doc->RenderToString();  // returns php code
    }
}
?>