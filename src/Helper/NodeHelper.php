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
 * Copyright (c) 2015 JBZoo Content Construction Kit (CCK)
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
use Platine\Workflow\Enum\NodeType;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\NodePath;
use Platine\Workflow\Model\Repository\NodePathRepository;
use Platine\Workflow\Model\Repository\NodeRepository;

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
     * Create new instance
     * @param NodeRepository $nodeRepository
     * @param NodePathRepository $nodePathRepository
     */
    public function __construct(
        NodeRepository $nodeRepository,
        NodePathRepository $nodePathRepository
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->nodePathRepository = $nodePathRepository;
    }

    /**
     * Return the start node for the given workflow
     * @param int $workflow
     * @param array $filters
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
     * @param array $filters
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
        ->where('workflow_node_paths.workflow_id')->is($workflow)
        ->where('workflow_node_paths.source_node_id')->is($sourceNode)
        ->get([
            'workflow_node_paths.*',
            'target_node.name' => 'target_name',
            'target_node.task_type' => 'target_task_type',
            'target_node.type' => 'target_type',
            'target_node.status' => 'target_status',
        ]);
    }

    /**
     * Return the list of node for decision
     * @param int $workflow
     * @param int $decisionNode
     * @return array<NodePath>
     */
    public function getDecisionNodes(int $workflow, int $decisionNode): array
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
        ->where('workflow_node_paths.workflow_id')->is($workflow)
        ->where('workflow_node_paths.source_node_id')->is($decisionNode)
        ->orderBy('workflow_node_paths.sort_order')
        ->all([
            'workflow_node_paths.*',
            'target_node.*',
        ]);
    }

    /**
     * Return the node type
     * @param int $workflowId
     * @param array<string, mixed> $filters
     * @return Node|null
     */
    protected function getNodeType(int $workflowId, array $filters = []): ?Node
    {
        return $this->nodeRepository->filters($filters)
                                     ->findBy([
                                        'workflow_id' => $workflowId
                                     ]);
    }
}
