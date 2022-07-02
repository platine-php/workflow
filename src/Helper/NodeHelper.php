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
use Platine\Workflow\Graph\Graph;
use Platine\Workflow\Graph\Link;
use Platine\Workflow\Graph\Node as GraphNode;
use Platine\Workflow\Model\Entity\Node;
use Platine\Workflow\Model\Entity\NodePath;
use Platine\Workflow\Model\Entity\Task;
use Platine\Workflow\Model\Repository\NodePathRepository;
use Platine\Workflow\Model\Repository\NodeRepository;
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
     * Create new instance
     * @param NodeRepository $nodeRepository
     * @param NodePathRepository $nodePathRepository
     * @param TaskRepository $taskRepository
     */
    public function __construct(
        NodeRepository $nodeRepository,
        NodePathRepository $nodePathRepository,
        TaskRepository $taskRepository
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->nodePathRepository = $nodePathRepository;
        $this->taskRepository = $taskRepository;
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
            'target_node.name' => 'target_name',
            'target_node.task_type' => 'target_task_type',
            'target_node.type' => 'target_type',
            'target_node.status' => 'target_status',
        ]);
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
     * Generate the graph (mermaid) for the given workflow
     * @param int $workflow
     * @return string
     */
    public function generateGraph(int $workflow): string
    {
        $paths = $this->getNodePaths($workflow);

        if (empty($paths)) {
            return '';
        }

        $graph = new Graph([]);
        $styles = [
            'startNodeStyle fill:#1767d1,stroke:#333,stroke-width:2px',
            'endNodeStyle fill:#a019e2,stroke:#333,stroke-width:4px',
            'decisionNodeStyle fill:#ae2,stroke:#333,stroke-width:2px',
            'userNodeStyle fill:#e3a571,stroke:#333,stroke-width:2px',
            'scriptServiceNodeStyle fill:#aef,stroke:#333,stroke-width:2px',
        ];
        foreach ($styles as $style) {
            $graph->addStyle(sprintf('classDef %s', $style));
        }

        foreach ($paths as $nodePath) {
            $sourceNode = null;
            $targetNode = null;
            $linkText = '';

            //SOURCE NODE
            if ($this->isStartNode($nodePath->source_type)) {
                $sourceNode = new GraphNode(
                    $nodePath->source_node_id,
                    $nodePath->source_name,
                    GraphNode::CIRCLE
                );
                $graph->addStyle(sprintf(
                    'class %s startNodeStyle',
                    $sourceNode->getId()
                ));
            } elseif ($this->isDecisionNode($nodePath->source_task_type)) {
                $sourceNode = new GraphNode(
                    $nodePath->source_node_id,
                    $nodePath->source_name,
                    GraphNode::RHOMBUS
                );
                $graph->addStyle(sprintf(
                    'class %s decisionNodeStyle',
                    $sourceNode->getId()
                ));
            } else {
                $icon = 'fa:fa-users';
                if ($this->isScriptServiceNode($nodePath->source_task_type)) {
                    $icon = 'fa:fa-code';
                }
                $sourceNode = new GraphNode(
                    $nodePath->source_node_id,
                    sprintf('%s %s', $icon, $nodePath->source_name),
                    GraphNode::ROUND
                );
            }

            //TARGET NODE
            if ($this->isEndNode($nodePath->target_task_type)) {
                $targetNode = new GraphNode(
                    $nodePath->target_node_id,
                    $nodePath->target_name,
                    GraphNode::CIRCLE
                );
                $graph->addStyle(sprintf(
                    'class %s endNodeStyle',
                    $targetNode->getId()
                ));
            } elseif ($this->isDecisionNode($nodePath->target_task_type)) {
                $targetNode = new GraphNode(
                    $nodePath->target_node_id,
                    $nodePath->target_name,
                    GraphNode::RHOMBUS
                );
                $graph->addStyle(sprintf(
                    'class %s decisionNodeStyle',
                    $sourceNode->getId()
                ));
            } else {
                $icon = 'fa:fa-users';
                if ($this->isScriptServiceNode($nodePath->target_task_type)) {
                    $icon = 'fa:fa-code';
                }
                $targetNode = new GraphNode(
                    $nodePath->target_node_id,
                    sprintf('%s %s', $icon, $nodePath->target_name),
                    GraphNode::ROUND
                );
            }

            $linkText = $nodePath->name;

            if (
                $this->isUserNode($nodePath->source_task_type)
                && !$this->isStartNode($nodePath->source_type)
                && !$this->isEndNode($nodePath->source_type)
            ) {
                $graph->addStyle(sprintf(
                    'class %s userNodeStyle',
                    $sourceNode->getId()
                ));
            }

            if ($this->isScriptServiceNode($nodePath->source_task_type)) {
                $graph->addStyle(sprintf(
                    'class %s scriptServiceNodeStyle',
                    $sourceNode->getId()
                ));
            }

            //ADD TO THE GRAPH
            $graph->addNode($sourceNode);
            $graph->addNode($targetNode);
            $graph->addLink(new Link($sourceNode, $targetNode, $linkText, Link::ARROW));
        }

        return $graph->render();
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

    /**
     * Whether the given type is for start node
     * @param string $type
     * @return bool
     */
    protected function isStartNode(string $type): bool
    {
        return NodeType::START === $type;
    }

    /**
     * Whether the given type is for end node
     * @param string $type
     * @return bool
     */
    protected function isEndNode(string $type): bool
    {
        return NodeType::END === $type;
    }

    /**
     * Whether the given type is for user node
     * @param string $type
     * @return bool
     */
    protected function isUserNode(string $type): bool
    {
        return NodeTaskType::USER === $type;
    }

    /**
     * Whether the given type is for decision node
     * @param string $type
     * @return bool
     */
    protected function isDecisionNode(string $type): bool
    {
        return NodeTaskType::DECISION === $type;
    }

    /**
     * Whether the given type is for script/service node
     * @param string $type
     * @return bool
     */
    protected function isScriptServiceNode(string $type): bool
    {
        return NodeTaskType::SCRIPT_SERVICE === $type;
    }
}
