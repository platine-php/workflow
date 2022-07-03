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
 * @file Workflow.php
 *
 * The main Workflow class used to manage workflow creation and
 * execution
 *
 *  @package    Platine\Workflow
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Workflow;

use Platine\Logger\LoggerInterface;
use Platine\Workflow\Helper\NodeHelper;

/**
 * @class Workflow
 * @package Platine\Workflow
 */
class Workflow
{
    
    /**
     * The logger instance
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    
    /**
     * The node helper instance
     * @var NodeHelper
     */
    protected NodeHelper $nodeHelper;
    
    /**
     * Create new instance
     * @param LoggerInterface $logger
     * @param NodeHelper $nodeHelper
     */
    public function __construct(
        LoggerInterface $logger, 
        NodeHelper $nodeHelper
    ) {
        $this->logger = $logger;
        $this->nodeHelper = $nodeHelper;
    }

}
