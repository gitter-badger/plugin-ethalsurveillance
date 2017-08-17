<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ethalsurveillance extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom */
    public static function cron() {
      foreach (eqLogic::byType('ethalsurveillance', true) as $ethalsurveillance) {

        $equipementType = '';
        $etat = self::ethCheckValue($ethalsurveillance,'etat');
        $pGeneral = $ethalsurveillance->getConfiguration('general','');
        $configCmdEquipement = $ethalsurveillance->getConfiguration('cmdequipement','');

        $cmdEquipement = cmd::byString($configCmdEquipement);
        if (is_object($cmdEquipement)) {
          if ($cmdEquipement->getSubType() == 'numeric') {
            $equipementType = 'numeric';
          }  elseif ($cmdEquipement->getSubType() == 'binary') {
            $equipementType = 'binary';
          }     
        }
        
        $_option = array('equipement_id' => $ethalsurveillance->getId());
        
        if ($etat == 1 and $equipementType == 'numeric' and $pGeneral != '1') {
          self::checkequipement($_option);
        }

      }
      
    }
    
    
    /*
    * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom */
    public static function cron5() {
      foreach (eqLogic::byType('ethalsurveillance', true) as $ethalsurveillance) {
          
        $configDebutheure = $ethalsurveillance->getConfiguration('debutheure','');
        
        $currentTime = time();

        $expectedStoppedTime = -1;
        $expectedStartedTime = -1;
        $expectedStartedTimeMin = -1;
        $expectedStartedTimeMax = -1;
        $expectedStoppedTimeMin = -1;
        $expectedStoppedTimeMax = -1;
 
        $dayconfigTempsMini = $ethalsurveillance->getConfiguration(date('N',$currentTime).'tempsmini',0);
        $dayconfigTempsMax = $ethalsurveillance->getConfiguration(date('N',$currentTime).'tempsmax',0);
        $dayconfigExpectedStoppedTime = $ethalsurveillance->getConfiguration(date('N',$currentTime).'expectedstoppedtime','');
        $dayconfigExpectedStartedTime = $ethalsurveillance->getConfiguration(date('N',$currentTime).'expectedstartedtime','');

        $configTempsMini = $ethalsurveillance->getConfiguration('tempsmini',0);
        $configTempsMax = $ethalsurveillance->getConfiguration('tempsmax',0);
        $configExpectedStoppedTime = $ethalsurveillance->getConfiguration('expectedstoppedtime','');
        $configExpectedStartedTime = $ethalsurveillance->getConfiguration('expectedstartedtime','');

        if ($dayconfigExpectedStoppedTime != ''){
          $configExpectedStoppedTime=$dayconfigExpectedStoppedTime;
        }

        if ($dayconfigExpectedStartedTime != ''){
          $configExpectedStartedTime=$dayconfigExpectedStartedTime;
        }

        if ($dayconfigTempsMini != 0){
          $configTempsMini=$dayconfigTempsMini;
        }

        if ($dayconfigTempsMax != 0){
          $configTempsMax=$dayconfigTempsMax;
        }

        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : min temps set to->' . $configTempsMini);
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : max temps set to->' . $configTempsMax);

        /* verification debut heure */
        if ($configDebutheure != ''){
          $debutHeure = DateTime::createFromFormat('Gi', $configDebutheure)->getTimestamp();
          $debutHeureMin = $debutHeure-120;
          $debutHeureMax = $debutHeure+120;          
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : debut heures set to->' .date('H:i:s',$debutHeureMin).'/' .date('H:i:s',$debutHeure).'/'.date('H:i:s',$debutHeureMax));
        } else {
          $debutHeure = $currentTime;
          $debutHeureMin = $debutHeure;
          $debutHeureMax = $debutHeure;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : debut heure default set to->' .date('H:i:s',$debutHeureMin).'/' .date('H:i:s',$debutHeure).'/'.date('H:i:s',$debutHeureMax));
        }

        if ($configExpectedStoppedTime == '') {
          $expectedStoppedTime = -1;
          $expectedStoppedTimeMin = -1;
          $expectedStoppedTimeMax = -1;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Arret prévu set to->' . $expectedStoppedTime);
        } else {
          $expectedStoppedTime = DateTime::createFromFormat('Gi', $configExpectedStoppedTime)->getTimestamp();
          $expectedStoppedTimeMin = $expectedStoppedTime;
          $expectedStoppedTimeMax = $expectedStoppedTime+310;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Arret prévu entre-> '.date('H:i:s',$expectedStoppedTimeMin).' et '.date('H:i:s',$expectedStoppedTimeMax));
        }

        if ($configExpectedStartedTime == '') {
          $expectedStartedTime = -1;
          $expectedStartedTimeMin = -1;
          $expectedStartedTimeMax = -1;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Marche prévu set to->' . $expectedStartedTime);
        } else {
          $expectedStartedTime = DateTime::createFromFormat('Gi', $configExpectedStartedTime)->getTimestamp();
          $expectedStartedTimeMin = $expectedStartedTime;
          $expectedStartedTimeMax = $expectedStartedTime+310;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Marche prévu entre-> ' .date('H:i:s',$expectedStartedTimeMin).' et '.date('H:i:s',$expectedStartedTimeMax));
        }

        $etat = self::ethCheckValue($ethalsurveillance,'etat');
        $alarme = self::ethCheckValue($ethalsurveillance,'alarme');

        $currentTempsFct = $currentTime- $ethalsurveillance->getConfiguration('startedtime');
        $currentTempsFctTotal = $ethalsurveillance->getConfiguration('previoustpsfct') + $currentTempsFct;


        /* Alarme si pas demarré a l'heure prevu + temps mini de fonctionnement et debut heure non vide */
        if ($currentTime >= ($debutHeure+($configTempsMini*60)) and $etat == 0 and $configTempsMini !=0) {
          if ($alarme ==0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Alarme debut heure->' . date('H:i:s',$debutHeure));
          }
          self::ethAlarmeCode($ethalsurveillance,1);
        }
        

        if ($etat == 1) {      
          
          $fmtCurrentTempsFct = self::ethFormatTpsFct($currentTempsFct);
          $fmtCurrentTempsFctTotal = self::ethFormatTpsFct($currentTempsFctTotal);
        
          $ethalsurveillance->checkAndUpdateCmd('tempsfct',$currentTempsFct);
          $ethalsurveillance->checkAndUpdateCmd('tempsfct_hms',$fmtCurrentTempsFct);
          $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal',$currentTempsFctTotal);
          $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);
        }
        
        if ($currentTempsFct >= ($configTempsMax*60) and $etat == 1 and $configTempsMax !=0) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Alarme Temps Max->' . $currentTempsFct);
          }
          self::ethAlarmeCode($ethalsurveillance,4);
        }

        if ($currentTime >= $expectedStoppedTimeMin and $currentTime <= $expectedStoppedTimeMax and $etat == 1 and $expectedStoppedTime !=-1) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Alarme Expected Stopped Time->' .date('H:i:s',$expectedStoppedTimeMin).'<' .date('H:i:s',$currentTime) .'>'.date('H:i:s',$expectedStoppedTimeMax));
          }
          self::ethAlarmeCode($ethalsurveillance,8);
        }

        if ($currentTime >= $expectedStartedTimeMin and $currentTime <= $expectedStartedTimeMax and $etat == 0 and $expectedStartedTime !=-1) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : cron5 : Alarme Expected Started Time->' .date('H:i:s',$expectedStartedTimeMin).'<' .date('H:i:s',$currentTime) .'>'.date('H:i:s',$expectedStartedTimeMax));
          }
          self::ethAlarmeCode($ethalsurveillance,16);
        }
      }
    }
    

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
    */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
    public static function cronDayly() {

    }
    */

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {

    }

    public function postSave() {

    }

    public function preUpdate() {
        
    }

    public function postUpdate() {

      $this->EthcreateCmd();
      
    }

    public function preRemove() {
      
      $listener = listener::byClassAndFunction('ethalsurveillance', 'checkequipement', array('equipement_id' => $this->getId()));
      if (is_object($listener)) {
        log::add('ethalsurveillance', 'debug', 'Suppression du listener->checkequipement');        
        $listener->remove();
      }
    }            

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /* public Ethal Surveillance plugin function */
    public function checkequipement($_option) {

      log::add('ethalsurveillance', 'debug', 'checkequipement started');

      $ethalsurveillance = ethalsurveillance::byId($_option['equipement_id']);
      if (is_object($ethalsurveillance) and $ethalsurveillance->getIsEnable() == 1) {
        
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName(). ' : checkequipement : equipement trouvé et actif');

        $etat = 0;
        $alarme = 0;
        $equipementType = '';
        $currentTime = time();
        $memoCurrentTimeStatus = 0;
        $minPuissanceDelaiReach = 0;

        $expectedStoppedTime = -1;
        $expectedStartedTime = -1;
        $expectedStartedTimeMin = -1;
        $expectedStartedTimeMax = -1;
        $expectedStoppedTimeMin = -1;
        $expectedStoppedTimeMax = -1;

        $configCmdEquipement = $ethalsurveillance->getConfiguration('cmdequipement','');
        $puissance = $ethalsurveillance->getConfiguration('puissance',-100000);
        $minPuissance = $ethalsurveillance->getConfiguration('minpuissance',0);
        $maxPuissance = $ethalsurveillance->getConfiguration('maxpuissance',-10000);
        $pGeneral = $ethalsurveillance->getConfiguration('general','');
        $memoPuissance = $ethalsurveillance->getConfiguration('memopuissance',''); // Feature used
        $configDebutheure = $ethalsurveillance->getConfiguration('debutheure','');

        $minPuissanceDelai = $ethalsurveillance->getConfiguration('minpuissancedelai',0);
        $memoCurrentTime =   $ethalsurveillance->getConfiguration('memocurrenttime',0);    
        $memoCurrentTimeStatus = $ethalsurveillance->getConfiguration('memocurrenttimestatus',0);

        $dayconfigTempsMini = $ethalsurveillance->getConfiguration(date('N',$currentTime).'tempsmini',0);
        $dayconfigTempsMax = $ethalsurveillance->getConfiguration(date('N',$currentTime).'tempsmax',0);
        $dayconfigExpectedStoppedTime = $ethalsurveillance->getConfiguration(date('N',$currentTime).'expectedstoppedtime','');
        $dayconfigExpectedStartedTime = $ethalsurveillance->getConfiguration(date('N',$currentTime).'expectedstartedtime','');
        $dayconfigCptAlarmeHaute = $ethalsurveillance->getConfiguration(date('N',$currentTime).'cptalarmehaute',0);

        $configTempsMini = $ethalsurveillance->getConfiguration('tempsmini',0);
        $configTempsMax = $ethalsurveillance->getConfiguration('tempsmax',0);
        $configExpectedStoppedTime = $ethalsurveillance->getConfiguration('expectedstoppedtime','');
        $configExpectedStartedTime = $ethalsurveillance->getConfiguration('expectedstartedtime','');
        $configCptAlarmeHaute = $ethalsurveillance->getConfiguration('cptalarmehaute',0);


        if ($dayconfigExpectedStoppedTime !='' ){
          $configExpectedStoppedTime=$dayconfigExpectedStoppedTime;
        }

        if ($dayconfigExpectedStartedTime !=''){
          $configExpectedStartedTime=$dayconfigExpectedStartedTime;
        }

        if ($dayconfigTempsMini !=0){
          $configTempsMini=$dayconfigTempsMini;
        }

        if ($dayconfigTempsMax !=0){
          $configTempsMax=$dayconfigTempsMax;
        }

        if ($dayconfigCptAlarmeHaute !=0){
          $configCptAlarmeHaute=$dayconfigCptAlarmeHaute;
        }


        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : min temps set to ->' . $configTempsMini);
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : max temps set to ->' . $configTempsMax);
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : min puissance set to->' . $minPuissance);
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : max puissance set to->' . $maxPuissance);
        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : puissance set to ->' . $puissance);
     
        /* verification debut heure */
        if ($configDebutheure != ''){
          $debutHeure = DateTime::createFromFormat('Gi', $configDebutheure)->getTimestamp();
          $debutHeureMin = $debutHeure-120;
          $debutHeureMax = $debutHeure+120;          
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : debut heures set to->' .date('H:i:s',$debutHeureMin).'/' .date('H:i:s',$debutHeure).'/'.date('H:i:s',$debutHeureMax));
        } else {
          $debutHeure = $currentTime;
          $debutHeureMin = $debutHeure;
          $debutHeureMax = $debutHeure;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : debut heure default set to->' .date('H:i:s',$debutHeureMin).'/' .date('H:i:s',$debutHeure).'/'.date('H:i:s',$debutHeureMax));
        }
        
        if ($configExpectedStoppedTime == '') {
          $expectedStoppedTime = -1;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Arret prévu set to->' . $expectedStoppedTime);
        } else {
          $expectedStoppedTime = DateTime::createFromFormat('Gi', $configExpectedStoppedTime)->getTimestamp();
          $expectedStoppedTimeMin = $expectedStoppedTime;
          $expectedStoppedTimeMax = $expectedStoppedTime+310;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Arret prévu entre ->'.date('H:i:s',$expectedStoppedTimeMin).' et '.date('H:i:s',$expectedStoppedTimeMax));
        }

        if ($configExpectedStartedTime == '') {
          $expectedStartedTime = -1;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Marche prévu set to->' .$expectedStartedTime);
        } else {
          $expectedStartedTime = DateTime::createFromFormat('Gi', $configExpectedStartedTime)->getTimestamp();
          $expectedStartedTimeMin = $expectedStartedTime;
          $expectedStartedTimeMax = $expectedStartedTime+310;
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Marche prévu entre ->'.date('H:i:s',$expectedStartedTimeMin).' et '.date('H:i:s',$expectedStartedTimeMax));
        }

        /* Verification de la commande de mesure de l'equipement*/
        $cmdEquipement = cmd::byString($configCmdEquipement);
        if (is_object($cmdEquipement)) {
          if ($cmdEquipement->getSubType() == 'numeric') {
            $equipementType = 'numeric';
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Numeric : Power measurement name->' . $cmdEquipement->getHumanName() .' Power measurement value->'.$cmdEquipement->execCmd());
          }  elseif ($cmdEquipement->getSubType() == 'binary') {
            $equipementType = 'binary';
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Binary : Equipement Cmd name->' . $cmdEquipement->getHumanName() .' Cmd equipement state->'.$cmdEquipement->execCmd());
          } else {
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : NOT Binary/Numeric : Equipement Cmd name->' . $cmdEquipement->getHumanName());            
          }     
        } else {          
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Equipment cmd not found');
        }
        
        $etat = self::ethCheckValue($ethalsurveillance,'etat');
        $alarme = self::ethCheckValue($ethalsurveillance,'alarme');
                
        $cmdValue = $cmdEquipement->execCmd();
        $compteur =  $ethalsurveillance->getCmd(null,'count')->execCmd();           


        if ($pGeneral == '1' and $equipementType == 'numeric') {
          $cmdValue = $cmdValue - $puissance;
          $minPuissance = 0;
          $maxPuissance = 0;
          $minPuissanceDelai = 0;          
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : cpt général =1, min/max cmd set to->0');
        }

        if ($equipementType == 'binary') {
          $minPuissance = 0;
          $maxPuissance = 1;
          $minPuissanceDelai = 0;          
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : binary cmd, max cmd set to->1 min cmd set to->0');
        }

        log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value etat->'.$etat. ' value cmd ->'.$cmdValue. ' min cmd->'.$minPuissance . ' max cmd->'.$maxPuissance);        
        /*
            0903 < 2330 < 0907
            2327 < 2330 < 2333 
        */

        if ($equipementType == 'numeric' or $equipementType == 'binary') {
        
          if ($cmdValue >= $maxPuissance and $etat == 0 and  (($currentTime >= $debutHeureMin and $currentTime <= $debutHeureMax) or $debutHeureMin == $debutHeure))  {

            $etat = 1;
            $ethalsurveillance->checkAndUpdateCmd('etat',$etat);

            $ethalsurveillance->checkAndUpdateCmd('startedtime',date('H:i:s',$currentTime));
            $ethalsurveillance->checkAndUpdateCmd('stoppedtime','-'); 

            $ethalsurveillance->checkAndUpdateCmd('count',$compteur+1);


            $ethalsurveillance->setConfiguration('startedtime',$currentTime);
            $ethalsurveillance->setConfiguration('memopuissance',$cmdValue);
            $ethalsurveillance->save();

            $alCode32 = $ethalsurveillance->getCmd(null,'code_alarme')->getConfiguration('ethalarmecode32');

            self::ethResetAlarme($ethalsurveillance);
            $alarme = self::ethCheckValue($ethalsurveillance,'alarme');

            if (($compteur+1) >= $configCptAlarmeHaute and $configCptAlarmeHaute != 0) {
              if ($alarme == 0) {
                $alarme = 1;
                $ethalsurveillance->checkAndUpdateCmd('alarme',1);
                log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Valeur compteur haute->' . $ethalsurveillance->getCmd(null,'count')->execCmd());
              }
              log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value change etat->'.$etat. ' compteur->'.$ethalsurveillance->getCmd(null,'count')->execCmd());
              self::ethAlarmeCode($ethalsurveillance,32);
            }else {
              //self::ethResetAlarme($ethalsurveillance);
            }

            if ($alCode32 !=1 )  {
              //self::ethResetAlarme($ethalsurveillance);
            }

          }


          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value memoCurrentTimeStatus->'.$memoCurrentTimeStatus. ' value minPuissanceDelaiReach->'.$minPuissanceDelaiReach. ' value memoCurrentTime+minPuissanceDelai->'.($memoCurrentTime+($minPuissanceDelai*60)));
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value change etat->'.$etat. ' compteur->'.$ethalsurveillance->getCmd(null,'count')->execCmd());
          
          /* gestion du delai sur la puissance mini*/
          if ($cmdValue <= $minPuissance and $etat == 1 and $equipementType == 'numeric' and $pGeneral != '1' and $memoCurrentTimeStatus == 0) {
            $memoCurrentTime = $currentTime;
            $memoCurrentTimeStatus = 1;
            $ethalsurveillance->setConfiguration('memocurrenttime',$memoCurrentTime);
            $ethalsurveillance->setConfiguration('memocurrenttimestatus',$memoCurrentTimeStatus);
            $ethalsurveillance->save();
          }

          if ($cmdValue >= $minPuissance and $etat == 1 and $equipementType == 'numeric' and $pGeneral != '1' and $memoCurrentTimeStatus == 1) {
            $memoCurrentTime = $currentTime;
            $memoCurrentTimeStatus = 0;
            $ethalsurveillance->setConfiguration('memocurrenttime',$memoCurrentTime);
            $ethalsurveillance->setConfiguration('memocurrenttimestatus',$memoCurrentTimeStatus);
            $ethalsurveillance->save();
          }
          /* End gestion du delai sur la puissance mini*/
          
          if ($currentTime >= ($memoCurrentTime+($minPuissanceDelai*60)) and $memoCurrentTimeStatus == 1 ) {
            $minPuissanceDelaiReach = 1; 
          }
          

          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value memoCurrentTimeStatus->'.$memoCurrentTimeStatus. ' value minPuissanceDelaiReach->'.$minPuissanceDelaiReach. ' value memoCurrentTime+minPuissanceDelai->'.($memoCurrentTime+($minPuissanceDelai*60)));
          log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value change etat->'.$etat. ' compteur->'.$ethalsurveillance->getCmd(null,'count')->execCmd());


          if (($cmdValue <= $minPuissance and ($equipementType == 'binary'  or $pGeneral == '1')  and $etat == 1) or ($minPuissanceDelaiReach == 1 and $etat == 1) ) {

            $etat = 0;
            $minPuissanceDelaiReach = 0;

            $ethalsurveillance->setConfiguration('memocurrenttime',0);
            $ethalsurveillance->setConfiguration('memocurrenttimestatus',0);

            $ethalsurveillance->checkAndUpdateCmd('etat',$etat);
            $ethalsurveillance->checkAndUpdateCmd('stoppedtime',date('H:i:s',$currentTime)); 
            
            $ethalsurveillance->setConfiguration('stoppedtime',$currentTime);
            $ethalsurveillance->setConfiguration('memopuissance',0);
            
            $currentTempsFct = $currentTime - $ethalsurveillance->getConfiguration('startedtime');
            $currentTempsFctTotal = $ethalsurveillance->getConfiguration('previoustpsfct') + $currentTempsFct;

            $ethalsurveillance->setConfiguration('previoustpsfct',$currentTempsFctTotal);
            $ethalsurveillance->save();

            $fmtCurrentTempsFct = self::ethFormatTpsFct($currentTempsFct);
            $fmtCurrentTempsFctTotal = self::ethFormatTpsFct($currentTempsFctTotal);

            $ethalsurveillance->checkAndUpdateCmd('tempsfct',$currentTempsFct);
            $ethalsurveillance->checkAndUpdateCmd('tempsfct_hms', $fmtCurrentTempsFct);
            $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal',$currentTempsFctTotal);
            $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);

            if ($currentTempsFct <= ($configTempsMini*60) and $configTempsMini !=0) {
              if ($alarme == 0){
                $alarme = 1;
                $ethalsurveillance->checkAndUpdateCmd('alarme',1);
                log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Temps min, alarme set to->1');
              }
              self::ethAlarmeCode($ethalsurveillance,2);
            }

            if ($currentTempsFct >= ($configTempsMax*60) and $configTempsMax !=0) {
              if ($alarme == 0){
                $alarme = 1;
                $ethalsurveillance->checkAndUpdateCmd('alarme',1);
                log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Temps max, alarme set to->1');
              }
              self::ethAlarmeCode($ethalsurveillance,4);
            }

            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Started Time->'.$ethalsurveillance->getConfiguration('startedtime').' Stopped Time->'. $currentTime);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value Temps mini(sec)->'.($configTempsMini*60).' Valeur Current Temps de fct->'. $currentTempsFct);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value Temps max(sec)->'.($configTempsMax*60).' Valeur Current Temps de fct->'. $currentTempsFct);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : value change etat->'.$etat);
          }
        }

        $currentTempsFct = $currentTime- $ethalsurveillance->getConfiguration('startedtime');
        $currentTempsFctTotal = $ethalsurveillance->getConfiguration('previoustpsfct') + $currentTempsFct;

        /* Alarme si pas demarré a l'heure prevu + temps mini de fonctionnement et debut heure non vide */
        if ($currentTime >= ($debutHeure+($configTempsMini*60)) and $etat == 0 and $configTempsMini !=0) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Alarme debut heure->' . $debutHeure);
          }
          self::ethAlarmeCode($ethalsurveillance,1);
        }
        if ($currentTempsFct >= ($configTempsMax*60) and $etat == 1 and $configTempsMax !=0) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Alarme Temps max->' . $currentTempsFct);
          }
          self::ethAlarmeCode($ethalsurveillance,4);
        }

        if ($currentTime >= $expectedStoppedTimeMin and $currentTime <= $expectedStoppedTimeMax and $etat ==1  and $expectedStoppedTime !=-1) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Alarme Expected Stopped Time->' .date('H:i:s',$expectedStoppedTimeMin).'<' .date('H:i:s',$currentTime) .'>'.date('H:i:s',$expectedStoppedTimeMax));
          }
          self::ethAlarmeCode($ethalsurveillance,8);
        }

        if ($currentTime >= $expectedStartedTimeMin and $currentTime <= $expectedStartedTimeMax and $etat == 0 and $expectedStartedTime !=-1) {
          if ($alarme == 0){
            $alarme = 1;
            $ethalsurveillance->checkAndUpdateCmd('alarme',1);
            log::add('ethalsurveillance', 'debug', $ethalsurveillance->getName().' : checkequipement : Alarme Expected Started Time->' .date('H:i:s',$expectedStartedTimeMin).'<' .date('H:i:s',$currentTime) .'>'.date('H:i:s',$expectedStartedTimeMax));
          }
          self::ethAlarmeCode($ethalsurveillance,16);
        }

        if ($etat == 1) {
          $fmtCurrentTempsFct = self::ethFormatTpsFct($currentTempsFct);
          $fmtCurrentTempsFctTotal = self::ethFormatTpsFct($currentTempsFctTotal);

          $ethalsurveillance->checkAndUpdateCmd('tempsfct',$currentTempsFct);
          $ethalsurveillance->checkAndUpdateCmd('tempsfct_hms',$fmtCurrentTempsFct);
          $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal',$currentTempsFctTotal);
          $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);
        }           
      
      }

    }

    /* private plugin function */

    private function ethResetAlarme($eq) {
      
        $eq->checkAndUpdateCmd('alarme',0);
        $eq->checkAndUpdateCmd('code_alarme',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode1',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode2',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode4',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode8',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode16',0);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode32',0);
 
      $eq->getCmd(null,'code_alarme')->save();
      log::add('ethalsurveillance', 'debug', $eq->getName().' : ethResetAlarme : Alarme Reset');

    }
    
    private function ethAlarmeCode($eq,$code) {

      //$alCode = $eq->getConfiguration('alarmecode'.$code);
      $alCode = $eq->getCmd(null,'code_alarme')->getConfiguration('ethalarmecode'.$code);
      log::add('ethalsurveillance', 'debug', $eq->getName().' : ethAlarmeCode : check Alarme Code '.$code .' current value->'.$alCode);

      if ($alCode != 1) {
        $eq->checkAndUpdateCmd('code_alarme',$eq->getCmd(null,'code_alarme')->execCmd()+$code);
        $eq->getCmd(null,'code_alarme')->setConfiguration('ethalarmecode'.$code,1);
        $eq->getCmd(null,'code_alarme')->save();          
        log::add('ethalsurveillance', 'debug', $eq->getName().' : ethAlarmeCode : Alarme Code set to->'.$code);
      }
    }  

    private function ethFormatTpsFct($myTime) {
      
      $myValue = '';
      if ((floor($myTime / (3600*24))) == 0) {
        $myValue = gmdate('H:i:s',$myTime);
      } else {
        $myValue = strval(floor($myTime / (3600*24))).'j '.gmdate('H:i:s',$myTime);
      }
      log::add('ethalsurveillance', 'debug', 'Function : ethFormatTpsFct : Temps Fct->' .$myValue);
      return $myValue;
    }

    private function ethCheckValue($eq,$name) {
      $value = $eq->getCmd(null,$name)->execCmd();

      log::add('ethalsurveillance', 'debug', $eq->getName().' : ethCheckValue : '.$name. ' current Type value->' . gettype($value));
      log::add('ethalsurveillance', 'debug', $eq->getName().' : ethCheckValue : '.$name. ' current value->' . $value);

      if (strlen($value) == 0 or $value == '' or $value == null) {
        $eq->checkAndUpdateCmd($name,0);
        $value = 0; 
        log::add('ethalsurveillance', 'debug', $eq->getName().' : ethCheckValue : '.$name. ' return init value->' . $value);
      } else {
        log::add('ethalsurveillance', 'debug', $eq->getName().' : ethCheckValue : '.$name. ' return value->' . $value);
      }
      return $value;      
    }

  private function EthcreateCmd() {
    
        /* commande alarme fonctionnement */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'alarme');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->alarme');        
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('alarme');
          $ethalsurveillanceCmd->setName(__('Alarme', __FILE__));
        }    
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('binary');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setOrder(0);

        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->alarme');

        /* commande alarme code fonctionnement 
        debut heure : 1
        Temps mini : 2
        Temps maxi : 4
        Arret prevu : 8
        Marche prevu : 16
        Compteur haut : 32
        */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'code_alarme');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->code_alarme');        
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('code_alarme');
        }    
        $ethalsurveillanceCmd->setName(__('Code Alarme', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('numeric');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setOrder(1);

        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->code_alarme');

        /* commande Temps de fonctionnement format heure:min:sec*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'tempsfct_hms');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->tempsfct_hms');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('tempsfct_hms');
        }
        $ethalsurveillanceCmd->setName(__('Temps Actif(H:M:S)', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('string');
        $ethalsurveillanceCmd->setOrder(2);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->tempsfct_hms');

        /* commande Temps de fonctionnement en seconde*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'tempsfct');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->tempsfct');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('tempsfct');
        }
        $ethalsurveillanceCmd->setName(__('Temps Actif', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('numeric');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setUnite('s');
        $ethalsurveillanceCmd->setOrder(3);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->tempsfct');


        /* commande Temps de fonctionnement global format heure:min:sec*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'tempsfcttotal_hms');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->tempsfctglobal_hms');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('tempsfcttotal_hms');
        }
        $ethalsurveillanceCmd->setName(__('Temps Actif Total(H:M:S)', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('string');
        $ethalsurveillanceCmd->setOrder(4);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->tempsfcttotal_hms');

        
        /* commande Temps de fonctionnement global en seconde*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'tempsfcttotal');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->tempsfcttotal');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('tempsfcttotal');
        }
        $ethalsurveillanceCmd->setName(__('Temps Actif Total', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('numeric');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setUnite('s');
        $ethalsurveillanceCmd->setOrder(5);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->tempsfcttotal');

        /* commande RAZ Temps Fct Total */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'raztempsfcttotal');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->raztempsfcttotal');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('raztempsfcttotal');
        }
        $ethalsurveillanceCmd->setName(__('RAZ Tps Actif Total', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('action');
        $ethalsurveillanceCmd->setSubType('other');
        $ethalsurveillanceCmd->setOrder(6);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->raztempsfcttotal');

        /* commande heure demarrage heure:min:sec*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'startedtime');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->startedtime');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('startedtime');
        }
        $ethalsurveillanceCmd->setName(__('Actif à', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('string');
        $ethalsurveillanceCmd->setOrder(7);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->startedtime');
        
        /* commande heure arret heure:min:sec*/
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'stoppedtime');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->stoppedtime');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('stoppedtime');
        }
        $ethalsurveillanceCmd->setName(__('Inactif à', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('string');
        $ethalsurveillanceCmd->setOrder(8);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->stoppedtime');
        
        /* commande etat fonctionnement */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'etat');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->etat');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('etat');
        }
        $ethalsurveillanceCmd->setName(__('Etat', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('binary');
        $ethalsurveillanceCmd->setIsHistorized(1);
        $ethalsurveillanceCmd->setConfiguration('historizeMode','none');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setOrder(9);
        $ethalsurveillanceCmd->save();

        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->etat');
        
        /* commande compteur */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'count');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->count');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('count');
        }
        $ethalsurveillanceCmd->setName(__('Compteur', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('info');
        $ethalsurveillanceCmd->setSubType('numeric');
        $ethalsurveillanceCmd->setTemplate('mobile','line');
        $ethalsurveillanceCmd->setTemplate('dashboard','line');
        $ethalsurveillanceCmd->setOrder(10);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->count');

        /* commande Set compteur plus */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'setcountplus');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->setcountplus');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
        $ethalsurveillanceCmd->setLogicalId('setcountplus');
        }
        $ethalsurveillanceCmd->setName(__('Compteur +', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('action');
        $ethalsurveillanceCmd->setSubType('other');
        $ethalsurveillanceCmd->setOrder(11);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->setcountplus');

        /* commande Set compteur moins */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'setcountmoins');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->setcountmoins');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('setcountmoins');
        }
        $ethalsurveillanceCmd->setName(__('Compteur -', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('action');
        $ethalsurveillanceCmd->setSubType('other');
        $ethalsurveillanceCmd->setOrder(12);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->setcountmoins');
        
        /* commande RAZ compteur  */
        $ethalsurveillanceCmd = ethalsurveillanceCmd::byEqLogicIdAndLogicalId($this->getId(),'razcount');
        if (!is_object($ethalsurveillanceCmd)) {
          log::add('ethalsurveillance', 'debug', 'Création de la commande->razcount');
          $ethalsurveillanceCmd = new ethalsurveillanceCmd();
          $ethalsurveillanceCmd->setLogicalId('razcount');
        }
        $ethalsurveillanceCmd->setName(__('RAZ Compteur', __FILE__));
        $ethalsurveillanceCmd->setEqLogic_id($this->getId());
        $ethalsurveillanceCmd->setEqType('ethalsurveillance');
        $ethalsurveillanceCmd->setType('action');
        $ethalsurveillanceCmd->setSubType('other');
        $ethalsurveillanceCmd->setOrder(13);
        
        $ethalsurveillanceCmd->save();
        log::add('ethalsurveillance', 'debug', 'Mise à jour de la commande->razcount');

        /* listener de la mesure de puissance our de la commande d'etat*/
        if ($this->getIsEnable() == 1 and $this->getConfiguration('cmdequipement') != null) {
          $listener = listener::byClassAndFunction('ethalsurveillance', 'checkequipement', array('equipement_id' => $this->getId()));
          if (!is_object($listener)) {
            log::add('ethalsurveillance', 'debug', 'Création du listener->checkequipement');        
            $listener = new listener();
          }
          $listener->setClass('ethalsurveillance');
          $listener->setFunction('checkequipement');
          $listener->setOption(array('equipement_id' => $this->getId()));
          $listener->emptyEvent();
          $listener->addEvent($this->getConfiguration('cmdequipement'));
          
          $listener->save();
          log::add('ethalsurveillance', 'debug', 'Mise à jour du listener->checkequipement');

        }
    }      

    /* public plugin function */
 
    public function ethCumulTps($_startDate = null, $_endDate = null) {
      $etatCmd = $this->getCmd(null, 'etat');
      if (!is_object($etatCmd)) {
        return array();
      }
      $return = array();
      $prevValue = 0;
      $prevDatetime = 0;
      $day = null;
      foreach ($etatCmd->getHistory($_startDate, $_endDate) as $history) {
        if (date('Y-m-d', strtotime($history->getDatetime())) != $day && $prevValue == 1 && $day != null) {
          if (strtotime($day . ' 23:59:59') > $prevDatetime) {
            $return[$day][1] += (strtotime($day . ' 23:59:59') - $prevDatetime) / 3600;
          }
          $prevDatetime = strtotime(date('Y-m-d 00:00:00', strtotime($history->getDatetime())));
        }
        $day = date('Y-m-d', strtotime($history->getDatetime()));
        if (!isset($return[$day])) {
          $return[$day] = array(strtotime($day . ' 00:00:00 UTC') * 1000, 0);
        }
        if ($history->getValue() == 1 && $prevValue == 0) {
          $prevDatetime = strtotime($history->getDatetime());
          $prevValue = 1;
        }
        if ($history->getValue() == 0 && $prevValue == 1) {
          if ($prevDatetime > 0 && strtotime($history->getDatetime()) > $prevDatetime) {
            $return[$day][1] += (strtotime($history->getDatetime()) - $prevDatetime) / 3600;
          }
          $prevValue = 0;
        }
      }
      
      return $return;
    }

    // Not used , work in progress
    public function ethCumulCpt($_startDate = null, $_endDate = null) {
      $cptCmd = $this->getCmd(null, 'count');
      if (!is_object($cptCmd)) {
        return array();
      }
      $return = array();
      $prevValue = 0;
      $prevDatetime = 0;
      $day = null;
      foreach ($ctpCmd->getHistory($_startDate, $_endDate) as $history) {
        if (date('Y-m-d', strtotime($history->getDatetime())) != $day && $prevValue == 1 && $day != null) {
          if (strtotime($day . ' 23:59:59') > $prevDatetime) {
            $return[$day][1] += (strtotime($day . ' 23:59:59') - $prevDatetime) / 3600;
          }
          $prevDatetime = strtotime(date('Y-m-d 00:00:00', strtotime($history->getDatetime())));
        }
        $day = date('Y-m-d', strtotime($history->getDatetime()));
        if (!isset($return[$day])) {
          $return[$day] = array(strtotime($day . ' 00:00:00 UTC') * 1000, 0);
        }
        if ($history->getValue() == 1 && $prevValue == 0) {
          $prevDatetime = strtotime($history->getDatetime());
          $prevValue = 1;
        }
        if ($history->getValue() == 0 && $prevValue == 1) {
          if ($prevDatetime > 0 && strtotime($history->getDatetime()) > $prevDatetime) {
            $return[$day][1] += (strtotime($history->getDatetime()) - $prevDatetime) / 3600;
          }
          $prevValue = 0;
        }
      }
      
      return $return;
    }

    /*     * **********************Getteur Setteur*************************** */

}

class ethalsurveillanceCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
      $ethalsurveillance = $this->getEqLogic();
      
      log::add('ethalsurveillance', 'debug', 'action->' . $this->getLogicalId());
      
      if ($this->getLogicalId() == 'setcountplus') {
        $compteur =  $ethalsurveillance->getCmd(null,'count')->execCmd();
        $ethalsurveillance->checkAndUpdateCmd('count',$compteur+1);
        log::add('ethalsurveillance', 'debug', 'Set Compteurs plus 1');
      }
      if ($this->getLogicalId() == 'setcountmoins') {
        $compteur =  $ethalsurveillance->getCmd(null,'count')->execCmd();
        $ethalsurveillance->checkAndUpdateCmd('count',$compteur-1);
        log::add('ethalsurveillance', 'debug', 'Set Compteurs moins 1');
      }
      if ($this->getLogicalId() == 'razcount') {
        $ethalsurveillance->checkAndUpdateCmd('count',0);
        log::add('ethalsurveillance', 'debug', 'RAZ Compteurs');

      }        
      if ($this->getLogicalId() == 'raztempsfcttotal') {
        $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal',0);
        $ethalsurveillance->checkAndUpdateCmd('tempsfcttotal_hms','-');
        $ethalsurveillance->setConfiguration('previoustpsfct',0);
        $ethalsurveillance->save();
        log::add('ethalsurveillance', 'debug', 'RAZ Temps Fct Total');
      }

    }

    /*     * **********************Getteur Setteur*************************** */
}

?>