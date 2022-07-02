<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowEntitiesTable20220701131240 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_entities', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('code')
                 ->notNull();

            $table->string('name')
                 ->notNull();

            $table->string('description');

            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_entities');
    }
}
