<?

trait GEN24_COMMON {

    protected function RequestJsonData($url) {

        SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 
        
        $streamContext = stream_context_create( array('http'=> array('timeout' => 5) ) ); //5 seconds

        $json = file_get_contents($url, false, $streamContext);

        if ($json === false) {
            $error = error_get_last();
            $errorMsg = implode (" | ", $error);
            SetValue($this->GetIDForIdent("ErrorCnt"), GetValue($this->GetIDForIdent("ErrorCnt")) + 1); 
            SetValue($this->GetIDForIdent("LastError"), $errorMsg);

            $logMsg =  sprintf("ERROR %s", $errorMsg);
            if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, $logMsg, 0); }
            IPS_LogMessage("GEN24", $logMsg);

            die();
        } else {
            SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
            SetValue($this->GetIDForIdent("LastDataReceived"), time()); 
        }
        return json_decode($json);
    }

    protected function CalcDuration_ms(float $timeStart) {
        $duration =  microtime(true)- $timeStart;
        return round($duration*1000,2);
    }	


    protected function RegisterProfiles() {


        if ( !IPS_VariableProfileExists('GEN24.Percent') ) {
            IPS_CreateVariableProfile('GEN24.Percent', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileDigits('GEN24.Percent', 0 );
            IPS_SetVariableProfileText('GEN24.Percent', "", " %" );
            //IPS_SetVariableProfileValues('GEN24.Prozent', 0, 0, 0);
        } 	

        if ( !IPS_VariableProfileExists('GEN24.Percent.1') ) {
            IPS_CreateVariableProfile('GEN24.Percent.1', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Percent.1', 1 );
            IPS_SetVariableProfileText('GEN24.Percent.1', "", " %" );
            //IPS_SetVariableProfileValues('GEN24.Prozent.1', 0, 0, 0);
        } 

        if ( !IPS_VariableProfileExists('GEN24.Percent.2') ) {
            IPS_CreateVariableProfile('GEN24.Percent.2', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Percent.2', 2 );
            IPS_SetVariableProfileText('GEN24.Percent.2', "", " %" );
            //IPS_SetVariableProfileValues('GEN24.Prozent.2', 0, 0, 0);
        } 	        

        if ( !IPS_VariableProfileExists('GEN24.Seconds') ) {
            IPS_CreateVariableProfile('GEN24.Seconds', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileDigits('GEN24.Seconds', 0 );
            IPS_SetVariableProfileText('GEN24.Seconds', "", " sec" );
            //IPS_SetVariableProfileValues('GEN24.Prozent.2', 0, 0, 0);
        } 	


        if ( !IPS_VariableProfileExists('GEN24.Watt') ) {
            IPS_CreateVariableProfile('GEN24.Watt', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileDigits('GEN24.Watt', 0 );
            IPS_SetVariableProfileText('GEN24.Watt', "", " W" );
            //IPS_SetVariableProfileValues('GEN24.Watt', 0, 0, 0);
        } 

        if ( !IPS_VariableProfileExists('GEN24.Watt.1') ) {
            IPS_CreateVariableProfile('GEN24.Watt.1', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Watt.1', 1 );
            IPS_SetVariableProfileText('GEN24.Watt.1', "", " W" );
            //IPS_SetVariableProfileValues('GEN24.Watt.1', 0, 0, 0);
        }         

        if ( !IPS_VariableProfileExists('GEN24.Watt.2') ) {
            IPS_CreateVariableProfile('GEN24.Watt.2', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Watt.2', 2 );
            IPS_SetVariableProfileText('GEN24.Watt.2', "", " W" );
            //IPS_SetVariableProfileValues('GEN24.Watt.1', 0, 0, 0);
        }  


        if ( !IPS_VariableProfileExists('GEN24.Power.2') ) {
            IPS_CreateVariableProfile('GEN24.Power.2', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Power.2', 2 );
            IPS_SetVariableProfileText('GEN24.Power.2', "", " W" );
            //IPS_SetVariableProfileValues('GEN24.Power.2', 0, 0, 0);
        } 

        if ( !IPS_VariableProfileExists('GEN24.SOC') ) {
            IPS_CreateVariableProfile('GEN24.SOC', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.SOC', 1 );
            IPS_SetVariableProfileText('GEN24.SOC', "", " %" );
            //IPS_SetVariableProfileValues('GEN24.SOC', 0, 0, 0);
        } 					

        if ( !IPS_VariableProfileExists('GEN24.Temp') ) {
            IPS_CreateVariableProfile('GEN24.Temp', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Temp', 1 );
            IPS_SetVariableProfileText('GEN24.Temp', "", " °C" );
            //IPS_SetVariableProfileValues('GEN24.Temp', 0, 0, 0);
        } 	

        

        if ( !IPS_VariableProfileExists('GEN24.StorCtl_Mod') ) {
            IPS_CreateVariableProfile('GEN24.StorCtl_Mod', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileText('GEN24.StorCtl_Mod', "", "" );
            IPS_SetVariableProfileAssociation ('GEN24.StorCtl_Mod', 0, "[%d] HOLD", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.StorCtl_Mod', 1, "[%d] CHARGE", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.StorCtl_Mod', 2, "[%d] DISCHARGE", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.StorCtl_Mod', 3, "[%d] CHARGE & DISCHARGE", "", -1);
        }

        if ( !IPS_VariableProfileExists('GEN24.ChaGriSet') ) {
            IPS_CreateVariableProfile('GEN24.ChaGriSet', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileText('GEN24.ChaGriSet', "", "" );
            IPS_SetVariableProfileAssociation ('GEN24.ChaGriSet', 0, "[%d] DISABLED", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaGriSet', 1, "[%d] ENABLED", "", -1);
        }

        if ( !IPS_VariableProfileExists('GEN24.ChaSt') ) {
            IPS_CreateVariableProfile('GEN24.ChaSt', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileText('GEN24.ChaSt', "", "" );
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 0, "[%d] Unbekannter Status", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 1, "[%d] OFF - Energiespeicher nicht verfügbar", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 2, "[%d] EMPTY", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 3, "[%d] DISCHAGING", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 4, "[%d] CHARGING", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 5, "[%d] FULL", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 6, "[%d] HOLDING", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 7, "[%d] TESTING", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.ChaSt', 8, "[%d] Unbekannter Status", "", -1);
        }        


        if ( !IPS_VariableProfileExists('GEN24.BatteryMode') ) {
            IPS_CreateVariableProfile('GEN24.BatteryMode', VARIABLE::TYPE_INTEGER );
            IPS_SetVariableProfileText('GEN24.BatteryMode', "", "" );
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 0, "[%d] disabled", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 1, "[%d] normal", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 2, "[%d] service", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 3, "[%d] charge boost", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 4, "[%d] nearly depleted", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 5, "[%d] suspended", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 6, "[%d] calibrate", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 7, "[%d] grid support", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 8, "[%d] deplete recovery", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 9, "[%d] non operable (voltage)", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 10, "[%d] non operable (temperature)", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 11, "[%d] preheating", "", -1);
            IPS_SetVariableProfileAssociation ('GEN24.BatteryMode', 12, "[%d] startup", "", -1);
        } 


        if ( !IPS_VariableProfileExists('GEN24.Current') ) {
            IPS_CreateVariableProfile('GEN24.Current', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Current', 3 );
            IPS_SetVariableProfileText('GEN24.Current', "", " A" );
            //IPS_SetVariableProfileValues('GEN24.Current', 0, 0, 0);
        } 

        if ( !IPS_VariableProfileExists('GEN24.Voltage') ) {
            IPS_CreateVariableProfile('GEN24.Voltage', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Voltage', 2 );
            IPS_SetVariableProfileText('GEN24.Voltage', "", " V" );
            //IPS_SetVariableProfileValues('GEN24.Voltage', 0, 0, 0);
        } 

        if ( !IPS_VariableProfileExists('GEN24.Frequency') ) {
            IPS_CreateVariableProfile('GEN24.Frequency', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.Frequency', 2 );
            IPS_SetVariableProfileText('GEN24.Frequency', "", " Hz" );
            //IPS_SetVariableProfileValues('GEN24.Frequency', 0, 0, 0);
        } 	
        
        if ( !IPS_VariableProfileExists('GEN24.Wh') ) {
            IPS_CreateVariableProfile('GEN24.Wh', VARIABLE::TYPE_INTEGER );
            //IPS_SetVariableProfileDigits('GEN24.Wh', 3 );
            IPS_SetVariableProfileText('GEN24.Wh', "", " Wh" );
            //IPS_SetVariableProfileValues('GEN24.Wh', 0, 0, 0);
        } 		

        //active energy == Wirkarbeit
        if ( !IPS_VariableProfileExists('GEN24.kWh') ) {
            IPS_CreateVariableProfile('GEN24.kWh', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.kWh', 3 );
            IPS_SetVariableProfileText('GEN24.kWh', "", " kWh" );
            //IPS_SetVariableProfileValues('GEN24.kWh', 0, 0, 0);
        } 
        
        //reactive energy == Blindarbeit
        if ( !IPS_VariableProfileExists('GEN24.kvarh') ) {
            IPS_CreateVariableProfile('GEN24.kvarh', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.kvarh', 3 );
            IPS_SetVariableProfileText('GEN24.kvarh', "", " kvarh" );
            //IPS_SetVariableProfileValues('GEN24.kvarh', 0, 0, 0);
        } 			
        
        // power factor = Leistungsfaktor
        if ( !IPS_VariableProfileExists('GEN24.PowerFactor') ) {
            IPS_CreateVariableProfile('GEN24.PowerFactor', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.PowerFactor', 2 );
            //IPS_SetVariableProfileText('GEN24.PowerFactor', "", " " );
            //IPS_SetVariableProfileValues('GEN24.PowerFactor', 0, 0, 0);
        } 			

        //active power = Wirkleistung
        if ( !IPS_VariableProfileExists('GEN24.PowerActive') ) {
            IPS_CreateVariableProfile('GEN24.PowerActive', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.PowerActive', 1 );
            IPS_SetVariableProfileText('GEN24.PowerActive', "", " W" );
            //IPS_SetVariableProfileValues('GEN24.PowerActive', 0, 0, 0);
        } 

        //apparent power = Scheinleistung
        if ( !IPS_VariableProfileExists('GEN24.PowerApparent') ) {
            IPS_CreateVariableProfile('GEN24.PowerApparent', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.PowerApparent', 1 );
            IPS_SetVariableProfileText('GEN24.PowerApparent', "", " VA" );
            //IPS_SetVariableProfileValues('GEN24.PowerApparent', 0, 0, 0);
        } 

        //reactive power = Blindleistung
        if ( !IPS_VariableProfileExists('GEN24.PowerReactive') ) {
            IPS_CreateVariableProfile('GEN24.PowerReactive', VARIABLE::TYPE_FLOAT );
            IPS_SetVariableProfileDigits('GEN24.PowerReactive', 1 );
            IPS_SetVariableProfileText('GEN24.PowerReactive', "", " VAr" );
            //IPS_SetVariableProfileValues('GEN24.PowerReactive', 0, 0, 0);
        } 
          
        if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variable Profiles registered", 0); }
    }


}