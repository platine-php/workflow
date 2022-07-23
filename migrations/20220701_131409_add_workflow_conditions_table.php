<?php

declare(strict_types=1);

namespace Platine\Framework\Migration;

use Platine\Database\Schema\CreateTable;
use Platine\Framework\Migration\AbstractMigration;

class AddWorkflowConditionsTable20220701131409 extends AbstractMigration
{
    public function up(): void
    {
      //Action when migrate up
        $this->create('workflow_conditions', function (CreateTable $table) {
            //issue with query that return custom columns
            // Using join the field "id" will be ambigus
            $table->integer('workflow_condition_id')
                  ->autoincrement()
                 ->primary();

            $table->string('sort_function')
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

            $table->string('operand1')
                   ->description('the 1st operand to be used for comparaison');

            $table->string('operator')
                   ->description('the operator to be used');

            $table->string('operand2')
                   ->description('the 2nd operand to be used for comparaison');

            $table->integer('sort_order')
                  ->notNull()
                  ->defaultValue(0);

            $table->integer('workflow_condition_group_id')
                   ->description('The condition group')
                    ->notNull();


            $table->datetime('created_at')
                  ->description('the created at')
                  ->notNull();

            $table->datetime('updated_at')
                  ->description('the updated at');

            $table->foreign('workflow_condition_group_id')
                ->references('workflow_condition_groups', 'id')
                ->onDelete('NO ACTION');

            $table->engine('INNODB');
        });
    }

    public function down(): void
    {
      //Action when migrate down
        $this->drop('workflow_conditions');
    }
}
