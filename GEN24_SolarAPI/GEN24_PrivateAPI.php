<?

trait GEN24_PrivateAPI {

    private $fronius_Ws2kWh = 1/(3600*1000);

    public function RequestPowerFlow() {
       
        $url = "http://" . $this->GEN24_IP . "/status/powerflow";

        $categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerFlow, $this->parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' found with ID '%s'", self::CATEGORY_NAME_PowerFlow, $categoryId), 0); }

            $jsonData = $this->RequestJsonData($url);

            if(isset($jsonData->site)) {

                $parmArr = [];
                $parmArr["BackupMode"]              = array("varType" => 0, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["BatteryStandby"]          = array("varType" => 0, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["E_Day"]                   = array("varType" => 2, "multiplikator" => 1,     	"round" => 3, "profileName" => "");
                $parmArr["E_Total"]                 = array("varType" => 2, "multiplikator" => 0.001,   "round" => 3, "profileName" => "GEN24.kWh");
                $parmArr["E_Year"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "");
                $parmArr["MLoc"]                    = array("varType" => 1, "multiplikator" => 1,   	"round" => 0, "profileName" => "");
                $parmArr["Mode"]                    = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
                $parmArr["P_Akku"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["P_Grid"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["P_Akku"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["P_Load"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["P_PV"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["rel_Autonomy"]            = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "GEN24.Prozent");
                $parmArr["rel_SelfConsumption"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "GEN24.Prozent");
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
                    if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
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
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->site' not found in '%s'", self::CATEGORY_NAME_PowerFlow), 0); }
            }


            if(isset($jsonData->inverters)) {

                $parmArr = [];
                $parmArr["BatMode"]     = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["CID"]         = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["DT"]          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["E_Total"]     = array("varType" => 2, "multiplikator" => 0.001,   "round" => 3, "profileName" => "GEN24.kWh");
                $parmArr["ID"]          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
                $parmArr["P"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["SOC"]         = array("varType" => 2, "multiplikator" => 1, 	    "round" => 1, "profileName" => "GEN24.Prozent");
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
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
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
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->inverters' not found in '%s'", self::CATEGORY_NAME_PowerFlow), 0); }
            }

            if(isset($jsonData->SecondaryMeters)) {

                $parmArr = [];
                $parmArr["Category"]    = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["Label"]       = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["MLoc"]        = array("varType" => 1, "multiplikator" => 1, 	    "round" => 2, "profileName" => "");
                $parmArr["P"]           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
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
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
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
                if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("jsonData->SecondaryMeters' not found in '%s'", self::CATEGORY_NAME_PowerFlow), 0); }
            }


            if(isset($jsonData->Smartloads->Ohmpilots)) {

                $parmArr = [];
                $parmArr["P_AC_Total"]      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
                $parmArr["State"]           = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
                $parmArr["Temperature"]     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Temp");
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
                        if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
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
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("jsonData->Smartloads->Ohmpilots' not found in '%s'", self::CATEGORY_NAME_PowerFlow), 0); }
            }
           
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' not found", self::CATEGORY_NAME_PowerFlow), 0); }
        }

    }

    protected function RequesPowerMeters() {
       
        $url = "http://" . $this->GEN24_IP . "/components/PowerMeter/readable";
        $categoryObjId = $this->GetCategoryObjId(self::CATEGORY_NAME_PowerMeters);
        if($categoryObjId !== false) {


            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                      = array("varType" => 1, "multiplikator" => 1,   	"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                     = array("varType" => 1, "multiplikator" => 1,   	"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                       = array("varType" => 1, "multiplikator" => 1,   	"round" => 0, "profileName" => "~UnixTimestamp");

            $parmArr["SMARTMETER_CURRENT_01_F64"]                       = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Current");
            $parmArr["SMARTMETER_CURRENT_02_F64"]                       = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Current");
            $parmArr["SMARTMETER_CURRENT_03_F64"]                       = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "GEN24.Current");
            $parmArr["SMARTMETER_CURRENT_AC_SUM_NOW_F64"]               = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "GEN24.Current");

            $parmArr["SMARTMETER_ENERGYACTIVE_ABSOLUT_MINUS_F64"]       = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kWh");
            $parmArr["SMARTMETER_ENERGYACTIVE_ABSOLUT_PLUS_F64"]        = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kWh");
            $parmArr["SMARTMETER_ENERGYACTIVE_CONSUMED_SUM_F64"]        = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kWh");
            $parmArr["SMARTMETER_ENERGYACTIVE_PRODUCED_SUM_F64"]        = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kWh");   

            $parmArr["SMARTMETER_ENERGYREACTIVE_CONSUMED_SUM_F64"]      = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kvarh");
            $parmArr["SMARTMETER_ENERGYREACTIVE_PRODUCED_SUM_F64"]      = array("varType" => 2, "multiplikator" => 0.001,   "round" => 2, "profileName" => "GEN24.kvarh");  

            $parmArr["SMARTMETER_FACTOR_POWER_01_F64"]                  = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "");  
            $parmArr["SMARTMETER_FACTOR_POWER_02_F64"]                  = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "");
            $parmArr["SMARTMETER_FACTOR_POWER_03_F64"]                  = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "");
            $parmArr["SMARTMETER_FACTOR_POWER_SUM_F64"]                 = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "");

            $parmArr["SMARTMETER_FREQUENCY_MEAN_F64"]                   = array("varType" => 2, "multiplikator" => 1,   	"round" => 3, "profileName" => "GEN24.Frequency");

            $parmArr["SMARTMETER_POWERACTIVE_01_F64"]                  = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");  
            $parmArr["SMARTMETER_POWERACTIVE_02_F64"]                  = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["SMARTMETER_POWERACTIVE_03_F64"]                  = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["SMARTMETER_POWERACTIVE_MEAN_01_F64"]             = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");  
            $parmArr["SMARTMETER_POWERACTIVE_MEAN_02_F64"]             = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["SMARTMETER_POWERACTIVE_MEAN_03_F64"]             = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["SMARTMETER_POWERACTIVE_MEAN_SUM_F64"]            = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Power.2");    

            $parmArr["SMARTMETER_POWERAPPARENT_01_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["SMARTMETER_POWERAPPARENT_02_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["SMARTMETER_POWERAPPARENT_03_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["SMARTMETER_POWERAPPARENT_MEAN_01_F64"]           = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");  
            $parmArr["SMARTMETER_POWERAPPARENT_MEAN_02_F64"]           = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["SMARTMETER_POWERAPPARENT_MEAN_03_F64"]           = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["SMARTMETER_POWERAPPARENT_MEAN_SUM_F64"]          = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerApparent");  

            $parmArr["SMARTMETER_POWERREACTIVE_01_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerReactive");  
            $parmArr["SMARTMETER_POWERREACTIVE_02_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerReactive");
            $parmArr["SMARTMETER_POWERREACTIVE_03_F64"]                = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerReactive");
            $parmArr["SMARTMETER_POWERREACTIVE_MEAN_SUM_F64"]          = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.PowerReactive");      

            $parmArr["SMARTMETER_VALUE_LOCATION_U16"]                  = array("varType" => 1, "multiplikator" => 1,        "round" => 0, "profileName" => "");    

            $parmArr["SMARTMETER_VOLTAGE_01_F64"]                      = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");                   
            $parmArr["SMARTMETER_VOLTAGE_02_F64"]                      = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_03_F64"]                      = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_01_F64"]                 = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_02_F64"]                 = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_03_F64"]                 = array("varType" => 2, "multiplikator" => 1,        "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_12_F64"]                 = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_23_F64"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["SMARTMETER_VOLTAGE_MEAN_31_F64"]                 = array("varType" => 2, "multiplikator" => 1,   	    "round" => 2, "profileName" => "GEN24.Voltage");            
            $parmArr["unknown"]                                        = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);
            $this->ExtractComponentValues("PowerMeter", $jsonData, $categoryObjId, self::CATEGORY_NAME_PowerMeters, $parmArr);
        }
    }

    protected function RequestBatteryManagementSystem() {
       
        $url = "http://" . $this->GEN24_IP . "/components/BatteryManagementSystem/readable";
        $categoryObjId = $this->GetCategoryObjId(self::CATEGORY_NAME_BatteryManagementSystem);
        if($categoryObjId !== false) {

            $parmArr["BAT_CURRENT_DC_F64"]                              = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "GEN24.Current");
            $parmArr["BAT_CURRENT_DC_INTERNAL_F64"]                     = array("varType" => 2, "multiplikator" => 1,     	"round" => 2, "profileName" => "GEN24.Current");
            
            $parmArr["BAT_ENERGYACTIVE_ESTIMATION_MAX_CAPACITY_F64"]    = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh, 	"round" => 3, "profileName" => "GEN24.kWh");    // vor v1.21.6-1: BAT_CAPACITY_ESTIMATION_MAX_F64
            $parmArr["BAT_ENERGYACTIVE_LIFETIME_CHARGED_F64"]           = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh, 	"round" => 3, "profileName" => "GEN24.kWh");
            $parmArr["BAT_ENERGYACTIVE_LIFETIME_DISCHARGED_F64"]        = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh, 	"round" => 3, "profileName" => "GEN24.kWh");
            $parmArr["BAT_ENERGYACTIVE_MAX_CAPACITY_F64"]               = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh, 	"round" => 3, "profileName" => "GEN24.kWh");    // vor v1.21.6-1:BAT_CAPACITY_ESTIMATION_REMAINING_F64

            $parmArr["BAT_MODE_CELL_STATE_U16"]                         = array("varType" => 1, "multiplikator" => 1,     	"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_HYBRID_OPERATING_STATE_U16"]             = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_LAST_FAULT_PARAMETER_U16"]               = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_STATE_U16"]                              = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_U16"]                                    = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_MODE_WAKE_ENABLE_STATUS_U16"]                 = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_TEMPERATURE_CELL_F64"]                        = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Temp");
            $parmArr["BAT_TEMPERATURE_CELL_MAX_F64"]                    = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Temp");
            $parmArr["BAT_TEMPERATURE_CELL_MIN_F64"]                    = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Temp");
            $parmArr["BAT_VALUE_STATE_OF_CHARGE_RELATIVE_U16"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Prozent");
            $parmArr["BAT_VALUE_STATE_OF_HEALTH_RELATIVE_U16"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Prozent");
            $parmArr["BAT_VALUE_WARNING_CODE_U16"]                      = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["BAT_VOLTAGE_DC_INTERNAL_F64"]                     = array("varType" => 2, "multiplikator" => 1, 		"round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                      = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                     = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                       = array("varType" => 1, "multiplikator" => 1, 		"round" => 0, "profileName" => "~UnixTimestamp");
            $parmArr["DCLINK_POWERACTIVE_LIMIT_DISCHARGE_F64"]          = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.PowerActive");
            $parmArr["DCLINK_POWERACTIVE_MAX_F32"]                      = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.PowerActive");
            $parmArr["DCLINK_VOLTAGE_MEAN_F32"]                         = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Voltage");
            $parmArr["DEVICE_TEMPERATURE_AMBIENTEMEAN_F32"]             = array("varType" => 2, "multiplikator" => 1, 		"round" => 1, "profileName" => "GEN24.Temp");
            $parmArr["unknown"]                                         = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);
            $this->ExtractComponentValues("Battery", $jsonData, $categoryObjId, self::CATEGORY_NAME_BatteryManagementSystem, $parmArr);
        }
    }

    protected function RequestOhmpilot() {
       
        $url = "http://" . $this->GEN24_IP . "/components/Ohmpilot/readable";
        $categoryObjId = $this->GetCategoryObjId(self::CATEGORY_NAME_Ohmpilot);
        if($categoryObjId !== false) {

            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                      = array("varType" => 1, "multiplikator" => 1,       "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                     = array("varType" => 1, "multiplikator" => 1,       "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                       = array("varType" => 1, "multiplikator" => 1,       "round" => 0, "profileName" => "~UnixTimestamp");
            $parmArr["OHMPILOT_ENERGYACTIVE_CONSUMED_SUM_F64"]          = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,	"round" => 3, "profileName" => "GEN24.kWh");    // vor v1.21.6-1: SMARTMETER_ENERGYACTIVE_CONSUMED_SUM_F64
            $parmArr["OHMPILOT_MODE_CODE_OF_STATE_F64"]                 = array("varType" => 1, "multiplikator" => 1,       "round" => 0, "profileName" => "");                             // vor v1.21.6-1: INVERTER_MODE_PSP_DEVICE_CTRL_STATE_U16
            $parmArr["OHMPILOT_POWERACTIVE_DESIRED_F64"]                = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "GEN24.PowerActive");
            $parmArr["OHMPILOT_POWERACTIVE_SUM_MEAN_F32"]               = array("varType" => 2, "multiplikator" => 1,       "round" => 2, "profileName" => "GEN24.PowerActive");            // vor v1.21.6-1: ACBRIDGE_POWERACTIVE_SUM_MEAN_F32
            $parmArr["OHMPILOT_TEMPERATURE_CHANNEL_01_MEAN_F64"]        = array("varType" => 2, "multiplikator" => 1,   	"round" => 1, "profileName" => "GEN24.Temp");                   // vor v1.21.6-1: LEGACY_TEMPERATURE_MEAN_00_F64
            $parmArr["unknown"]                                         = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);
            $this->ExtractComponentValues("Ohmolilot", $jsonData, $categoryObjId, self::CATEGORY_NAME_Ohmpilot, $parmArr);
        }
    }

    protected function RequestDevices() {
       
        $url = "http://" . $this->GEN24_IP . "/status/devices";
        $categoryObjId = $this->GetCategoryObjId(self::CATEGROY_NAME_Devices);
        if($categoryObjId !== false) {

            $parmArr["id"]               = array("varType" => 3, "multiplikator" => 1, 	    "round" => null, "profileName" => "");
            $parmArr["status"]           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0,    "profileName" => "");
            $parmArr["statusMessage"]    = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
            $parmArr["type"]             = array("varType" => 3, "multiplikator" => 1,     	"round" => null, "profileName" => "");
            $parmArr["serial"]           = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");
            $parmArr["unknown"]          = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);
            $this->ExtractEntryValues("Devices", $jsonData, $categoryObjId, self::CATEGROY_NAME_Devices, $parmArr);
        }               
    }    

    protected function RequestCache() {
       
        $url = "http://" . $this->GEN24_IP . "/components/cache/readable";
        $categoryObjId = $this->GetCategoryObjId(self::CATEGROY_NAME_Cache);
        if($categoryObjId !== false) {

            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_01_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Current");
            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_02_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Current");
            $parmArr["ACBRIDGE_CURRENT_ACTIVE_MEAN_03_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Current");

            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_01_U64"]     = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");
            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_02_U64"]     = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");
            $parmArr["ACBRIDGE_ENERGYACTIVE_ACTIVECONSUMED_SUM_03_U64"]     = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");

            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_01_U64"]           = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");
            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_02_U64"]           = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");
            $parmArr["ACBRIDGE_ENERGYACTIVE_PRODUCED_SUM_03_U64"]           = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => null, "profileName" => "GEN24.kWh");

            $parmArr["ACBRIDGE_FREQUENCY_MEAN_F32"]                         = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Frequency");

            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_01_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_02_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_MEAN_03_F32"]                    = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["ACBRIDGE_POWERACTIVE_SUM_MEAN_F32"]                   = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");

            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_01_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_02_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_MEAN_03_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerApparent");
            $parmArr["ACBRIDGE_POWERAPPARENT_SUM_MEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerApparent");

            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_01_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_02_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_MEAN_03_F32"]                  = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerReactive");
            $parmArr["ACBRIDGE_POWERREACTIVE_SUM_MEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.PowerReactive");

            $parmArr["ACBRIDGE_TIME_BACKUPMODE_UPTIME_SUM_F32"]             = array("varType" => 1, "multiplikator" => 1, 	    "round" => 3, "profileName" => "~UnixTimestampTime");

            $parmArr["ACBRIDGE_VOLTAGE_MEAN_01_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_02_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_03_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_12_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_23_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["ACBRIDGE_VOLTAGE_MEAN_31_F32"]                        = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");

            $parmArr["BAT_CURRENT_MEAN_F32"]                                = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Current");
            $parmArr["BAT_ENERGYACTIVE_ACTIVECHARGE_SUM_01_U64"]            = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => 3, "profileName" => "GEN24.kWh");
            $parmArr["BAT_ENERGYACTIVE_ACTIVEDISCHARGE_SUM_01_U64"]         = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => 3, "profileName" => "GEN24.kWh");
            $parmArr["BAT_MODE_ENFORCED_U16"]                               = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["BAT_POWERACTIVE_MEAN_F32"]                            = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Power.2");
            $parmArr["BAT_VOLTAGE_OUTER_MEAN_01_F32"]                       = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");

            $parmArr["COMPONENTS_MODE_ENABLE_U16"]                          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_MODE_VISIBLE_U16"]                         = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["COMPONENTS_TIME_STAMP_U64"]                           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "~UnixTimestamp");

            $parmArr["DCLINK_VOLTAGE_MEAN_F32"]                             = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["DEVICE_MODE_OPERATING_REFERRAL_U16"]                  = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["DEVICE_TEMPERATURE_AMBIENTEMEAN_F32"]                 = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Temp");
            $parmArr["DEVICE_TIME_UPTIME_SUM_F32"]                          = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");
            $parmArr["DEVICE_VOLTAGE_SELV_F32"]                             = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");

            $parmArr["FANCONTROL_PERCENT_01_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Prozent");
            $parmArr["FANCONTROL_PERCENT_02_F32"]                           = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Prozent");

            $parmArr["FEEDINPOINT_FREQUENCY_MEAN_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Frequency");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_01_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_02_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_03_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_12_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_23_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");  
            $parmArr["FEEDINPOINT_VOLTAGE_MEAN_31_F32"]                     = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");

            $parmArr["INVERTER_VALUE_SYNCHRONISATION_BITMAP_U16"]           = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");  
            $parmArr["LEGACY_MODE_BACKUP_OPERATION_SYNC_DM_SYSTEMS_U16"]    = array("varType" => 1, "multiplikator" => 1, 	    "round" => 0, "profileName" => "");

            $parmArr["MODULE_TEMPERATURE_MEAN_01_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Temp");  
            $parmArr["MODULE_TEMPERATURE_MEAN_03_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Temp");
            $parmArr["MODULE_TEMPERATURE_MEAN_04_F32"]                      = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Temp");  
            
            $parmArr["PV_CURRENT_MEAN_01_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Current");
            $parmArr["PV_CURRENT_MEAN_02_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 3, "profileName" => "GEN24.Current");  
            $parmArr["PV_ENERGYACTIVE_ACTIVE_SUM_01_U64"]                   = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,    "round" => 3, "profileName" => "GEN24.kWh");
            $parmArr["PV_ENERGYACTIVE_ACTIVE_SUM_02_U64"]                   = array("varType" => 2, "multiplikator" => $this->fronius_Ws2kWh,   "round" => 3, "profileName" => "GEN24.kWh");  
            $parmArr["PV_POWERACTIVE_MEAN_01_F32"]                          = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");
            $parmArr["PV_POWERACTIVE_MEAN_02_F32"]                          = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Power.2");  
            $parmArr["PV_VOLTAGE_MEAN_01_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");
            $parmArr["PV_VOLTAGE_MEAN_02_F32"]                              = array("varType" => 2, "multiplikator" => 1, 	    "round" => 2, "profileName" => "GEN24.Voltage");  
            $parmArr["unknown"]                                             = array("varType" => 3, "multiplikator" => 1, 		"round" => null, "profileName" => "");

            $jsonData = $this->RequestJsonData($url);
            $this->ExtractComponentValues("Cache", $jsonData, $categoryObjId, self::CATEGROY_NAME_Cache, $parmArr);
        }

    }


    protected function GetCategoryObjId($categoryName) {
        $categoryId = @IPS_GetObjectIDByIdent($categoryName, $this->parentRootId);
        if($categoryId !== false) {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("IPS-Category '%s' found with ID '%s'", $categoryName, $categoryId), 0); }
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("IPS-Category '%s' not found", $categoryName), 0); }
        }
        return $categoryId;
    }

    protected function ExtractComponentValues($instanzBaseName, $jsonData, $categoryObjId, $categoryName, $parmArr) {

        if(isset($jsonData->Body->Data)) {
            $jsonBodyData = $jsonData->Body->Data;

            $cnt = 0;
            foreach($jsonBodyData as $key => $jsonsComponentData) {
                $cnt++;
                $instanceIdent = sprintf("%s%s", $instanzBaseName, $key);
                $instanceName = sprintf("%s [%s]", $instanzBaseName, $key);                
            
                $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryObjId);
                if($instanzId === false) {
                    $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                    IPS_SetIdent($instanzId, $instanceIdent);
                    IPS_SetName($instanzId, $instanceName);
                    IPS_SetParent($instanzId,  $categoryObjId);
                    IPS_SetPosition($instanzId, $cnt*10);
                    if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
                }

                foreach($jsonsComponentData->channels as $key => $value) {
                    if(substr($key, 0, 1) == "<") {
                        if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Key '%s' with Value '%s' form '%s' is not extracted", $key, $value, $categoryName), 0); }
                    } else {
                        if(array_key_exists($key, $parmArr)) {
                            $paramArrElem = $parmArr[$key];
                        } else {
                            $key = "_" . $key ;
                            //$paramArrElem = $parmArr["unknown"];
                            $paramArrElem = array("varType" => 3, "multiplikator" => 1, "round" => null, "profileName" => "");
                        }
                        $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                    }
                }
            }               

        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("'jsonData->Body->Data' not found in '%s'", $categoryName), 0); }
        }
        
    }    


    protected function ExtractEntryValues($instanzBaseName, $jsonData, $categoryObjId, $categoryName, $parmArr) {

        $cnt = 0;
        foreach($jsonData as $key => $jsonDataEntry) {

            $id = $cnt;
            if(isset($jsonDataEntry->id)) {    
                $id = $jsonDataEntry->id;
            }

            $instanceIdent = sprintf("Entry%s", $id);
            $instanceName = sprintf("ID :: %s", $id);          
        
            $instanzId = @IPS_GetObjectIDByIdent($instanceIdent, $categoryObjId);
            if($instanzId === false) {
                $instanzId = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                IPS_SetIdent($instanzId, $instanceIdent);
                IPS_SetName($instanzId, $instanceName);
                IPS_SetParent($instanzId,  $categoryObjId);
                IPS_SetPosition($instanzId, $cnt*10);
                if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanceName, $instanzId), 0); }
            }

            foreach($jsonDataEntry as $key => $value) {
                if(substr($key, 0, 1) == "<") {
                    if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Key '%s' with Value '%s' form '%s' is not  extracted", $key, $value, $categoryName), 0); }
                } else {
                    if(array_key_exists($key, $parmArr)) {
                        $paramArrElem = $parmArr[$key];
                    } else {
                        $key = "_" . $key ;
                        //$paramArrElem = $parmArr["unknown"];
                        $paramArrElem = array("varType" => 3, "multiplikator" => 1, "round" => null, "profileName" => "");
                    }
                    $this->SaveyPropertyValue($instanzId, $key, $value, $paramArrElem);
                }
            }
            $cnt++;
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
                    if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR setting Profile '%s' to varID %s", $profileName, $varId), 0); }
                }
            }
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $key), 0); }
        } else {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $key, $value), 0); }
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
                $varProfileName = "GEN24.Current";  
            } else if(!strpos($key, "_VOLTAGE") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.Voltage";  
            } else if(!strpos($key, "_MODE") === false) {
                $varType = 1; 
                $varProfileName = "";  
            } else if(!strpos($key, "TIME_STAMP") === false) {
                $varType = 1; 
                $varProfileName = "~UnixTimestamp";  
            } else if(!strpos($key, "_FREQUENCY") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.Frequency";  
            } else if(!strpos($key, "_ENERGY") === false) {
                $varType = 1; 
                $varProfileName = "GEN24.Wh";  
            } else if(!strpos($key, "_FACTOR_POWER") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.PowerFactor";  
            } else if(!strpos($key, "_POWERACTIVE") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.PowerActive";  
            } else if(!strpos($key, "_POWERAPPARENT") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.PowerApparent";  
            } else if(!strpos($key, "_POWERREACTIVE") === false) {
                $varType = 2; 
                $varProfileName = "GEN24.PowerReactive";  
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
                    if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR setting Profile '%s' to varID %s", $varProfileName, $varId), 0); }
                }
            }
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Variable '%s' created for Property '%s'", $varId, $key), 0); }
        } else {
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Found Variable '%s' for Property '%s' [Raw Value = %s]", $varId, $key, $value), 0); }
        }
        SetValue($varId, round($value, 3));
    
    }		

}

?>