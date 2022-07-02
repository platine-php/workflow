<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowNodesTable20220701130908 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_nodes', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('name')
                 ->notNull();

            $table->enum('type', ['S', 'I', 'E'])
                   ->description('The node type:  S=Start, I=Intermediate, E=End')
                    ->defaultValue('I')
                    ->notNull();

            $table->enum('task_type', ['U', 'D', 'S'])
                   ->description('The node task type: U=User, D=Decision, S=Service/Script')
                    ->defaultValue('U')
                    ->notNull();

            $table->enum('status', ['A', 'D'])
                   ->description('The status')
                    ->defaultValue('A')
                    ->notNull();

            $table->integer('workflow_id')
                ->notNull();

            $table->integer('workflow_role_id');

            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_id')
                ->references('workflows', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('workflow_role_id')
                ->references('workflow_roles', 'id')
                ->onDelete('NO ACTION');


            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_nodes');
    }
}
