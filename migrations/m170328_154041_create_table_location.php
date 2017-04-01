<?php

use yii\db\Migration;

class m170328_154041_create_table_location extends Migration {

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%location}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer()->notNull(),
            'p0' => $this->decimal(10, 8)->notNull(),
            'date_time' => $this->decimal(15, 3)->notNull(),
            'lat' => $this->decimal(10, 8),
            'lon' => $this->decimal(11, 8),
            'created_at' => $this->integer()->notNull(),

        ], $tableOptions);
        
        $this->addForeignKey("{location}_object_id_fkey", '{{%location}}', 'object_id', '{{%object}}', 'id', 'CASCADE', 'CASCADE');

    }

    public function safeDown()
    {
        $this->dropTable('{{%location}}');
    }
}
