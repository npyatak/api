<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\ServerErrorHttpException;

use app\components\Helper;

use app\models\Sequence;
use app\models\SequenceData;
use app\models\Object;
use app\models\Location;

class DataController extends ActiveController {

    public $modelClass = 'app\models\Object';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'location'  => ['post'],
                    'sequence'  => ['post'],
                    'p2alg1'  => ['get', 'head'],
                    'p3alg1'  => ['get', 'head'],
                    'alg1result'  => ['get', 'head'],
                    'aggregate'  => ['get', 'head'],
                    'erase'  => ['get', 'head'],
                ],
        ];
      
        return $behaviors;
    }

    public function actionLocation() {
        $inputDatas = json_decode(Yii::$app->request->getRawBody(), true);

        foreach ($inputDatas as $inputData) {
            $object = Object::findOne($inputData['objID']);
            if($object === null) {
                $object = new Object;
                $object->id = $inputData['objID'];
                $object->save();
            }

            $location = new Location;
            $location->object_id = $object->id;
            $location->p0 = $inputData['p0'];
            $location->lat = $inputData['lat'];
            $location->lon = $inputData['lon'];
            $location->date_time = Helper::timestampWithMS($inputData['dateTime']);

            if(!$location->save()) {
                throw new ServerErrorHttpException(implode(', ', $location->getFirstErrors()));
            }
        }

        Yii::$app->getResponse()->setStatusCode(204);
        return 'success';
    }

    public function actionSequence() {
        $inputDatas = json_decode(Yii::$app->request->getRawBody(), true);

        foreach ($inputDatas as $inputData) {
            $object = Object::findOne($inputData['objID']);
            if($object === null) {
                $object = new Object;
                $object->id = $inputData['objID'];
                $object->save();
            }

            $sequence = new Sequence;
            $sequence->object_id = $object->id;
            $sequence->p_type = $inputData['Ptype'];
            $sequence->p_max = $inputData['Pmax'];

            if($sequence->validate()) {
                $object->sequence_count = $object->sequence_count + 1;
                $object->sequence_total_mark = $object->getSequenceTotalMark($inputData['Pmax']);
                $object->save();

                $sequence->result = $object->sequence_total_mark / $object->sequence_count;
                $sequence->save();

                foreach ($inputData['data'] as $data) {
                    $sequenceData = new SequenceData;
                    $sequenceData->sequence_id = $sequence->id;
                    $sequenceData->date_time = Helper::timestampWithMS($data['dateTime']);
                    $sequenceData->p0 = $data['p0'];
                    $sequenceData->lat = $data['lat'];
                    $sequenceData->lon = $data['lon'];
                    $sequenceData->save();
                }
            } else {
                throw new ServerErrorHttpException('Wrong input data');
            }
        }

        Yii::$app->getResponse()->setStatusCode(204);
        return 'success';
    }

	public function actionP2alg1() {
		$sequences = Sequence::find()->where(['p_type'=>2])->joinWith('datas')->asArray()->all();
        $result = [];
        foreach ($sequences as $sequence) {
            $data = [];
            foreach ($sequence['datas'] as $d) {
                $data[] = ['dateTime'=>Helper::dateFormatWithMS($d['date_time']), 'p0'=>$d['p0'], 'lat'=>$d['lat'], 'lon'=>$d['lon']]; 
            }
            $result[] = ['objID'=>$sequence['object_id'], 'Ptype'=>2, 'Pmax'=>$sequence['p_max'], 'result'=>$sequence['result'], 'data'=>$data];
        }

		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $result;
	}

    public function actionP3alg1() {
        $sequences = Sequence::find()->where(['p_type'=>3])->joinWith('datas')->asArray()->all();
        $result = [];
        foreach ($sequences as $sequence) {
            $data = [];
            foreach ($sequence['datas'] as $d) {
                $data[] = ['dateTime'=>Helper::dateFormatWithMS($d['date_time']), 'p0'=>$d['p0'], 'lat'=>$d['lat'], 'lon'=>$d['lon']]; 
            }
            $result[] = ['objID'=>$sequence['object_id'], 'Ptype'=>3, 'Pmax'=>$sequence['p_max'], 'result'=>$sequence['result'], 'data'=>$data];
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public function actionAlg1result($object_id) {
        $sequence = Sequence::find()->where(['object_id'=>$object_id])->orderBy('created_at DESC')->one();

        $date = new \DateTime();
        $dateTime = $date->setTimestamp($sequence->created_at)->format("Y-m-d H:i:s");
        $result = [
            'objID'=>$sequence->object_id, 
            'result'=>$sequence->result, 
            'Ptype'=>$sequence->p_type, 
            'Pmax'=>$sequence->p_max, 
            'dateTime'=>$dateTime
        ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

     public function actionAggregate($object_id, $dateFrom, $dateTo) {
        $object = Object::findOne($object_id);
        $lastSequence = Sequence::find()->where(['object_id'=>$object_id])->orderBy('created_at DESC')->one();

        if($object === null || $lastSequence === null) {
            throw new ServerErrorHttpException('No object found with ID: '.$object_id);
        }

        $result = [
            'objID'=>$lastSequence->object_id, 
            'dateFrom'=>$dateFrom,
            'dateTo'=>$dateTo,
            'sequence_total_mark'=>$object->sequence_total_mark,
            'sequence_count'=>$object->sequence_count,
            'result'=>$lastSequence->result,
            'Ptype'=>$lastSequence->p_type,
            'Pmax'=>$lastSequence->p_max
        ];

        $sequences = Sequence::find()
            ->select(['sequence.id as sequence_id', 'object_id', 'p0', 'lat', 'lon', 'date_time', 'result', 'p_max', 'p_type', 'sequence_id'])
            ->join('RIGHT JOIN', 'sequence_data as data', 'data.sequence_id=sequence.id')
            ->where(['object_id'=>$object_id])
            ->andWhere(['between', 'date_time', Helper::timestampWithMS($dateFrom), Helper::timestampWithMS($dateTo)])
            ->asArray()
            ->all();

        $seqArr = [];
        foreach ($sequences as $sequence) {
            if(!isset($seqArr[$sequence['sequence_id']])) {
                $seqArr[$sequence['sequence_id']] = [
                    'result'=>$sequence['result'],
                    'Pmax'=>$sequence['p_max'],
                    'Ptype'=>$sequence['p_type'],
                    'data'=>[]
                ];
            }

            $seqArr[$sequence['sequence_id']]['data'][] = [
                'dateTime'=>Helper::dateFormatWithMS($sequence['date_time']), 
                'p0'=>$sequence['p0'], 
                'lat'=>$sequence['lat'], 
                'lon'=>$sequence['lon']
            ];
        }

        foreach ($seqArr as $key => $arr) {
            $result['sequence'][] = $arr;
        }

        $locations = Location::find()->where(['object_id'=>$object_id])->andWhere(['between', 'date_time', Helper::timestampWithMS($dateFrom), Helper::timestampWithMS($dateTo)])->asArray()->all();
        foreach ($locations as $location) {
            $result['location'][] = [
                'dateTime'=>Helper::dateFormatWithMS($location['date_time']), 
                'p0'=>$location['p0'],
                'lat'=>$location['lat'],
                'lon'=>$location['lon']
            ];
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public function actionErase($object_id) {
        $object = Object::findOne($object_id);

        if($object === null) {
            throw new ServerErrorHttpException('No object found with ID: '.$object_id);
        }

        Location::deleteAll(['object_id'=>$object_id]);
        Sequence::deleteAll(['object_id'=>$object_id]);

        $object->sequence_total_mark = 5;
        $object->sequence_count = 1;
        $object->save();

        $sequence = new Sequence;
        $sequence->result = 5;
        $sequence->object_id = $object_id;
        $sequence->p_type = 2;
        $sequence->p_max = 1;
        $sequence->save();

        Yii::$app->getResponse()->setStatusCode(204);
        return 'success';
    }
}