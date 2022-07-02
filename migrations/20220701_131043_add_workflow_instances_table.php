<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowInstancesTable20220701131043 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_instances', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('description');

            $table->enum('status', ['I', 'C', 'T'])
                   ->description('The status: I = Processing, T = Completed, C = cancel')
                    ->defaultValue('I')
                    ->notNull();

            $table->string('entity_id')
                   ->description('The entity under workflow')
                   ->notNull();

            $table->string('entity_name')
                   ->description('The entity name under workflow');

            $table->string('entity_detail')
                   ->description('Displayed detail to help validators');

            $table->string('comment')
                  ->description('Comment to help validators');

            $table->integer('workflow_id')
                ->notNull();

            $table->integer('user_id')
                ->notNull()
                 ->description('User who start or update the workflow instance');

            $table->datetime('start_date')
                  ->description('the start date')
                  ->notNull();

            $table->datetime('end_date')
                  ->description('the end date');

            $table->foreign('workflow_id')
                ->references('workflows', 'id')
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
        $this->drop('workflow_instances');
    }
}
