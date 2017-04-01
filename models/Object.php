<?php

namespace app\models;

use Yii;

class Object extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'object';
    }

    public function rules()
    {
        return [
            [['id', 'sequence_count'], 'integer'],
            [['sequence_total_mark'], 'number'],
        ];
    }

    public function getSequenceTotalMark($pMax) {
        if($pMax < 1.6) {
            $this->sequence_total_mark += 5;
        } elseif($pMax < 1.8) {
            $this->sequence_total_mark += 4;
        } elseif($pMax < 2.0) {
            $this->sequence_total_mark += 3;
        } else {
            $this->sequence_total_mark -= $this->sequence_total_mark * 0.5;
        }

        return $this->sequence_total_mark;
    }
}
