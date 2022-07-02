<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowsTable20220701130522 extends AbstractMigration
{
    public function up(): void
    {
        //Action when migrate up
        $this->create('workflows', function (CreateTable $table) {
            $table->integer('id')
                  ->autoincrement()
                 ->primary();

            $table->string('name')
                 ->description('The workflow name')
                 ->notNull();

            $table->string('description')
                 ->description('The workflow description');


            $table->enum('status', ['A', 'D'])
                   ->description('The workflow status')
                    ->defaultValue('A')
                    ->notNull();

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
        $this->drop('workflows');
    }
}
