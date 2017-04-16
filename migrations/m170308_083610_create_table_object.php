<?php

use yii\db\Migration;

class m170308_083610_create_table_object extends Migration {
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%object}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100),
            'p1_sequence_total_mark' => $this->float(),
            'p1_sequence_count' => $this->integer()->defaultValue(0)->notNull(),
            'p3_sequence_total_mark' => $this->float(),
            'p3_sequence_count' => $this->integer()->defaultValue(0)->notNull(),

        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropTable('{{%object}}');
    }
}
