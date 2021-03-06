<?php
/**
 * A set of PHP-Parser statements
 */
class PHPUnitScribe_Statements
{
    protected $statements = array();
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    public function get_instrumented_statements()
    {
        $traverser = new PHPParser_NodeTraverser();
        $traverser->addVisitor(new PHPParser_NodeVisitor_NameResolver);
        $traverser->addVisitor(new PHPUnitScribe_NodeVisitor_Decomposer);
        $modified_statements = $traverser->traverse($this->statements);

        $traverser = new PHPParser_NodeTraverser();
        $traverser->addVisitor(new PHPUnitScribe_NodeVisitor_Instrumentor);
        $traverser->addVisitor(new PHPUnitScribe_NodeVisitor_ShadowNamespacer);
        $modified_statements = $traverser->traverse($modified_statements);

        return new PHPUnitScribe_Statements($modified_statements);
    }

    public function get_code()
    {
        $printer = new PHPParser_PrettyPrinter_Default();
        return $printer->prettyPrint($this->statements);
    }

    public function execute()
    {
        echo "executing statements\n";
        eval($this->get_code());
    }

    public function add_statement($statement)
    {
        $this->statements[] = $statement;
    }

    public function get_statements()
    {
        return $this->statements;
    }

}
