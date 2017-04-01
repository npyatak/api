<?php

use yii\db\Migration;

class m170330_062159_create_table_sequence_data extends Migration {
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%sequence_data}}', [
            'id' => $this->primaryKey(),
            'sequence_id' => $this->integer()->notNull(),
            'date_time' => $this->decimal(15, 3)->notNull(),
            'p0' => $this->decimal(10, 8)->notNull(),
            'lat' => $this->decimal(10, 8),
            'lon' => $this->decimal(11, 8),

        ], $tableOptions);  

        $this->addForeignKey("{sequence_data}_sequence_id_fkey", '{{%sequence_data}}', 'sequence_id', '{{%sequence}}', 'id', 'CASCADE', 'CASCADE');

    }

    public function safeDown()
    {
        $this->dropTable('{{%sequence_data}}');
    }
}
