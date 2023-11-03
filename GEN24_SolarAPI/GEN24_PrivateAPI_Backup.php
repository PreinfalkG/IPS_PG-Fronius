<?

trait GEN24_PrivateAPI {


    public function RequestPowerFlow() {
       
        $url = "http://" . $this->GEN24_IP . "/status/powerflow";

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerFlow, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGORY_NAME_PowerFlow, $categoryId)); }

            $jsonData = $this->RequestJsonData($url);

            if(isset($jsonData->site)) {

                $parmArr = [];
                $parmArr["BackupMode"]              = array("varType" => 0, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["BatteryStandby"]          = array("varType" => 0, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["E_Day"]                   = array("varType" => 2, "multiplikator" => 1,     	"round" => 3, "profileName" => "");
                $parmArr["E_Total"]                 = array("varType" => 2, "multiplikator" => 1,     	"round" => 3, "profileName" => "");
                $parmArr["E_Year"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "");
                $parmArr["MLoc"]                    = array("varType" => 1, "multiplikator" => 1,   	"round" => 0, "profileName" => "");
                $parmArr["Mode"]                    = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
                $parmArr["P_Akku"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["P_Grid"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["P_Akku"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["P_Load"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["P_PV"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["rel_Autonomy"]            = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "Fronius.Prozent");
                $parmArr["rel_SelfConsumption"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "Fronius.Prozent");
                $parmArr["unknown"]                 = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

                $site = $jsonData->site;
                $instanceIdent = "Site";
                $instanceName = "Site";                
            
                $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                if($instanzId === false) {
                    $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                    IPS_SetIdent($instanzId, $instanceIdent);
                    IPS_SetName($instanzId, $instanceName);
                    IPS_SetParent($instanzId,  $categoryId);
                    IPS_SetPosition($instanzId, 10);
                    if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                }

                foreach($site as $key => $value) {
                    if(array_key_exists($key, $parmArr)) {
                        $paramArrElem = $parmArr[$key];
                    } else {
                        $key = "_" . $key ;
                        $paramArrElem = $parmArr["unknown"];
                    }
                    $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                }
            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->site' not found in '%s'", self::CATEGORY_NAME_PowerFlow)); }
            }


            if(isset($jsonData->inverters)) {

                $parmArr = [];
                $parmArr["BatMode"]     = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["CID"]         = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["DT"]          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["ID"]          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["P"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["SOC"]         = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "Fronius.Prozent");
                $parmArr["unknown"]     = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

                $inverters = $jsonData->inverters;
                $cnt = 0;
                foreach($inverters as $key => $inverter) {
                    $cnt++;
                    $instanceIdent = sprintf("Inverter%s", $key);
                    $instanceName = sprintf("Inverter [%s]", $key);                
                
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 20 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }
  
                    foreach($inverter as $key => $value) {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            $paramArrElem = $parmArr["unknown"];
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    }
                }
            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->inverters' not found in '%s'", self::CATEGORY_NAME_PowerFlow)); }
            }

            if(isset($jsonData->SecondaryMeters)) {

                $parmArr = [];
                $parmArr["Category"]    = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["Label"]       = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["MLoc"]        = array("varType" => 1, "multiplikator" => 1, 	    "round" => 2, "profileName" => "");
                $parmArr["P"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["unknown"]     = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

                $SecondaryMeters = $jsonData->SecondaryMeters;
                $cnt = 0;
                foreach($SecondaryMeters as $key => $SecondaryMeter) {
                    $cnt++;
                    $instanceIdent = sprintf("SecondaryMeter%s", $key);
                    $instanceName = sprintf("SecondaryMeter [%s]", $key);
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 30 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($SecondaryMeter as $key => $value) {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            $paramArrElem = $parmArr["unknown"];
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    }   
                }
            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->SecondaryMeters' not found in '%s'", self::CATEGORY_NAME_PowerFlow)); }
            }


            if(isset($jsonData->Smartloads->Ohmpilots)) {

                $parmArr = [];
                $parmArr["P_AC_Total"]      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
                $parmArr["State"]           = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["Temperature"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Temp");
                $parmArr["unknown"]         = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

                $Ohmpilots = $jsonData->Smartloads->Ohmpilots;
                $cnt = 0;
                foreach($Ohmpilots as $key => $Ohmpilot) {
                    $cnt++;
                    $instanceIdent = sprintf("Ohmpilot%s", $key);
                    $instanceName = sprintf("Ohmpilot [%s]", $key);
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 40 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($Ohmpilot as $key => $value) {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            $paramArrElem = $parmArr["unknown"];
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    } 
                }
            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Smartloads->Ohmpilots' not found in '%s'", self::CATEGORY_NAME_PowerFlow)); }
            }
           
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGORY_NAME_PowerFlow)); }
        }

    }

    protected function RequesPowerMeters() {
       
        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerMeters, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGORY_NAME_PowerMeters, $categoryId)); }

            $url = "http://" . $this->GEN24_IP . "/components/PowerMeter/readable";
            $jsonData = $this->RequestJsonData($url);
            if(isset($jsonData->Body->Data)) {
                $meters = $jsonData->Body->Data;
                $cnt = 0;
                foreach($meters as $key => $meters) {
                    $cnt++;
                    $instanceIdent = sprintf("PowerMeter%s", $key);
                    $instanceName = sprintf("PowerMeter [%s]", $key);                
                
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 10 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($meters->channels as $key => $value) {
                        $this->SavePowerMeterPropertyValue($instanzId, $key, $value);
                    }
                }               
            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Body->Data' not found in '%s'", self::CATEGORY_NAME_PowerMeters)); }
            }
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGORY_NAME_PowerMeters)); }
        }
    }

    public function RequestBatteryManagementSystem() {
       
        $url = "http://" . $this->GEN24_IP . "/components/BatteryManagementSystem/readable";

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_BatteryManagementSystem, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGORY_NAME_BatteryManagementSystem, $categoryId)); }

            $parmArr["BAT_CAPACITY_ESTIMATION_MAX_F64"]                 = array("varType" => 2, "multiplikator" => 0.001, 	"round" => 3, "profileName" => "Fronius.kWh");
            $parmArr["BAT_CAPACITY_ESTIMATION_REMAINING_F64"]           = array("varType" => 2, "multiplikator" => 0.001, 	"round" => 3, "profileName" => "Fronius.kWh");
            $parmArr["BAT_CURRENT_DC_F64"]                              = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "Fronius.Current");
            $parmArr["BAT_CURRENT_DC_INTERNAL_F64"]                     = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "Fronius.Current");
            $parmArr["BAT_ENERGYACTIVE_LIFETIME_CHARGED_F64"]           = array("varType" => 2, "multiplikator" => 0.001, 	"round" => 3, "profileName" => "Fronius.kWh");
            $parmArr["BAT_ENERGYACTIVE_LIFETIME_DISCHARGED_F64"]        = array("varType" => 2, "multiplikator" => 0.001, 	"round" => 3, "profileName" => "Fronius.kWh");
            $parmArr["BAT_MODE_CELL_STATE_U16"]                         = array("varType" => 1, "multiplikator" => 1,     	"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_CELL_STATE_U16"]                         = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_HYBRID_OPERATING_STATE_U16"]             = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_LAST_FAULT_PARAMETER_U16"]               = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_STATE_U16"]                              = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_U16"]                                    = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_WAKE_ENABLE_STATUS_U16"]                 = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_TEMPERATURE_CELL_F64"]                        = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Temp");
            $parmArr["BAT_TEMPERATURE_CELL_MAX_F64"]                    = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Temp");
            $parmArr["BAT_TEMPERATURE_CELL_MIN_F64"]                    = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Temp");
            $parmArr["BAT_VALUE_STATE_OF_CHARGE_RELATIVE_U16"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Prozent");
            $parmArr["BAT_VALUE_STATE_OF_HEALTH_RELATIVE_U16"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Prozent");
            $parmArr["BAT_VALUE_WARNING_CODE_U16"]                      = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_VOLTAGE_DC_INTERNAL_F64"]                     = array("varType" => 2, "multiplikator" => 1, 		"round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                      = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                     = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                       = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "~UnixTimestamp");
            $parmArr["DCLINK_POWERACTIVE_LIMIT_DISCHARGE_F64"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.PowerActive");
            $parmArr["DCLINK_POWERACTIVE_MAX_F32"]                      = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.PowerActive");
            $parmArr["DCLINK_VOLTAGE_MEAN_F32"]                         = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Voltage");
            $parmArr["DEVICE_TEMPERATURE_AMBIENTEMEAN_F32"]             = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "Fronius.Temp");
            $parmArr["unknown"]                                         = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);

            if(isset($jsonData->Body->Data)) {
                $batteries = $jsonData->Body->Data;

                $cnt = 0;
                foreach($batteries as $key => $meters) {
                    $cnt++;
                    $instanceIdent = sprintf("Battery%s", $key);
                    $instanceName = sprintf("Battery [%s]", $key);                
                
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 10 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($meters->channels as $key => $value) {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            $paramArrElem = $parmArr["unknown"];
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    }
                }               

            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Body->Data' not found in '%s'", self::CATEGORY_NAME_PowerMeters)); }
            }

        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGORY_NAME_PowerMeters)); }
        }

    }

    public function RequestOhmpilot() {
       
        $url = "http://" . $this->GEN24_IP . "/components/Ohmpilot/readable";

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_Ohmpilot, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGORY_NAME_Ohmpilot, $categoryId)); }

            $parmArr["ACBRIDGE_POWERACTIVE_SUM_MEAN_F32"]               = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerActive");
            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                      = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                     = array("varType" => 1, "multiplikator" => 1,     	"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                       = array("varType" => 1, "multiplikator" => 1,     	"round" => 0, "profileName" => "~UnixTimestamp");
            $parmArr["INVERTER_MODE_PSP_DEVICE_CTRL_STATE_U16"]         = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["LEGACY_TEMPERATURE_MEAN_00_F64"]                  = array("varType" => 2, "multiplikator" => 1,   	"round" => 1, "profileName" => "Fronius.Temp");
            $parmArr["OHMPILOT_POWERACTIVE_DESIRED_F64"]                = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "Fronius.PowerActive");
            $parmArr["SMARTMETER_ENERGYACTIVE_CONSUMED_SUM_F64"]        = array("varType" => 2, "multiplikator" => 0.001, 	"round" => 3, "profileName" => "Fronius.kWh");
            $parmArr["unknown"]                                         = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);

            if(isset($jsonData->Body->Data)) {
                $batteries = $jsonData->Body->Data;

                $cnt = 0;
                foreach($batteries as $key => $meters) {
                    $cnt++;
                    $instanceIdent = sprintf("Ohmpilot%s", $key);
                    $instanceName = sprintf("Ohmpilot [%s]", $key);                
                
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 10 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($meters->channels as $key => $value) {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            $paramArrElem = $parmArr["unknown"];
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    }
                }               

            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Body->Data' not found in '%s'", self::CATEGORY_NAME_Ohmpilot)); }
            }

        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGORY_NAME_Ohmpilot)); }
        }

    }

    public function RequestDevices() {
       
        $url = "http://" . $this->GEN24_IP . "/status/devices";

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGROY_NAME_Devices, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGROY_NAME_Devices, $categoryId)); }


            $parmArr["id"]               = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["status"]           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0,    "profileName" => "");
            $parmArr["statusMessage"]    = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
            $parmArr["type"]             = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
            $parmArr["serial"]           = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");
            $parmArr["unknown"]          = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonDevices = $this->RequestJsonData($url);

            $cnt = 0;
            foreach($jsonDevices as $device) {
    

                $deviceID = $cnt;
                if(isset($device->id)) {    
                    $deviceID = $device->id;
                }

                $instanceIdent = sprintf("Device%s", $deviceID);
                $instanceName = sprintf("DeviceID :: %s", $deviceID);   
             
            
                $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                if($instanzId === false) {
                    $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                    IPS_SetIdent($instanzId, $instanceIdent);
                    IPS_SetName($instanzId, $instanceName);
                    IPS_SetParent($instanzId,  $categoryId);
                    IPS_SetPosition($instanzId, 10 + $cnt);
                    if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                }

                foreach($device as $key => $value) {
                    if(array_key_exists($key, $parmArr)) {
                        $paramArrElem = $parmArr[$key];
                    } else {
                        $key = "_" . $key ;
                        $paramArrElem = $parmArr["unknown"];
                    }
                    $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                }
                $cnt++;
            }       
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGROY_NAME_Devices)); }
        }                    
    }    


    public function RequestCache() {
       
        $url = "http://" . $this->GEN24_IP . "/components/cache/readable";

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGROY_NAME_Cache, $parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGROY_NAME_Cache, $categoryId)); }

            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_01_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Current");
            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_02_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Current");
            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_03_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Current");
            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_01_U64"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_02_U64"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_03_U64"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_01_U64"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_02_U64"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_03_U64"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["ACBRIDGE_FREQUENCY_MEAN_F32"]                         = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Frequency");
            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_01_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_02_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_03_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_SUM_MEAN_F32"]                   = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_01_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_02_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_03_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_SUM_MEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerApparent");
            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_01_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_02_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_03_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_SUM_MEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.PowerReactive");
            $parmArr["ACBRIDGE_TIME_BACKUPMODE_UPTIME_SUM_F32"]             = array("varType" => 1, "multiplikator" => 1, 	    "round" => 3, "profileName" => "~UnixTimestampTime");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_01_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_02_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_03_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_12_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_23_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_31_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["BAT_CURRENT_MEAN_F32"]                                = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Current");
            $parmArr["BAT_ENERGYACTIVE_ACTIVECHARGE_SUM_01_U64"]            = array("varType" => 2, "multiplikator" => 1,       "round" => 3, "profileName" => "");
            $parmArr["BAT_ENERGYACTIVE_ACTIVEDISCHARGE_SUM_01_U64"]         = array("varType" => 2, "multiplikator" => 1,       "round" => 3, "profileName" => "");
            $parmArr["BAT_MODE_ENFORCED_U16"]                               = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["BAT_POWERACTIVE_MEAN_F32"]                            = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Power.2");
            $parmArr["BAT_VOLTAGE_OUTER_MEAN_01_F32"]                       = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                         = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "~UnixTimestamp");
            $parmArr["DCLINK_VOLTAGE_MEAN_F32"]                             = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["DEVICE_MODE_OPERATING_REFERRAL_U16"]                  = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["DEVICE_TEMPERATURE_AMBIENTEMEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Temp");
            $parmArr["DEVICE_TIME_UPTIME_SUM_F32"]                          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["FANCONTROL_PERCENT_01_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_02_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_03_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_04_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_05_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_06_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FANCONTROL_PERCENT_07_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Prozent");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_01_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_02_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_03_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_12_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_23_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");  
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_31_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["INVERTER_VALUE_SYNCHRONISATION_BITMAP_U16"]           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");  
            $parmArr["LEGACY_MODE_BACKUP_OPERATION_SYNC_DM_SYSTEMS_U16"]    = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["MODULE_TEMPERATURE_MEAN_01_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Temp");  
            $parmArr["MODULE_TEMPERATURE_MEAN_03_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Temp");
            $parmArr["MODULE_TEMPERATURE_MEAN_04_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Temp");  
            $parmArr["PV_CURRENT_MEAN_01_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Current");
            $parmArr["PV_CURRENT_MEAN_02_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "Fronius.Current");  
            $parmArr["PV_ENERGYACTIVE_ACTIVE_SUM_01_U64"]                   = array("varType" => 2, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["PV_ENERGYACTIVE_ACTIVE_SUM_02_U64"]                   = array("varType" => 2, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");  
            $parmArr["PV_POWERACTIVE_MEAN_01_F32"]                          = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");
            $parmArr["PV_POWERACTIVE_MEAN_02_F32"]                          = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Power.2");  
            $parmArr["PV_VOLTAGE_MEAN_01_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");
            $parmArr["PV_VOLTAGE_MEAN_02_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "Fronius.Voltage");  
            $parmArr["unknown"]                                             = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);

            if(isset($jsonData->Body->Data)) {
                $dataArr = $jsonData->Body->Data;

                $cnt = 0;
                foreach($dataArr as $key => $arr) {
                    $cnt++;
                    $instanceIdent = sprintf("Cache%s", $key);
                    $instanceName = sprintf("Cache [%s]", $key);                
                
                    $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryId);
                    if($instanzId === false) {
                        $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetIdent($instanzId, $instanceIdent);
                        IPS_SetName($instanzId, $instanceName);
                        IPS_SetParent($instanzId,  $categoryId);
                        IPS_SetPosition($instanzId, 10 + $cnt);
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId)); }
                    }

                    foreach($arr->channels as $key => $value) {
                        if(substr($key, 0, 1) == "<") {
                            if($this->logLevel >= LogLevel::TEST) { $this->AddLog(__FUNCTION__, sprintf("Key '%s' with Value '%s' wird nicht gespeichert", $key, $value)); }
                        } else {
                            if(array_key_exists($key, $parmArr)) {
                                $paramArrElem = $parmArr[$key];
                            } else {
                                $key = "_" . $key ;
                                $paramArrElem = $parmArr["unknown"];
                            }
                            $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                        }
                    }
                }               

            } else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Body->Data' not found in '%s'", self::CATEGROY_NAME_Cache)); }
            }

        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGROY_NAME_Cache)); }
        }

    }

    protected function SaveyPropertyValue($instanzId, $key, $value,  $paramArr) {

        $multiplikator =  $paramArr["multiplikator"];
        $round =  $paramArr["round"];

        $varId = @IPS_GetObjectIDByName($key, $instanzId);
        if ($varId === false) {

            $varType =  $paramArr["varType"];
            $profileName = $paramArr["profileName"];
           
            $varId = IPS_CreateVariable($varType ); //0 - Boolean | 1-Integer | 2 - Float | 3 - String
            IPS_SetName($varId, $key);
            IPS_SetParent($varId, $instanzId);
            if($profileName != "") { 
                $return = @IPS_SetVariableCustomProfile ($varId, $profileName); 
                if(!$return) { 
                    if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR setting Profile '%s' to varID %s", $profileName, $varId)); }
                }
            }
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $key)); }
        } else {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $key, $value)); }
        }
        if($multiplikator != 1) { $value = $value * $multiplikator; }
        if(!is_null($round)) { $value = round($value, $round); }
        SetValue($varId, $value);
    
    }	

    protected function SavePowerMeterPropertyValue($instanzId, $key, $value) {
	
        $varId = @IPS_GetObjectIDByName($key, $instanzId);
        if ($varId === false) {

            $varType = 3;    
            $varProfileName = "";  
            if(!strpos($key, "_CURRENT") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.Current";  
            } else if(!strpos($key, "_VOLTAGE") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.Voltage";  
            } else if(!strpos($key, "_MODE") === false) {
                $varType = 1; 
                $varProfileName = "";  
            } else if(!strpos($key, "TIME_STAMP") === false) {
                $varType = 1; 
                $varProfileName = "~UnixTimestamp";  
            } else if(!strpos($key, "_FREQUENCY") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.Frequency";  
            } else if(!strpos($key, "_ENERGY") === false) {
                $varType = 1; 
                $varProfileName = "Fronius.Wh";  
            } else if(!strpos($key, "_FACTOR_POWER") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.PowerFactor";  
            } else if(!strpos($key, "_POWERACTIVE") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.PowerActive";  
            } else if(!strpos($key, "_POWERAPPARENT") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.PowerApparent";  
            } else if(!strpos($key, "_POWERREACTIVE") === false) {
                $varType = 2; 
                $varProfileName = "Fronius.PowerReactive";  
            } else if(!strpos($key, "VALUE_LOCATION") === false) {
                $varType = 1; 
                $varProfileName = "";  
            } else {
                $varType = 3;    
                $varProfileName = "";  
                $key = $key . "_";
            }                                         
            
            $varId = IPS_CreateVariable($varType); //0 - Boolean | 1-Integer | 2 - Float | 3 - String
            IPS_SetName($varId, $key);
            IPS_SetParent($varId, $instanzId);
            if($varProfileName != "") { 
                $return = @IPS_SetVariableCustomProfile ($varId, $varProfileName); 
                if(!$return) { 
                    if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR setting Profile '%s' to varID %s", $varProfileName, $varId)); }
                }
            }
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $key)); }
        } else {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $key, $value)); }
        }
        SetValue($varId, round($value, 3));
    
    }		


    protected function SavePropertyValueOLD($json, $rootId, $variablenTyp, $propertyName, $varProfileName="", $enableLogging=false, $round=NULL, $multiplikator=1) {
	
		if(@property_exists($json, $propertyName)) 	{
			$value = $json->$propertyName;
			$varId = @IPS_GetObjectIDByName($propertyName, $rootId);
			if ($varId === false) {
				$varId = IPS_CreateVariable($variablenTyp); //0 - Boolean | 1-Integer | 2 - Float | 3 - String
				IPS_SetName($varId, $propertyName);
				IPS_SetParent($varId, $rootId);
				//IPS_SetPosition($varId, 10);
				if($varProfileName != "") { 
					$return = @IPS_SetVariableCustomProfile ($varId, $varProfileName); 
					if(!$return) { 
                        if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR setting Profile '%s' to varID %s", $varProfileName, $varId)); }
                    }
				}
				if($enableLogging) { 
                    $archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
					AC_SetLoggingStatus($archivInstanzID, $varId, true);
					IPS_ApplyChanges($archivInstanzID);
				}
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $propertyName)); }
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $propertyName, $value)); }
			}
			if($multiplikator != 1) { $value = $value * $multiplikator; }
			if(is_null($round)) {
				SetValue($varId, $value);
			} else {
				SetValue($varId, round($value, $round));
			}
		} else {

			if($propertyName == "RAW_DATA") {
				$value = json_encode($json);
				$varId = @IPS_GetObjectIDByName($propertyName, $rootId);
				if ($varId === false) {
					$varId = IPS_CreateVariable(3); //0 - Boolean | 1-Integer | 2 - Float | 3 - String
					IPS_SetName($varId, $propertyName);
					IPS_SetParent($varId, $rootId);
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $propertyName)); }
				} else {
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $propertyName, $value)); }
				}
				SetValue($varId, $value);
			} else {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Property '%s' not found in JSON", $propertyName)); }
			}
		}	
	}		


}

?>