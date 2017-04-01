<?php

namespace app\models;

use Yii;

class SequenceData extends \yii\db\ActiveRecord {

    public static function tableName()
    {
        return 'sequence_data';
    }

    public function rules()
    {
        return [
            [['sequence_id', 'p0'], 'required'],
            [['sequence_id'], 'integer'],
            [['p0', 'lat', 'lon', 'date_time'], 'number'],
            [['sequence_id'], 'exist', 'skipOnError' => false, 'targetClass' => Sequence::className(), 'targetAttribute' => ['sequence_id' => 'id']],
        ];
    }

    public function getSequence()
    {
        return $this->hasOne(P3Alg1Sequence::className(), ['id' => 'sequence_id']);
    }
}
