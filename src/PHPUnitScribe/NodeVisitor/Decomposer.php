<?php
/**
 */
class PHPUnitScribe_NodeVisitor_Decomposer extends PHPParser_NodeVisitorAbstract
{

    protected $context_stack = array();

    protected $decomposition_queue = array();

    protected $inner_stmts_decomposed = array();

    protected function pop_context()
    {
        return array_shift($this->context_stack);
    }

    protected function push_context($context)
    {
        array_unshift($this->context_stack, $context);
    }

    protected function get_inner_stmts(PHPParser_Node $node)
    {
        if (PHPUnitScribe_Interceptor::node_contains_inner_stmts(get_class($node)))
        {
            return $node->stmts;
        }
        return null;
    }

    protected function set_inner_stmts(PHPParser_Node &$node, array $stmts)
    {
        if (PHPUnitScribe_Interceptor::node_contains_inner_stmts(get_class($node)))
        {
            $node->stmts = $stmts;
        }
    }

    protected function is_decomposition_enabled()
    {
        foreach ($this->context_stack as $context)
        {
            if (PHPUnitScribe_Interceptor::node_contains_inner_stmts($context))
            {
                return false;
            }
        }
        return true;
    }

    protected function in_decomposable_context()
    {
        if (count($this->context_stack) === 0)
        {
            return false;
        }
        // Don't decompose simple assignments
        if (count($this->context_stack) == 1 &&
            $this->context_stack[0] === 'PHPParser_Node_Expr_Assign')
        {
            return false;
        }

        return true;
    }

    public function enterNode(PHPParser_Node $node)
    {
        $this->push_context(get_class($node));
        $inner_stmts = $this->get_inner_stmts($node);
        if (is_array($inner_stmts))
        {
            // Start a sub traversal of the node's inner stmts
            // Necessary for distinguishing the context of the inner stmts
            $inner_traverser = new PHPParser_NodeTraverser();
            $inner_traverser->addVisitor(new PHPUnitScribe_NodeVisitor_Decomposer);
            // Save the inner stmts and zero them out so we don't do extra work
            $this->inner_stmts_decomposed = $inner_traverser->traverse($inner_stmts);
            $this->set_inner_stmts($node, array());
        }
    }

    public function leaveNode(PHPParser_Node $node)
    {
        $this->pop_context();
        // If this node has inner stmts and a sub traversal has processed
        // them, insert the processed stmts
        if (count($this->inner_stmts_decomposed) > 0 &&
            PHPUnitScribe_Interceptor::node_contains_inner_stmts(get_class($node)))
        {
            $this->set_inner_stmts($node, $this->inner_stmts_decomposed);
            $this->inner_stmts_decomposed = array();
        }
        // If this node is mockable and we are in a nested context that requires
        // decomposition, perform the substitution
        if (PHPUnitScribe_Interceptor::is_interceptable_reference($node) &&
            $this->in_decomposable_context())
        {
            $var_name = PHPUnitScribe_Interceptor::get_new_var_name();
            $var = new PHPParser_Node_Expr_Variable($var_name);
            $assigner = new PHPParser_Node_Expr_Assign($var, $node);
            $this->decomposition_queue[] = $assigner;
            return $var;
        }
        // Otherwise, if we are in a context where new statements can be added and
        // we have temp vars to assign, add the assignments to the result
        else if (count($this->context_stack) === 0 && count($this->decomposition_queue) > 0)
        {
            $stmts_to_return = $this->decomposition_queue;
            $stmts_to_return[] = $node;
            $this->decomposition_queue = array();
            return $stmts_to_return;
        }
        return $node;
    }
}
