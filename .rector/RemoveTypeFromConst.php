<?php
/*
 * @copyright 2025 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use function is_string;

class RemoveTypeFromConst extends AbstractRector implements MinPhpVersionInterface {
    /**
     * @readonly
     */
    private ReflectionProvider $reflectionProvider;
    /**
     * @readonly
     */
    private StaticTypeMapper $staticTypeMapper;

    public function __construct(ReflectionProvider $reflectionProvider, StaticTypeMapper $staticTypeMapper) {
        $this->reflectionProvider = $reflectionProvider;
        $this->staticTypeMapper   = $staticTypeMapper;
    }

    public function getRuleDefinition(): RuleDefinition {
        return new RuleDefinition('Add type to constants based on their value', [
            new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public const string TYPE = 'some_type';
}
CODE_SAMPLE
                , <<<'CODE_SAMPLE'
final class SomeClass
{
    public const TYPE = 'some_type';
}
CODE_SAMPLE
            )
        ]);
    }

    public function getNodeTypes(): array {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_ {
        $className = $this->getName($node);
        if(!is_string($className)) {
            return null;
        }
        if($node->isAbstract()) {
            return null;
        }
        $classConsts = $node->getConstants();
        if($classConsts === []) {
            return null;
        }

        $hasChanged = false;
        foreach($classConsts as $classConst) {
            // If a type is set, remove
            if($classConst->type !== null) {
                $classConst->type = null;
                $hasChanged       = true;
            }
        }
        if(!$hasChanged) {
            return null;
        }

        return $node;
    }

    public function provideMinPhpVersion(): int {
        return PhpVersion::PHP_80;
    }
}