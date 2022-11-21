<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Создание таблицы расписания в бд.
 */
final class CreateScheduleTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        // create the table
        $table = $this->table('schedule');

        $table->addColumn('date', 'date')
            ->addColumn('time', 'string')
            ->addColumn('subgroup', 'string')
            ->addColumn('lesson', 'string')
            ->addColumn('teacher', 'string')
            ->addColumn('cabinet', 'string')
            ->addColumn('group', 'string')
            ->addColumn('type', 'string')
            ->create();
    }
}
