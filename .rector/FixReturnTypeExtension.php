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

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class FixReturnTypeExtension extends AbstractRector {
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
        if(!$this->isName($node->name, 'insert') && !$this->isName($node->name, 'update')) {
            return null;
        }

        if($node->returnType === null || $node->returnType->toString() === 'OCA\Passwords\Db\AbstractRevision') {
            $node->returnType = new \PhpParser\Node\Name\FullyQualified('OCP\AppFramework\Db\Entity');

            return $node;
        }

        return null;
    }

    /**
     * This method helps other to understand the rule and to generate documentation.
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Remove incompatible PHP return type hints',
            [
                new CodeSample(
                    'public function insert(): \OCA\Passwords\Db\AbstractRevision',
                    'public function insert(): Entity'
                ),
            ]
        );
    }
}