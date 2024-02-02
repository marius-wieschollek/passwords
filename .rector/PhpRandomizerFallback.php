<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class PhpRandomizerFallback extends AbstractRector {
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array {
        // what node types are we looking for?
        // pick from
        // https://github.com/rectorphp/php-parser-nodes-docs/
        return [Use_::class];
    }

    /**
     * @param Use_ $node
     */
    public function refactor(Node $node): ?Node {
        $isChanged = false;
        foreach($node->uses as $i => $useUse) {
            $name = $useUse->name->getParts();
            if($name == ['Random', 'Randomizer']) {
                $newUseUse        = new UseUse(new Name('OCA\Passwords\Helper\Random\Randomizer', $useUse->getAttributes()));
                $node->uses[ $i ] = $newUseUse;
                $isChanged        = true;
            }
        }

        return $isChanged ? $node:null;
    }

    /**
     * This method helps other to understand the rule
     * and to generate documentation.
     */
    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition(
            'Replace Randomizer with fallback',
            [
                new CodeSample(
                // code before
                    'use Random\Randomizer;',
                    // code after
                    'use OCA\Passwords\Helper\Random\Randomizer;'
                ),
            ]
        );
    }
}