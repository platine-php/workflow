<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowRolesTable20220701130541 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_roles', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('name')
                 ->notNull();

            $table->string('description');

            $table->integer('workflow_id')
                ->notNull();

            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_id')
                ->references('workflows', 'id')
                ->onDelete('NO ACTION');


            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_roles');
    }
}
