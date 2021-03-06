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
 * @file Instance.php
 *
 * The Workflow Instance Entity class
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
 * @class Instance
 * @package Platine\Workflow\Model\Entity
 */
class Instance extends Entity
{
    /**
    * {@inheritdoc}
    */
    public static function mapEntity(EntityMapperInterface $mapper): void
    {
         $mapper->table('workflow_instances');
         $mapper->name('workflow_instance');
         $mapper->relation('workflow')->belongsTo(Workflow::class);
         //TODO change it in sub class
         $mapper->relation('user')->belongsTo(Entity::class);
         $mapper->casts([
            'start_date' => 'date',
            'end_date' => '?date',
         ]);

         $mapper->filter('status', function (Query $q, $value) {
            $q->where('status')->is($value);
         });

        $mapper->filter('entity_id', function (Query $q, $value) {
            $q->where('entity_id')->is($value);
        });

        $mapper->filter('entity_name', function (Query $q, $value) {
            $q->where('entity_name')->is($value);
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

        $mapper->filter('user', function (Query $q, $value) {
            $q->where('user_id')->is($value);
        });

        $mapper->filter('workflow', function (Query $q, $value) {
            $q->where('workflow_id')->is($value);
        });
    }
}
