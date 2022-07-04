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
use Platine\Orm\Entity;
use Platine\Workflow\Enum\NodeStatus;
use Platine\Workflow\Helper\NodeHelper;
use Platine\Workflow\Model\Entity\Instance;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\Workflow as WorkflowEntity;
use Platine\Workflow\Result\WorkflowResult;

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
     * The workflow entity
     * @var Entity
     */
    protected Entity $entity;

    /**
     * The workflow entity to be used
     * @var WorkflowEntity
     */
    protected WorkflowEntity $workflowEntity;

    /**
     * The workflow instance
     * @var Instance
     */
    protected Instance $instance;

    /**
     * The workflow current node
     * @var Node|null
     */
    protected ?Node $currentNode = null;

    /**
     * Whether to stop workflow execution inside loop
     * @var bool
     */
    protected bool $stop = false;

    /**
     * Whether the end not is reached
     * @var bool
     */
    protected bool $endNodeReached = false;

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

    /**
     * Set the entity to be used
     * @param Entity $entity
     * @return $this
     */
    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Set the workflow entity
     * @param WorkflowEntity $workflowEntity
     * @return $this
     */
    public function setWorkflowEntity(WorkflowEntity $workflowEntity): self
    {
        $this->workflowEntity = $workflowEntity;
        return $this;
    }

    /**
     * Set the workflow instance
     * @param Instance $instance
     * @return $this
     */
    public function setInstance(Instance $instance): self
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * Set the current node
     * @param Node $currentNode
     * @return $this
     */
    public function setCurrentNode(Node $currentNode)
    {
        $this->currentNode = $currentNode;
        return $this;
    }


    /**
     * Execute the workflow
     * @return WorkflowResult
     */
    public function execute(): WorkflowResult
    {
        $workflow = $this->workflowEntity;
        $this->logger->info('Start execution of workflow [{workflow}]', [
            'workflow' => $workflow->name
        ]);
        $node = $this->currentNode;
        if ($node === null) {
            //use start node as current node
            $node = $this->nodeHelper->getStartNode($workflow->id);
            if ($node !== null) {
                $this->currentNode = $node;
            } else {
                $this->logger->info('Current node is null or no start node for the workflow [{workflow}]', [
                    'workflow' => $workflow->name
                ]);

                return new WorkflowResult(true);
            }
        }

        return $this->executeCurrentNode();
    }

    /**
     * Execute the workflow node
     * @return WorkflowResult
     */
    protected function executeCurrentNode(): WorkflowResult
    {
        $workflow = $this->workflowEntity;

        while (!$this->stop && ! $this->endNodeReached) {
            $node = $this->currentNode;
            if ($node === null) {
                $this->logger->info('Current node is null for the workflow [{workflow}]', [
                    'workflow' => $workflow->name
                ]);

                $this->stop = true;
                break;
            }

            $this->logger->info(
                'Start execution of node [{node}], id [{id}], type [{type}], task type [{task_type}], status [{status}]',
                [
                    'node' => $node->name,
                    'id' => $node->id,
                    'type' => $node->type,
                    'task_type' => $node->task_type,
                    'status' => $node->status,
                ]
            );

            if ($node->status !== NodeStatus::ACTIVE) {
                $this->logger->info('Node [{node}] status is not active ignore it', [
                    'node' => $node->name,
                ]);
                $this->moveCurrentNodeToNext();
                continue;
            }

            if ($this->nodeHelper->isStartNode($node->type)) {
                $this->executeStartNode();
                continue;
            }

            if ($this->nodeHelper->isEndNode($node->type)) {
                $this->executeEndNode();
            }

            if ($this->nodeHelper->isUserNode($node->task_type)) {
                $this->executeUserNode();
                break;
            }

            if ($this->nodeHelper->isDecisionNode($node->task_type)) {
                $this->executeDecisionNode();
                continue;
            }

            if ($this->nodeHelper->isScriptServiceNode($node->task_type)) {
                $this->executeScriptServiceNode();
                continue;
            }
        }

        if ($this->endNodeReached) {
            $this->executeEndNodeActions();
        }

        return new WorkflowResult($this->endNodeReached);
    }

    /**
     * Execute the user task workflow
     * @return void
     */
    protected function executeUserNode(): void
    {
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->moveCurrentNodeToNext();
        $this->stop = true;
    }

    /**
     * Execute decision and return the result
     * @return void
     */
    protected function executeDecisionNode(): void
    {
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->moveCurrentNodeToNext();
    }

    /**
     * Execute script/service and return the result
     * @return void
     */
    protected function executeScriptServiceNode(): void
    {
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->moveCurrentNodeToNext();
    }

    /**
     * Execute start node
     * @return void
     */
    protected function executeStartNode(): void
    {
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->moveCurrentNodeToNext();
    }

    /**
     * Execute end node
     * @return void
     */
    protected function executeEndNode(): void
    {
        $this->endNodeReached = true;
        $this->stop = true;
        $this->executeNodeActions($this->currentNode);
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
    }

    /**
     * Execute end node actions
     * @return void
     */
    protected function executeEndNodeActions(): void
    {
    }

    protected function moveCurrentNodeToNext(): void
    {
        if ($this->currentNode === null) {
            $this->logger->info('Current node is null for the workflow [{workflow}]', [
                'workflow' => $this->workflowEntity->name
            ]);
            return;
        }
        $this->currentNode = $this->nodeHelper->getNextNode(
            (int) $this->workflowEntity->id,
            (int) $this->currentNode->id
        );
    }

    /**
     * Execute node conditions and return the result
     * @param Node $node
     * @return mixed
     */
    protected function executeNodeConditions(Node $node)
    {
    }

    /**
     * Execute node actions
     * @param Node $node
     * @return void
     */
    protected function executeNodeActions(Node $node): void
    {
    }
}
