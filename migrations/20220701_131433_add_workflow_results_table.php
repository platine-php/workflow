<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowResultsTable20220701131433 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_results', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('result')
                 ->description('The node result, for object must be serialized form');

            $table->string('datatype')
                 ->description('The result data type')
                 ->notNull();

             $table->string('datatype')
                 ->description('The node data type')
                 ->notNull();

             $table->enum('type', ['U', 'D', 'S'])
                   ->description('The node task type: U=User, D=Decision, S=Service/Script')
                    ->defaultValue('U')
                    ->notNull();

            $table->integer('workflow_node_id')
                   ->description('Source node')
                    ->notNull();


            $table->integer('workflow_instance_id')
                ->notNull();

            $table->datetime('date')
                  ->description('the date')
                  ->notNull();

            $table->foreign('workflow_node_id')
                ->references('workflow_nodes', 'id')
                ->onDelete('NO ACTION');


            $table->foreign('workflow_instance_id')
                ->references('workflow_instances', 'id')
                ->onDelete('NO ACTION');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_results');
    }
}
