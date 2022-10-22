<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Util;

/**
 * Description of Util
 *
 * @author bayarmagnai
 */
class Util {
     
    public function generateLogMessage($functionName, $logId, &$logStep, $extra){
        $tagName = '=====> FUNCTION['.$functionName.']';
        $logId = 'LOG ID['.$logId.']';
        $logStep += 1;
        $message =  $tagName.' '.$logId.' STEP['.($logStep).'] INFO[ '.$extra.' ]';
        return $message;
    }
    
    public function generateCode() {
        $code = "";
        for($i = 0; $i < 4; $i++) {
            $code .= rand(0,9);
        }
        return $code;
    }
    
    public function isNull($value){
        return $value == "" || $value == null ? null : $value;
    }
    
}
