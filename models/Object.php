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
            [['id', 'p1_sequence_count', 'p3_sequence_count'], 'integer'],
            [['p1_sequence_total_mark', 'p3_sequence_total_mark'], 'number'],
            [['name'], 'string', 'max' => 100],
        ];
    }

    /*public function getNewSequenceTotalMark($pMax, $pType) {
        $attr = 'p'.$pType.'_sequence_total_mark';
        if($pMax < 1.6) {
            $this->$attr += 5;
        } elseif($pMax < 1.8) {
            $this->$attr += 4;
        } elseif($pMax < 2.0) {
            $this->$attr += 3;
        } else {
            $this->$attr -= $this->$attr * 0.5;
        }

        return $this->$attr;
    }

    public function getNewSequenceCount($pType) {
        $attr = 'p'.$pType.'_sequence_count';
        return $this->$attr + 1;
    }*/

    public function updateSequenceParams($pMax, $pType) {
        $attrCount = 'p'.$pType.'_sequence_count';
        $attr = 'p'.$pType.'_sequence_total_mark';

        if($pMax < 1.6) {
            $this->$attr += 5;
        } elseif($pMax < 1.8) {
            $this->$attr += 4;
        } elseif($pMax < 2.0) {
            $this->$attr += 3;
        } else {
            $this->$attr -= $this->$attr * 0.5;
        }

        $this->$attrCount = $this->$attrCount + 1;

        return $this->save();
    }

    public function getResult() {
        return ($this->p1_sequence_total_mark + $this->p3_sequence_total_mark) / ($this->p1_sequence_count + $this->p3_sequence_count);
    }
}
