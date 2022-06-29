<?php

/**
 * Platine Workflow
 *
 * Platine Workflow is an activity-based workflow system including the
 * definition and execution of workflow specifications
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Workflow
 * Copyright (c) Alexander Kiryukhin
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * @file CustomFunction.php
 *
 * The custom function class
 *
 *  @package    Platine\Workflow\Expression
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */
declare(strict_types=1);

namespace Platine\Workflow\Expression;

use Platine\Workflow\Expression\Exception\IncorrectNumberOfFunctionParametersException;
use ReflectionFunction;

/**
 * @class CustomFunction
 * @package Platine\Workflow\Expression
 */
class CustomFunction
{
    /**
     * The function name
     * @var string
     */
    protected string $name = '';

    /**
     * The function to be called
     * @var callable
     */
    protected $function;

    /**
     * Number of function argument required
     * @var int
     */
    protected int $requiredParamCount = 0;

    /**
     * Create new instance
     * @param string $name
     * @param callable $function
     */
    public function __construct(string $name, callable $function)
    {
        $this->name = $name;
        $this->function = $function;

        $reflection = new ReflectionFunction($function);
        $this->requiredParamCount = $reflection->getNumberOfRequiredParameters();
    }

    /**
     * Execute the function
     * @param array<Token> $stack
     * @param int $stackParamCount
     * @return Token
     */
    public function execute(array &$stack, int $stackParamCount): Token
    {
        if ($stackParamCount < $this->requiredParamCount) {
            throw new IncorrectNumberOfFunctionParametersException(sprintf(
                'Incorrect number of function parameters, [%d] needed, [%d] passed',
                $this->requiredParamCount,
                $stackParamCount
            ));
        }

        $args = [];
        if ($stackParamCount > 0) {
            for ($i = 0; $i < $stackParamCount; $i++) {
                $token = array_pop($stack);
                if ($token !== null) {
                    array_unshift($args, $token->getValue());
                }
            }
        }

        $result = call_user_func_array($this->function, $args);

        return new Token(Token::LITERAL, $result);
    }

    /**
     * Return the name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the function
     * @return callable
     */
    public function getFunction(): callable
    {
        return $this->function;
    }

    /**
     * Return the number of required parameters
     * @return int
     */
    public function getRequiredParamCount(): int
    {
        return $this->requiredParamCount;
    }
}
