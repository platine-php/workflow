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
 * @file Task.php
 *
 * The Workflow Task Entity class
 *
 *  @package    Platine\Workflow\Model\Entity
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */
declare(strict_types=1);

namespace Platine\Workflow\Model\Entity;

use Platine\Database\Query\WhereStatement;
use Platine\Orm\Entity;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Query\Query;

/**
 * @class Task
 * @package Platine\Workflow\Model\Entity
 */
class Task extends Entity
{
    /**
    * {@inheritdoc}
    */
    public static function mapEntity(EntityMapperInterface $mapper): void
    {
         $mapper->table('workflow_tasks');
         $mapper->name('workflow_task');
         $mapper->primaryKey('workflow_task_id');
         $mapper->relation('node')->belongsTo(Node::class);
         $mapper->relation('instance')->belongsTo(Instance::class);
         $mapper->relation('outcome')->belongsTo(Outcome::class);
         //TODO change it in sub class
         $mapper->relation('user')->belongsTo(Entity::class);
         $mapper->casts([
            'start_date' => 'date',
            'end_date' => '?date',
         ]);

         $mapper->filter('status', function (Query $q, $value) {
            $q->where('status')->is($value);
         });

         $mapper->filter('user', function (Query $q, $value) {
            $q->where('user_id')->is($value);
         });

        $mapper->filter('node', function (Query $q, $value) {
            $q->where('workflow_node_id')->is($value);
        });

        $mapper->filter('instance', function (Query $q, $value) {
            $q->where('workflow_instance_id')->is($value);
        });

        $mapper->filter('outcome', function (Query $q, $value) {
            $q->where('workflow_outcome_id')->is($value);
        });

        $mapper->filter('cancel_trigger', function (Query $q, $value) {
            $q->where('cancel_trigger')->is($value);
        });

        $mapper->filter('start_date', function (Query $q, $value) {
            $q->where('start_date')->gte($value);
        });

        $mapper->filter('end_date', function (Query $q, $value) {
            $q->where(function (WhereStatement $where) use ($value) {
                $where->where('end_date')->is($value)
                ->orWhere('end_date')->isNull();
            });
        });
    }
}
