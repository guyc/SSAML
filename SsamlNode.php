<?php
class SsamlNode
{
    public $children = array();
    public $state = null;  // set to an instance of SsamlState before calling render
    public $mode = Ssaml::XML_MODE;

    function RenderPrefix()
    {
    }

    function RenderSuffix()
    {
    }

    function SetMode($Mode)
    {
        if ($Mode == Ssaml::PHP_MODE) {
            assert($this->state);
            if ($this->state->mode == Ssaml::XML_MODE) {
                print "<?php ";
            }
        } elseif ($Mode == Ssaml::XML_MODE) {
            if ($this->state->mode == Ssaml::PHP_MODE) {
                print " ?>";
            }
        }
        $this->state->mode = $Mode;
    }

    function Render()
    {
        $this->SetMode($this->mode);
        $this->RenderPrefix();
        foreach ($this->children as $child) {
            $child->state = $this->state;
            $child->Render();
        }
        $this->SetMode($this->mode);
        $this->RenderSuffix();
    }

    /*
     *  $Children is an array of SsamlParseTree.
     *  The default behaviour here is just to Generate SsamlNodes
     *  for each child, but some nodes (like SsamlCommentNode)
     *  will override this method to handle their children differently
     */
    function AddChildren($Children)
    {
        // Generate and add children to the node.
        foreach ($Children as $child) {
            $this->children[] = $child->Generate();
        }
    }
}
?>