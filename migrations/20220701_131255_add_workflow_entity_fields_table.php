<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowEntityFieldsTable20220701131255 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_entity_fields', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('name')
                 ->description('The human readable name')
                 ->notNull();

            $table->string('field')
                 ->description('The entity or object field name')
                 ->notNull();

            $table->string('description');

            $table->string('type')
                 ->description('The field data type')
                 ->notNull();

            $table->enum('relation', ['Y', 'N'])
                    ->defaultValue('N')
                    ->notNull();

            $table->integer('workflow_entity_id')
                ->notNull();

            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_entity_id')
                ->references('workflow_entities', 'id')
                ->onDelete('NO ACTION');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_entity_fields');
    }
}
