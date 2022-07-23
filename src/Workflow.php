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
use Platine\Workflow\Helper\WorkflowExecutor;
use Platine\Workflow\Model\Entity\Instance;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\Workflow as WorkflowEntity;

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
     * The workflow executor
     * @var WorkflowExecutor
     */
    protected WorkflowExecutor $executor;

    /**
     * Create new instance
     * @param LoggerInterface $logger
     * @param NodeHelper $nodeHelper
     * @param WorkflowExecutor $executor
     */
    public function __construct(
        LoggerInterface $logger,
        NodeHelper $nodeHelper,
        WorkflowExecutor $executor
    ) {
        $this->logger = $logger;
        $this->nodeHelper = $nodeHelper;
        $this->executor = $executor;
        
        $this->logger->setChannel('Workflow');
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
     * @param Node|null $currentNode
     * @return $this
     */
    public function setCurrentNode(?Node $currentNode): self
    {
        $this->currentNode = $currentNode;
        return $this;
    }


    /**
     * Execute the workflow
     * @return void
     */
    public function execute(): void
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

                return;
            }
        }

        $this->executeWorkflow();
    }

    /**
     * Execute the workflow node
     * @return void
     */
    protected function executeWorkflow(): void
    {
        $workflow = $this->workflowEntity;

        while (true) {
            $node = $this->currentNode;
            if ($node === null) {
                $this->logger->info('Current node is null for the workflow [{workflow}]', [
                    'workflow' => $workflow->name
                ]);
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
                break;
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
    }

    /**
     * Execute the user task workflow
     * @return void
     */
    protected function executeUserNode(): void
    {
        $actors = $this->nodeHelper->getWorkflowRoleActors(
            (int) $this->instance->id,
            (int) $this->currentNode->workflow_role_id
        );
        if (empty($actors)) {
            $this->logger->info('No actors for user node [{node}]', [
                'node' => $this->currentNode->name,
            ]);
            $this->currentNode = null;
            return;
        }
        $this->nodeHelper->executeUserNode(
            $this->instance,
            $this->currentNode,
            $actors
        );
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->currentNode = null;
    }

    /**
     * Execute decision and return the result
     * @return void
     */
    protected function executeDecisionNode(): void
    {
        $node = null;
        $defaultNode = null;
        $decisionNode = $this->currentNode;
        $nodes = $this->nodeHelper->getDecisionNodes(
            $this->workflowEntity->id, 
            $this->currentNode->id
        );
        if(empty($nodes)){
            $this->logger->info('No node path for decision node [{node}], workflow terminated', [
                'node' => $this->currentNode->name,
            ]); 
        } else if(count($nodes) === 1){
            $this->logger->info('Found only one destination for decision node [{node}], just use it', [
                'node' => $this->currentNode->name,
            ]); 
            $node = $nodes[0]->target_node;
        } else {
            $this->logger->info('Check for node path of decision node [{node}], total path [{total}]', [
                'node' => $this->currentNode->name,
                'total' => count($nodes),
            ]);
            foreach ($nodes as $n){
               if($n->target_node->status !== NodeStatus::ACTIVE){
                    $this->logger->info('Node path [{node}] is not active ignore it', [
                        'node' => $n->target_node->name,
                    ]); 
                   continue;
               }
               if($defaultNode === null && $n->is_default){
                   $defaultNode = $n->target_node;
               } 
               $this->logger->info('Check for node path [{node}]', [
                    'node' => $n->target_node->name,
                ]); 
               $result = $this->executeNodeConditions($n->target_node);
                if($result === false){
                   $this->logger->info('Condition for node [{node}] does not match', [
                        'node' => $n->target_node->name,
                    ]); 
                } else {
                    $this->logger->info('Condition for node [{node}] match use it', [
                        'node' => $n->target_node->name,
                    ]); 
                    $node = $n->target_node;
                    break;
                }
            }
        }
        
        if($node === null && $defaultNode){
            $this->logger->info('No nodes conditions match for decision node [{node}] use the default node [{dnode}]', [
                'node' => $this->currentNode->name,
                'dnode' => $defaultNode->name,
            ]);
            $node = $defaultNode;
        }
        
        if($node !== null){
            $this->executeNodeActions($node);
            $this->currentNode = $node;
            //We already execute this node just move to the next
            $this->moveCurrentNodeToNext();
        }
        
        $this->logger->info('End execution of node [{node}]', [
            'node' => $decisionNode->name,
        ]);
    }

    /**
     * Execute script/service and return the result
     * @return void
     */
    protected function executeScriptServiceNode(): void
    {
        $result = $this->executeNodeConditions($this->currentNode);
        if($result === false){
           $this->logger->info('Condition for node [{node}] does not match', [
                'node' => $this->currentNode->name,
            ]); 
        } else {
            $this->executeNodeActions($this->currentNode);
        }
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        if($result === false){
            $this->currentNode = null;
            return;
        }
        $this->moveCurrentNodeToNext();
    }

    /**
     * Execute start node
     * @return void
     */
    protected function executeStartNode(): void
    {
        $result = $this->executeNodeConditions($this->currentNode);
        if($result === false){
           $this->logger->info('Condition for node [{node}] does not match', [
                'node' => $this->currentNode->name,
            ]); 
        } else {
            $this->executeNodeActions($this->currentNode);
        }
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
        $result = $this->executeNodeConditions($this->currentNode);
        if($result === false){
           $this->logger->info('Condition for node [{node}] does not match', [
                'node' => $this->currentNode->name,
            ]); 
        } else {
            $this->executeNodeActions($this->currentNode);
        }
        $this->executeEndNodeActions();
        $this->logger->info('End execution of node [{node}]', [
            'node' => $this->currentNode->name,
        ]);
        $this->currentNode = null;
    }

    /**
     * Execute end node actions
     * @return void
     */
    protected function executeEndNodeActions(): void
    {
    }

    /**
     * Move the pointer to next node
     * @return void
     */
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
        $this->logger->info('Execute conditions for node [{node}] ', [
            'node' => $node->name
        ]);
        $conditions = $this->nodeHelper->getNodeConditionExpressions($node->id);
        if (empty($conditions)) {
            $this->logger->info('No conditions for node [{node}] return true', [
                'node' => $node->name
            ]);
            return true;
        }

        $this->logger->info('Conditions for node [{node}] is [{conditions}]', [
            'node' => $node->name,
            'conditions' => $conditions,
        ]);
        
        $result = $this->executor->execute($conditions);
        $this->logger->info('Condition result for node [{node}] is [{result}]', [
            'node' => $node->name,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Execute node actions
     * @param Node $node
     * @return void
     */
    protected function executeNodeActions(Node $node): void
    {
        $this->logger->info('Execute actions for node [{node}] ', [
            'node' => $node->name
        ]);
    }
}
