<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowRoleUsersTable20220701131134 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_roles_users', function (CreateTable $table) {

            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->integer('workflow_instance_id')
                ->notNull();

            $table->integer('workflow_role_id')
                ->notNull();

            $table->integer('user_id')
                ->notNull();

            $table->foreign('workflow_instance_id')
                ->references('workflow_instances', 'id')
                ->onDelete('NO ACTION');

            $table->foreign('workflow_role_id')
                ->references('workflow_roles', 'id')
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
        $this->drop('workflow_roles_users');
    }
}
