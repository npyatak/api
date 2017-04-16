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
                    'add' => ['post'],
                    'location'  => ['post'],
                    'sequence'  => ['post'],
                    'p2alg1'  => ['get', 'head'],
                    'p3alg1'  => ['get', 'head'],
                    'alg1result'  => ['get', 'head'],
                    'aggregate'  => ['get', 'head'],
                    'erase'  => ['get', 'head'],
                    'reset'  => ['get', 'head'],
                    'lastdata'  => ['get', 'head'],
                ],
        ];
      
        return $behaviors;
    }

    public function actionAdd() {
        $inputData = json_decode(Yii::$app->request->getRawBody(), true);

        $object = new Object;
        $object->name = $inputData['name'];
        
        if($object->save()) {
            Sequence::generateDefault($object->id);
            
            Yii::$app->getResponse()->setStatusCode(204);
            return 'success';
        }
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
            $sequence->p_type = (int)$inputData['Ptype'];
            $sequence->p_max = $inputData['Pmax'];

            if($sequence->validate()) {
                $object->updateSequenceParams($inputData['Pmax'], $sequence->p_type);

                $sequence->p_result = $sequence->getNewPResult($object, $sequence->p_type);
                $sequence->result = $object->result;
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

     public function actionAggregate($object_id, $Ptype=null, $dateFrom=null, $dateTo=null) {
        $object = Object::findOne($object_id);

        $query = Sequence::find()->where(['object_id'=>$object_id])
            ->orderBy('created_at DESC');
        if($Ptype) $query->andWhere(['p_type'=>$Ptype]);
        $lastSequence = $query->one();

        if($object === null || $lastSequence === null) {
            throw new ServerErrorHttpException('No object found with ID: '.$object_id);
        }

        if(!$dateFrom) {
            $dateFrom = Helper::dateFormatWithMS($lastSequence->created_at);
        }
        if(!$dateTo) {
            $dateTo = date("Y-m-d H:i:s");
        }
        


        $result = [
            'objID'=>$lastSequence->object_id, 
            'dateFrom'=>$dateFrom,
            'dateTo'=>$dateTo,
            //'sequence_total_mark'=>$object->sequence_total_mark,
            //'sequence_count'=>$object->sequence_count,
            'result'=>$lastSequence->result,
            'p_result'=>$lastSequence->result,
            'Ptype'=>$lastSequence->p_type,
            'Pmax'=>$lastSequence->p_max
        ];
    
        $lastLocation = Location::find()->where(['object_id'=>$object->id])->asArray()->orderBy('created_at DESC')->one();
        if($lastLocation) {
            $data['lastLocation'] = [
                'dateTime'=>Helper::dateFormatWithMS($lastLocation['date_time']), 
                'p0'=>$lastLocation['p0'],
                'lat'=>$lastLocation['lat'],
                'lon'=>$lastLocation['lon']
            ];
        } 

        $query = Sequence::find()
            ->select(['sequence.id as sequence_id', 'object_id', 'category', 'p0', 'lat', 'lon', 'date_time', 'result', 'p_max', 'p_type', 'sequence_id'])
            ->join('RIGHT JOIN', 'sequence_data as data', 'data.sequence_id=sequence.id')
            ->where(['object_id'=>$object_id])
            ->andWhere(['between', 'date_time', Helper::timestampWithMS($dateFrom), Helper::timestampWithMS($dateTo)])
            ->asArray();
        if($Ptype) $query->andWhere(['p_type'=>$Ptype]);

        $sequences = $query->all();

        $seqArr = [];
        foreach ($sequences as $sequence) {
            if(!isset($seqArr[$sequence['sequence_id']])) {
                $seqArr[$sequence['sequence_id']] = [
                    'result'=>$sequence['result'],
                    'Pmax'=>$sequence['p_max'],
                    'Ptype'=>$sequence['p_type'],
                    'category'=>$sequence['category'],
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
            $result['cat'.$arr['category']]['sequence'][] = $arr;
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

        Sequence::generateDefault($object_id);

        Yii::$app->getResponse()->setStatusCode(204);
        return 'success';
    }

    public function actionReset($object_id, $comment='') {
        $object = Object::findOne($object_id);

        if($object === null) {
            throw new ServerErrorHttpException('No object found with ID: '.$object_id);
        }

        Sequence::generateDefault($object_id, $comment);

        Yii::$app->getResponse()->setStatusCode(204);
        return 'success';
    }

    public function actionLastdata($object_id=null) {
        $data = [];
        if($object_id) {
            $object = Object::findOne($object_id);

            if($object === null) {
                throw new ServerErrorHttpException('No object found with ID: '.$object_id);
            }
            $data = $this->prepareLastData($object);
        } else {
            $objects = Object::find()->all();
            foreach ($objects as $object) {
                $data[] = $this->prepareLastData($object);
            }
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $data;
    }

    private function prepareLastData($object) {
        $data = ['objId'=>$object->id, 'name'=>$object->name, 'result'=>$object->result];

        foreach ([1, 3] as $pType) {
            $lastSequence = Sequence::find()->where(['object_id'=>$object->id])->andWhere(['p_type'=>$pType])->orderBy('created_at DESC')->one();
            $data['p'.$pType.'Result'] = $lastSequence->result;
        }

        $lastLocation = Location::find()->where(['object_id'=>$object->id])->asArray()->orderBy('created_at DESC')->one();
        if($lastLocation) {
            $data['lastLocation'] = [
                'dateTime'=>Helper::dateFormatWithMS($lastLocation['date_time']), 
                'p0'=>$lastLocation['p0'],
                'lat'=>$lastLocation['lat'],
                'lon'=>$lastLocation['lon']
            ];
        } else {
            $data['lastLocation'] = [];
        }

        return $data;
    }
}