<?php

use yii\db\Migration;

class m170330_061732_create_table_sequence extends Migration {
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%sequence}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer()->notNull(),
            'p_max' => $this->decimal(10, 8)->notNull(),
            'p_type' => $this->integer(1)->notNull(),
            'result' => $this->float(),

            'created_at' => $this->integer()->notNull(),

        ], $tableOptions);

        $this->addForeignKey("{sequence}_object_id_fkey", '{{%sequence}}', 'object_id', '{{%object}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%sequence}}');
    }
}