<?php

namespace app\components;

class Helper {

    public static function dateFormatWithMS($timestamp) {
    	$dateArr = explode('.', $timestamp);
        $date = new \DateTime();
        $dateTime = $date->setTimestamp($dateArr[0])->format("Y-m-d H:i:s");
        if(isset($dateArr[1])) 
            $dateTime .= '.'.$dateArr[1];

        return $dateTime;
    }

    public static function timestampWithMS($date) {
        $dateArr = explode('.', $date);
        $date = new \DateTime($dateArr[0]);
        $timestamp = $date->getTimestamp();
        if(isset($dateArr[1])) 
            $timestamp .= '.'.$dateArr[1];

        return $timestamp;
    }
}