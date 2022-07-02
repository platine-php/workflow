<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowActionsTable20220701131319 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_actions', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('action_function')
                   ->description('the function to be used');

            $table->string('param1')
                   ->description('function parameter 1');

            $table->string('param2')
                   ->description('function parameter 3');

            $table->string('param3')
                   ->description('function parameter 3');

            $table->string('param4')
                   ->description('function parameter 4');

            $table->string('param5')
                   ->description('function parameter 5');

            $table->integer('sort_order')
                  ->notNull()
                  ->defaultValue(0);

            $table->integer('workflow_node_id')
                   ->description('Source node')
                    ->notNull();


            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_node_id')
                ->references('workflow_nodes', 'id')
                ->onDelete('NO ACTION');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_actions');
    }
}
