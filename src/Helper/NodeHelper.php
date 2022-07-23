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
 * @file NodeHelper.php
 *
 * The Workflow node helper class
 *
 *  @package    Platine\Workflow\Helper
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */
declare(strict_types=1);

namespace Platine\Workflow\Helper;

use DateTime;
use Platine\Database\Query\Join;
use Platine\Workflow\Enum\NodeTaskType;
use Platine\Workflow\Enum\NodeType;
use Platine\Workflow\Enum\TaskStatus;
use Platine\Workflow\Model\Entity\Action;
use Platine\Workflow\Model\Entity\Condition;
use Platine\Workflow\Model\Entity\Instance;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\Result;
use Platine\Workflow\Model\Entity\RoleUser;
use Platine\Workflow\Model\Entity\Task;
use Platine\Workflow\Model\Repository\ActionRepository;
use Platine\Workflow\Model\Repository\ConditionRepository;
use Platine\Workflow\Model\Repository\NodePathRepository;
use Platine\Workflow\Model\Repository\NodeRepository;
use Platine\Workflow\Model\Repository\ResultRepository;
use Platine\Workflow\Model\Repository\RoleUserRepository;
use Platine\Workflow\Model\Repository\TaskRepository;

/**
 * @class NodeHelper
 * @package Platine\Workflow\Helper
 */
class NodeHelper
{
    /**
     * The node repository instance
     * @var NodeRepository
     */
    protected NodeRepository $nodeRepository;

    /**
     * The node path repository instance
     * @var NodePathRepository
     */
    protected NodePathRepository $nodePathRepository;

    /**
     * The task repository instance
     * @var TaskRepository
     */
    protected TaskRepository $taskRepository;

    /**
     * The role user repository
     * @var RoleUserRepository
     */
    protected RoleUserRepository $roleUserRepository;

    /**
     * The workflow result instance
     * @var ResultRepository
     */
    protected ResultRepository $resultRepository;

    /**
     * The node condition repository instance
     * @var ConditionRepository
     */
    protected ConditionRepository $conditionRepository;

    /**
     * The node action repository instance
     * @var ActionRepository
     */
    protected ActionRepository $actionRepository;

    /**
     * Create new instance
     * @param NodeRepository $nodeRepository
     * @param NodePathRepository $nodePathRepository
     * @param TaskRepository $taskRepository
     * @param RoleUserRepository $roleUserRepository
     * @param ResultRepository $resultRepository
     * @param ConditionRepository $conditionRepository
     * @param ActionRepository $actionRepository
     */
    public function __construct(
        NodeRepository $nodeRepository,
        NodePathRepository $nodePathRepository,
        TaskRepository $taskRepository,
        RoleUserRepository $roleUserRepository,
        ResultRepository $resultRepository,
        ConditionRepository $conditionRepository,
        ActionRepository $actionRepository
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->nodePathRepository = $nodePathRepository;
        $this->taskRepository = $taskRepository;
        $this->roleUserRepository = $roleUserRepository;
        $this->resultRepository = $resultRepository;
        $this->conditionRepository = $conditionRepository;
        $this->actionRepository = $actionRepository;
    }

    /**
     * Return the start node for the given workflow
     * @param int $workflow
     * @param array<string, mixed> $filters
     * @return Node|null
     */
    public function getStartNode(int $workflow, array $filters = []): ?Node
    {
        return $this->getNodeType(
            $workflow,
            array_merge($filters, ['type' => NodeType::START])
        );
    }

    /**
     * Return the end node for the given workflow
     * @param int $workflow
     * @param array<string, mixed> $filters
     * @return Node|null
     */
    public function getEndNode(int $workflow, array $filters = []): ?Node
    {
        return $this->getNodeType(
            $workflow,
            array_merge($filters, ['type' => NodeType::END])
        );
    }

    /**
     * Return the actors for the given workflow instance
     * and role
     * @param int $instance
     * @param int $role
     * @param array<string, mixed> $filters
     * @return RoleUser[]
     */
    public function getWorkflowRoleActors(int $instance, int $role, array $filters = []): array
    {
        return $this->getActors(
            $instance,
            array_merge($filters, [
                'role' => $role
            ])
        );
    }

    /**
     * Return the node paths for the given workflow
     * @param int $workflow
     * @return array<NodePath>
     */
    public function getNodePaths(int $workflow): array
    {
        return $this->nodePathRepository->query()
            ->with([
                'workflow',
                'source_node.role',
                'target_node.role',
            ])
            ->where('workflow_node_paths.workflow_id')->is($workflow)
            ->all();
    }


    /**
     * Return the target node for the given source node
     * @param int $workflow
     * @param int $sourceNode
     * @return Node|null
     */
    public function getNextNode(int $workflow, int $sourceNode): ?Node
    {
        $nodePath = $this->nodePathRepository->with([
            'workflow',
            'source_node',
        ])
        ->filters([
            'source_node' => $sourceNode,
        ])
        ->findBy(['workflow_id' => $workflow]);

        return $nodePath->target_node ?? null;
    }

    /**
     * Return the node condition groups
     * @param int $node
     * @return Condition[]
     */
    public function getNodeConditions(int $node): array
    {
        $query = $this->conditionRepository->query();
        return $query->join('workflow_condition_groups', function (Join $j) {
            $j->on(
                'workflow_conditions.workflow_condition_group_id',
                'workflow_condition_groups.id'
            );
        })
        ->orderBy([
           'workflow_condition_groups.sort_order',
            'workflow_conditions.sort_order'
        ])
        ->where('workflow_condition_groups.workflow_node_id')->is($node)
        ->all([
            'workflow_conditions.*',
            'workflow_condition_groups.sort_order' => 'cg_sort_order',
            'workflow_condition_groups.id' => 'cg_id',
        ]);
    }

    /**
     * Return the node condition expressions
     * @param int $node
     * @return string|null
     */
    public function getNodeConditionExpressions(int $node): ?string
    {
        $conditions = $this->getNodeConditions($node);

        if (empty($conditions)) {
            return null;
        }
        $groups = [];
        foreach ($conditions as $c) {
            $groups[$c->cg_id][] = sprintf(
                '%s %s %s',
                $c->operand1,
                $c->operator,
                $c->operand2
            );
        }
        
        $groupExpressions = [];
        foreach ($groups as $c) {
            $groupExpressions[] = implode(' || ', $c);
        }
        
        $expression = [];
        foreach ($groupExpressions as $e) {
            $expression[] = sprintf('(%s)', $e);
        }
        
        return implode(' && ', $expression);
    }

    /**
     * Return the node actions
     * @param int $node
     * @return Action[]
     */
    public function getNodeActions(int $node): array
    {
        return $this->actionRepository
        ->orderBy('sort_order')
        ->filters([
            'node' => $node
        ])
        ->all();
    }

    /**
     * Return the list of node for decision
     * @param int $workflow
     * @param int $decisionNode
     * @return array<NodePath>
     */
    public function getDecisionNodes(int $workflow, int $decisionNode): array
    {
        return $this->nodePathRepository->with([
            'workflow',
            'source_node',
            'target_node',
        ])
        ->filters([
            'workflow' => $workflow,
            'source_node' => $decisionNode,
        ])
        ->orderBy('sort_order')
        ->all();
    }

    /**
     * Return the outcome result for the given workflow instance
     * @param int $instance
     * @param int $node
     * @return Task|null
     */
    public function getNodeOutcomeResult(int $instance, int $node): ?Task
    {
        $query = $this->taskRepository->query();
        return $query->join('workflow_instances', function (Join $j) {
            $j->on('workflow_tasks.workflow_instance_id', 'workflow_instances.id');
        })
        ->join('workflow_nodes', function (Join $j) {
            $j->on('workflow_tasks.workflow_node_id', 'workflow_nodes.id');
        })
        ->join('workflow_outcomes', function (Join $j) {
            $j->on('workflow_outcomes.workflow_node_id', 'workflow_nodes.id')
               ->andOn('workflow_tasks.workflow_outcome_id', 'workflow_outcomes.id');
        })
        ->where('workflow_tasks.workflow_node_id')->is($node)
        ->where('workflow_tasks.workflow_instance_id')->is($instance)
        ->where('workflow_tasks.status')->is(TaskStatus::COMPLETED)
        ->orderBy('workflow_tasks.end_date', 'DESC') //it's very important to add ORDER BY here
        ->get([
            'workflow_outcomes.code' => 'code',
        ]);
    }

    /**
     * Return the node last result
     * @param int $instance
     * @param int $node
     * @return Result|null
     */
    public function getNodeLastResult(int $instance, int $node): ?Result
    {
        return $this->resultRepository->filters([
            'instance' => $instance,
        ])
        ->orderBy('date', 'DESC')
        ->findBy([
            'workflow_node_id' => $node
        ]);
    }

    /**
     * Execute user task
     * @param Instance $instance
     * @param Node $node
     * @param array<RoleUser> $actors
     * @return void
     */
    public function executeUserNode(Instance $instance, Node $node, array $actors): void
    {
        foreach ($actors as $actor) {
            //TODO optimize the create of new entity
            $task = $this->taskRepository->create([
                'status' => TaskStatus::PROCESSING,
                'comment' => null,
                'start_date' => new DateTime('now'),
            ]);
            $task->instance = $instance;
            $task->node = $node;
            $task->user = $actor->user;

            $this->taskRepository->save($task);
        }
    }

    /**
     * Whether the given type is for start node
     * @param string $type
     * @return bool
     */
    public function isStartNode(string $type): bool
    {
        return NodeType::START === $type;
    }

    /**
     * Whether the given type is for end node
     * @param string $type
     * @return bool
     */
    public function isEndNode(string $type): bool
    {
        return NodeType::END === $type;
    }

    /**
     * Whether the given type is for user node
     * @param string $type
     * @return bool
     */
    public function isUserNode(string $type): bool
    {
        return NodeTaskType::USER === $type;
    }

    /**
     * Whether the given type is for decision node
     * @param string $type
     * @return bool
     */
    public function isDecisionNode(string $type): bool
    {
        return NodeTaskType::DECISION === $type;
    }

    /**
     * Whether the given type is for script/service node
     * @param string $type
     * @return bool
     */
    public function isScriptServiceNode(string $type): bool
    {
        return NodeTaskType::SCRIPT_SERVICE === $type;
    }

    /**
     * Return the node type
     * @param int $workflow
     * @param array<string, mixed> $filters
     * @return Node|null
     */
    protected function getNodeType(int $workflow, array $filters = []): ?Node
    {
        return $this->nodeRepository->filters($filters)
                                     ->with('workflow')
                                     ->findBy([
                                        'workflow_id' => $workflow
                                     ]);
    }

    /**
     * Return the actors for the given instance
     * @param int $instance
     * @param array<string, mixed> $filters
     * @return RoleUser[]
     */
    protected function getActors(int $instance, array $filters = []): array
    {
        return $this->roleUserRepository->filters($filters)
                                     ->with(['user', 'role'])
                                     ->findAllBy([
                                        'workflow_instance_id' => $instance
                                     ]);
    }
}
