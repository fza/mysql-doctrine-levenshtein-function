<?php

namespace Fza\MysqlDoctrineLevenshteinFunction\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class LevenshteinFunction extends FunctionNode
{
	public $firstStringExpression = null;
	public $secondStringExpression = null;

	public function getSql(SqlWalker $sqlWalker)
	{
		return 'LEVENSHTEIN('.
			   $this->firstStringExpression->dispatch($sqlWalker).', '.
			   $this->secondStringExpression->dispatch($sqlWalker).
			   ')';
	}

	public function parse(Parser $parser)
	{
		// levenshtein(str1, str2)
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->firstStringExpression = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_COMMA);
		$this->secondStringExpression = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}
