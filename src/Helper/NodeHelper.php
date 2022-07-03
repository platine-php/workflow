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

use Platine\Database\Query\Join;
use Platine\Workflow\Enum\NodeTaskType;
use Platine\Workflow\Enum\NodeType;
use Platine\Workflow\Enum\TaskStatus;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\NodePath;
use Platine\Workflow\Model\Entity\Result;
use Platine\Workflow\Model\Entity\RoleUser;
use Platine\Workflow\Model\Entity\Task;
use Platine\Workflow\Model\Repository\ActionRepository;
use Platine\Workflow\Model\Repository\ConditionGroupRepository;
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
     * The node condition group
     * @var ConditionGroupRepository
     */
    protected ConditionGroupRepository $conditionGroupRepository;

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
     * @param ConditionGroupRepository $conditionGroupRepository
     */
    public function __construct(
        NodeRepository $nodeRepository,
        NodePathRepository $nodePathRepository,
        TaskRepository $taskRepository,
        RoleUserRepository $roleUserRepository,
        ResultRepository $resultRepository,
        ConditionRepository $conditionRepository,
        ActionRepository $actionRepository,
        ConditionGroupRepository $conditionGroupRepository
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->nodePathRepository = $nodePathRepository;
        $this->taskRepository = $taskRepository;
        $this->roleUserRepository = $roleUserRepository;
        $this->resultRepository = $resultRepository;
        $this->conditionRepository = $conditionRepository;
        $this->actionRepository = $actionRepository;
        $this->conditionGroupRepository = $conditionGroupRepository;
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
        $query = $this->nodePathRepository->query();
        return $query->leftJoin('workflows', function (Join $j) {
            $j->on('workflow_node_paths.workflow_id', 'workflows.id');
        })
        ->leftJoin(['workflow_nodes' => 'source_node'], function (Join $j) {
            $j->on('workflow_node_paths.source_node_id', 'source_node.id');
        })
        ->leftJoin(['workflow_nodes' => 'target_node'], function (Join $j) {
            $j->on('workflow_node_paths.target_node_id', 'target_node.id');
        })
        ->leftJoin(['workflow_roles' => 'source_role'], function (Join $j) {
            $j->on('source_node.workflow_role_id', 'source_role.id');
        })
        ->leftJoin(['workflow_roles' => 'target_role'], function (Join $j) {
            $j->on('target_node.workflow_role_id', 'target_role.id');
        })
        ->where('workflow_node_paths.workflow_id')->is($workflow)
        ->orderBy(['source_node.type', 'target_node.type'])
        ->all([
            'workflow_node_paths.*',
            'source_role.name' => 'source_role_name',
            'target_role.name' => 'target_role_name',
            'source_node.name' => 'source_name',
            'source_node.task_type' => 'source_task_type',
            'source_node.type' => 'source_type',
            'source_node.status' => 'source_status',
            'target_node.name' => 'target_name',
            'target_node.task_type' => 'target_task_type',
            'target_node.type' => 'target_type',
            'target_node.status' => 'target_status',
        ]);
    }


    /**
     * Return the target node for the given source node
     * @param int $workflow
     * @param int $sourceNode
     * @return NodePath|null
     */
    public function getNextNode(int $workflow, int $sourceNode): ?NodePath
    {
        return $this->nodePathRepository->with([
            'workflow',
            'source_node',
            'target_node',
        ])
        ->filters([
            'source_node' => $sourceNode,
        ])
        ->findBy(['workflow_id' => $workflow]);
    }

    /**
     * Return the node condition groups
     * @param int $node
     * @return array<ConditionGroup>
     */
    public function getNodeConditionGroups(int $node): array
    {
        return $this->conditionGroupRepository->with([
            'node',
        ])
        ->filters([
            'node' => $node,
        ])
        ->orderBy('sort_order')
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
