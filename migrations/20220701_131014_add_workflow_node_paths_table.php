<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowNodePathsTable20220701131014 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_node_paths', function (CreateTable $table) {
            //issue with query that return custom columns
            // Using join the field "id" will be ambigus
            $table->integer('workflow_node_path_id')
                  ->autoincrement()
                 ->primary();

            $table->string('name');

            $table->enum('is_default', ['Y', 'N'])
                    ->defaultValue('N')
                    ->notNull();

            $table->integer('sort_order')
                  ->notNull()
                  ->defaultValue(0);

            $table->integer('workflow_id')
                ->notNull();

            $table->integer('source_node_id')
                   ->description('Source node')
                    ->notNull();

            $table->integer('target_node_id')
                   ->description('Target node')
                    ->notNull();

            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_id')
                ->references('workflows', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('source_node_id')
                ->references('workflow_nodes', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('target_node_id')
                ->references('workflow_nodes', 'id')
                ->onDelete('NO ACTION');


            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_node_paths');
    }
}
