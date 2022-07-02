<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowTasksTable20220701131058 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_tasks', function (CreateTable $table) {
            //issue with query that return custom columns
            // Using join the field "id" will be ambigus
            $table->integer('workflow_task_id')
                  ->autoincrement()
                 ->primary();

            $table->string('comment');

            $table->enum('cancel_trigger', ['U', 'S'])
                   ->description('The who cancel the task: U = User, S = System')
                    ->defaultValue('U')
                    ->notNull();

            $table->enum('status', ['I', 'C', 'T'])
                   ->description('The status: I = Processing, T = Completed, C = cancel')
                    ->defaultValue('I')
                    ->notNull();

            $table->integer('workflow_instance_id')
                ->notNull();

            $table->integer('workflow_outcome_id');

            $table->integer('workflow_node_id')
                  ->notNull();

            $table->integer('user_id')
                ->notNull()
                 ->description('Current or validated actors');

            $table->datetime('start_date')
                  ->description('the start date')
                  ->notNull();

            $table->datetime('end_date')
                  ->description('the end date');

            $table->foreign('workflow_instance_id')
                ->references('workflow_instances', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('workflow_outcome_id')
                ->references('workflow_outcomes', 'id')
                ->onDelete('NO ACTION');


            $table->foreign('workflow_node_id')
                ->references('workflow_nodes', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('user_id')
                ->references('users', 'id')
                ->onDelete('NO ACTION');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_tasks');
    }
}
