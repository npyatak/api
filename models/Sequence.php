<?php

namespace app\models;

use Yii;

class Sequence extends \yii\db\ActiveRecord {

    const DEFAULT_RESULT = 5;
    const DEFAULT_PMAX = 1;

    const PTYPES_ARRAY = [1, 3];

    public $categories = [1, 2, 3, 4];

    public static function tableName()
    {
        return 'sequence';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['object_id', 'p_max', 'p_type'], 'required'],
            [['object_id', 'p_type', 'category'], 'integer'],
            [['p_max', 'result', 'p_result'], 'number'],
            [['comment'], 'string', 'max' => 1000],
            [['created_at'], 'safe'],
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

    public function beforeSave($insert) {
        if($this->p_max < 1.6) {
            $this->category = 1;
        } elseif($this->p_max < 1.8) {
            $this->category = 2;
        } elseif($this->p_max < 2.0) {
            $this->category = 3;
        } else {
            $this->category = 4;
        }

        return parent::beforeSave($insert);
    }
    
    public function getDatas()
    {
        return $this->hasMany(SequenceData::className(), ['sequence_id' => 'id']);
    }

    public function generateDefault($object_id, $comment='') {
        foreach (self::PTYPES_ARRAY as $pType) {
            $sequence = new self;
            $sequence->object_id = $object_id;
            $sequence->comment = $comment;
            $sequence->result = self::DEFAULT_RESULT;
            $sequence->p_type = $pType;
            $sequence->p_max = self::DEFAULT_PMAX;
            $sequence->save();

            $object = Object::findOne($object_id);
            $object->updateSequenceParams($sequence->p_max, $pType);
        }
    }

    public function getNewPResult($object, $pType) {
        $attrCount = 'p'.$pType.'_sequence_count';
        $attrTotalMark = 'p'.$pType.'_sequence_count';

        return $object->$attrTotalMark / $object->$attrCount;
    }

    /*public function create($obj, $data) {
        $x = 0;
        $y = 2;
        $lastId = null;

        $sequence = [];
        foreach ($data as $d) {
            if($d->p2 >= $x && $d->p2 < $y && (empty($sequence) || (!empty($sequence) && end($sequence)->date_time - $d->date_time <= 3))) {
                $sequence[] = $d;
            } else {
                $this->saveSequence($obj, $sequence);
                $lastId = $d->id;
                $sequence = [];
            }
        }

        return $lastId;
    }

    private function saveSequence($obj, $sequence) {
        if(count($sequence) >= 5) {
            if(end($sequence)->date_time - $sequence[0]->date_time < 0.5) {
                return;
            }

            $model = new self;

            $p2Array = [];
            foreach ($sequence as $seq) {
                $p2Array[] = $seq->p2;
            }

            $model->p_max = max($p2Array);
            $model->object_id = $obj->object_id;

            if($model->save()) {
                foreach ($sequence as $seq) {
                    $data = new P2Alg1Data;
                    $data->p2_alg1_sequence_id = $model->id;
                    $data->date_time = $seq->date_time;
                    $data->p0 = $seq->p0;
                    $data->lat = $seq->lat;
                    $data->lon = $seq->lon;
                    $data->save();
                }

                $obj->updateSequenceParams($this->tableName(), $model->p_max);
                Alg1Result::create($obj, $this->tableName(), $model->p_max);
            }
        }
    }*/
}
