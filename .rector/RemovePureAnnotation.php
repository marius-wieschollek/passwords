<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace Utils\Rector\Rector;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RemovePureAnnotation extends AbstractRector {
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node {
        if(!$this->isName($node->name, '__construct')) {
            return null;
        }

        $node->attrGroups = [];

        return $node;
    }

    /**
     * This method helps other to understand the rule and to generate documentation.
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Remove attributes from method calls',
            [
                new CodeSample(
                    '#[Pure] public function __construct',
                    'public function __construct'
                ),
            ]
        );
    }
}