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
use Platine\Workflow\Result\WorkflowDecisionResult;
use Platine\Workflow\Result\WorkflowResult;
use Platine\Workflow\Result\WorkflowScriptServiceResult;
use Platine\Workflow\Result\WorkflowUserResult;

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
     * The entity id value
     * @var mixed
     */
    protected $entityId;

    /**
     * The entity name value
     * @var string
     */
    protected string $entityName = '';

    /**
     * The workflow current node
     * @var Node|null
     */
    protected ?Node $currentNode = null;

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
     * Set the entity id
     * @param mixed $entityId
     * @return $this
     */
    public function setEntityId($entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * Set the entity name
     * @param string $entityName
     * @return $this
     */
    public function setEntityName(string $entityName): self
    {
        $this->entityName = $entityName;
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
        $endNodeReached = false; //if already at the end node
        $stop = false; //if need sort out the loop

        while (! $stop && ! $endNodeReached) {
            $node = $this->currentNode;
            $this->logger->info('Start execution of node [{node}], type [{type}], [{task_type}], status [{status}]', [
                'node' => $node->name,
                'type' => $node->type,
                'task_type' => $node->task_type,
                'status' => $node->status,
            ]);

            $nextNode = $this->nodeHelper->getNextNode($workflow->id, (int) $node->id);
            if ($nextNode === null) {
                $this->logger->info('Can not find the next node for the previous node [{node}]', [
                    'node' => $node->name
                ]);
                $endNodeReached = true;
                break;
            }

            if ($node->status !== NodeStatus::ACTIVE) {
                $this->logger->info('Node [{node}] status is not active ignore it', [
                    'node' => $node->name,
                ]);
                $this->currentNode = $nextNode;
                continue;
            }

            if ($this->nodeHelper->isUserNode($node->task_type)) {
                $result = $this->executeUserNode();
                if ($result->isEndNodeReached()) {
                    $this->logger->info('User Node [{node}] does not have actors', [
                        'node' => $node->name,
                    ]);
                    break;
                }
                $stop = true;
                break;
            }

            if ($this->nodeHelper->isDecisionNode($node->task_type)) {
                $result = $this->executeDecisionNode();
                if ($result->getNextNode() === null) {
                    $this->logger->info('No nodes match the conditions for decision node [{node}]', [
                        'node' => $node->name,
                    ]);
                    $stop = true;
                    $endNodeReached = true;
                } else {
                    $this->currentNode = $result->getNextNode();
                }
            }

            if ($this->nodeHelper->isScriptServiceNode($node->task_type)) {
                $result = $this->executeScriptServiceNode();
                if (!$result->isSuccess()) {
                    $this->logger->info('Script/Service Node [{node}] does not', [
                        'node' => $node->name,
                    ]);
                    break;
                }
            }
            $this->logger->info('End execution of node [{node}]', [
                'node' => $node->name,
            ]);
        }

        if ($endNodeReached) {
            $this->executeEndNode();
        }

        return new WorkflowResult($endNodeReached);
    }

    /**
     * Execute the user task workflow
     * @return WorkflowUserResult
     */
    protected function executeUserNode(): WorkflowUserResult
    {
        return new WorkflowUserResult(false);
    }

    /**
     * Execute decision and return the result
     * @return WorkflowDecisionResult
     */
    protected function executeDecisionNode(): WorkflowDecisionResult
    {
        return new WorkflowDecisionResult(false, null);
    }

    /**
     * Execute script/service and return the result
     * @return WorkflowScriptServiceResult
     */
    protected function executeScriptServiceNode(): WorkflowScriptServiceResult
    {
        return new WorkflowScriptServiceResult(false, true);
    }

    /**
     * Execute end node
     * @return WorkflowResult
     */
    protected function executeEndNode(): WorkflowResult
    {
        $node = $this->nodeHelper->getEndNode($this->workflowEntity->id);
        if ($node !== null) {
            return new WorkflowResult(true);
        }
        
        return new WorkflowResult(true);
    }
}
