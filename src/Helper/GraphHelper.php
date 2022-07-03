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
 * @file GraphHelper.php
 *
 * The Workflow graph helper class
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

use Platine\Workflow\Graph\Graph;
use Platine\Workflow\Graph\Link;
use Platine\Workflow\Graph\Node as GraphNode;

/**
 * @class GraphHelper
 * @package Platine\Workflow\Helper
 */
class GraphHelper
{
    /**
     * The node helper instance
     * @var NodeHelper
     */
    protected NodeHelper $nodeHelper;

    /**
     * Create new instance
     * @param NodeHelper $nodeHelper
     */
    public function __construct(NodeHelper $nodeHelper)
    {
        $this->nodeHelper = $nodeHelper;
    }

    /**
     * Generate the graph (mermaid) for the given workflow
     * @param int $workflow
     * @return string
     */
    public function generateGraph(int $workflow): string
    {
        $paths = $this->nodeHelper->getNodePaths($workflow);

        if (empty($paths)) {
            return '';
        }

        $graph = new Graph([]);
        $this->addStyles($graph);

        foreach ($paths as $nodePath) {
            //SOURCE NODE
            $sourceNode = $this->buildNode(
                $graph,
                (string) $nodePath->source_node_id,
                $nodePath->source_name,
                $nodePath->source_type,
                $nodePath->source_task_type,
                true
            );

            //TARGET NODE
            $targetNode = $this->buildNode(
                $graph,
                (string) $nodePath->target_node_id,
                $nodePath->target_name,
                $nodePath->target_type,
                $nodePath->target_task_type,
                false
            );

            $this->handlerSourceNodeForUserScript(
                $graph,
                $sourceNode,
                $nodePath->source_type,
                $nodePath->source_task_type
            );

            //ADD TO THE GRAPH
            $graph->addNode($sourceNode);
            $graph->addNode($targetNode);
            $graph->addLink(new Link(
                $sourceNode,
                $targetNode,
                (string) $nodePath->name,
                Link::ARROW
            ));
        }

        return $graph->render();
    }

    /**
     * Add the style to the graph
     * @param Graph $graph
     * @return void
     */
    protected function addStyles(Graph $graph): void
    {
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
    }

    /**
     * Build and return the graph node
     * @param Graph $graph
     * @param string $id
     * @param string $name
     * @param string $type
     * @param string $taskType
     * @param bool $isStart
     * @return GraphNode
     */
    protected function buildNode(
        Graph $graph,
        string $id,
        string $name,
        string $type,
        string $taskType,
        bool $isStart = true
    ): GraphNode {
        $startEndMethodName = $isStart ? 'isStartNode' : 'isEndNode';
        if ($this->nodeHelper->{$startEndMethodName}($type)) {
            return $this->buildStartEndNode(
                $graph,
                $id,
                $name,
                $isStart
            );
        }

        if ($this->nodeHelper->isDecisionNode($taskType)) {
            return $this->buildDecisionNode(
                $graph,
                $id,
                $name
            );
        }

        $icon = 'fa:fa-users';
        if ($this->nodeHelper->isScriptServiceNode($taskType)) {
            $icon = 'fa:fa-code';
        }

        $node = new GraphNode(
            $id,
            sprintf('%s %s', $icon, $name),
            GraphNode::ROUND
        );

        return $node;
    }

    /**
     * Build graph the decision node
     * @param Graph $graph
     * @param string $id
     * @param string $name
     * @return GraphNode
     */
    protected function buildDecisionNode(
        Graph $graph,
        string $id,
        string $name
    ): GraphNode {
        $node = new GraphNode(
            $id,
            $name,
            GraphNode::RHOMBUS
        );

        $graph->addStyle(sprintf(
            'class %s decisionNodeStyle',
            $node->getId()
        ));

        return $node;
    }

    /**
     * Build the graph start or end node
     * @param Graph $graph
     * @param string $id
     * @param string $name
     * @param bool $isStart
     * @return GraphNode
     */
    protected function buildStartEndNode(
        Graph $graph,
        string $id,
        string $name,
        bool $isStart
    ): GraphNode {
        $node = new GraphNode(
            $id,
            $name,
            GraphNode::CIRCLE
        );

        $className = $isStart ? 'startNodeStyle' : 'endNodeStyle';

        $graph->addStyle(sprintf(
            'class %s %s',
            $node->getId(),
            $className
        ));

        return $node;
    }

    /**
     *
     * @param Graph $graph
     * @param GraphNode $node
     * @param string $type
     * @param string $taskType
     * @return void
     */
    protected function handlerSourceNodeForUserScript(
        Graph $graph,
        GraphNode $node,
        string $type,
        string $taskType
    ): void {
        if (
            $this->nodeHelper->isUserNode($taskType)
            && !$this->nodeHelper->isStartNode($type)
            && !$this->nodeHelper->isEndNode($type)
        ) {
            $graph->addStyle(sprintf(
                'class %s userNodeStyle',
                $node->getId()
            ));
        }

        if ($this->nodeHelper->isScriptServiceNode($taskType)) {
            $graph->addStyle(sprintf(
                'class %s scriptServiceNodeStyle',
                $node->getId()
            ));
        }
    }
}
