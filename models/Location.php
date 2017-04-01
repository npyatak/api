<?php

namespace app\models;

use Yii;

class Location extends \yii\db\ActiveRecord
{
    
    public static function tableName()
    {
        return 'location';
    }
    
    public function rules()
    {
        return [
            [['object_id', 'p0'], 'required'],
            [['object_id', 'created_at'], 'integer'],
            [['p0', 'lat', 'lon', 'date_time'], 'number'],
        ];
    }

    public function behaviors() {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ]
            ],
        ];
    }
}
