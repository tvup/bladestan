<?php

declare(strict_types=1);

namespace TomasVotruba\Bladestan\TemplateCompiler\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Registry;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use TomasVotruba\Bladestan\TemplateCompiler\Reflection\PrivatesAccessor;

final class TemplateRulesRegistry implements Registry
{
    /**
     * @var array<string, array<Rule<\PhpParser\Node>>>
     */
    private array $rulesRegistry = [];

    /**
     * @var string[]
     */
    private const EXCLUDED_RULES = [
        'Symplify\PHPStanRules\Rules\ForbiddenFuncCallRule',
        'Symplify\PHPStanRules\Rules\NoDynamicNameRule',
    ];

    /**
     * @param array<Rule<Node>> $rules
     */
    public function __construct(array $rules)
    {
        $this->rulesRegistry = $this->filterAndRegisterRules($rules);
    }

    /**
     * @template TNode as \PhpParser\Node
     * @param class-string<TNode> $nodeType
     * @return array<Rule<TNode>>
     */
    public function getRules(string $nodeType): array
    {
        $activeRules = $this->rulesRegistry[$nodeType] ?? [];

        // only fix in a weird test case setup
        if (defined('PHPUNIT_COMPOSER_INSTALL') && $nodeType === MethodCall::class) {
            $privatesAccessor = new PrivatesAccessor();

            foreach ($activeRules as $activeRule) {
                if (! $activeRule instanceof CallMethodsRule) {
                    continue;
                }

                /** @var FunctionCallParametersCheck $check */
                $check = $privatesAccessor->getPrivateProperty($activeRule, 'parametersCheck');

                $privatesAccessor->setPrivateProperty($check, 'checkArgumentTypes', true);
            }
        }

        return $activeRules;
    }

    /**
     * @param array<Rule<Node>> $rules
     * @return array<string, array<Rule<Node>>>
     */
    private function filterAndRegisterRules(array $rules): array
    {
        $rulesRegistry = [];

        foreach ($rules as $rule) {
            foreach (self::EXCLUDED_RULES as $excludedRule) {
                if ($rule instanceof $excludedRule) {
                    continue 2;
                }
            }

            $nodeType = $this->getNodeType($rule);
            if (!isset($rulesRegistry[$nodeType])) {
                $rulesRegistry[$nodeType] = [];
            }

            $rulesRegistry[$nodeType][] = $rule;
        }

        return $rulesRegistry;
    }

    /**
     * @param Rule<Node> $rule
     * @return string
     */
    private function getNodeType(Rule $rule): string
    {
        return $rule->getNodeType();
    }
}
