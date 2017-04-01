<?php

namespace app\models;

use Yii;

class Sequence extends \yii\db\ActiveRecord {

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
            [['object_id', 'p_type'], 'integer'],
            [['p_max', 'result'], 'number'],
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
    
    public function getDatas()
    {
        return $this->hasMany(SequenceData::className(), ['sequence_id' => 'id']);
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
