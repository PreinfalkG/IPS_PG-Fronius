<?

// ArrayOffsets
if (!defined('IMR_START_REGISTER'))
{
	define("IMR_SIZE", 0);
	define("IMR_RW", 1);
	define("IMR_FUNCTION_CODE", 2);
	define("IMR_NAME", 3);
	define("IMR_TYPE", 4);
	define("IMR_UNITS", 5);
    define("IMR_SF", 6);
    define("IPS_VARTYPE", 7);
    define("IPS_ARCHIV", 8);
    define("IPS_VARPROFILE", 9);
    define("IPS_VARMULTIPLIER", 10);
    define("IPS_VARROUND", 11);
    define("IMR_DESCRIPTION_SHORT", 12);
    define("IMR_DESCRIPTION_LONG", 13);
}

// ModBus RTU TCP
if (!defined('MODBUS_INSTANCES')) {
	define("MODBUS_INSTANCES", "{A5F663AB-C400-4FE5-B207-4D67CC030564}");
}
if (!defined('CLIENT_SOCKETS')) {
	define("CLIENT_SOCKETS", "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
}
if (!defined('MODBUS_ADDRESSES')) {
	define("MODBUS_ADDRESSES", "{CB197E50-273D-4535-8C91-BB35273E3CA5}");
}



// Offset von Register (erster Wert 1) zu Adresse (erster Wert 0) ist -1
if (!defined('MODBUS_REGISTER_TO_ADDRESS_OFFSET'))
{
	define("MODBUS_REGISTER_TO_ADDRESS_OFFSET", -1);
}


trait GEN24_Modbus {

    protected function CreateInverterModel($key, $value) {

        $parentRootId = IPS_GetParent($this->InstanceID);
        $categoryId = @IPS_GetObjectIDByIdent($key, $parentRootId);
        if($categoryId === false) {
            $categoryId = IPS_CreateCategory();
            IPS_SetIdent($categoryId, $key);
            IPS_SetName($categoryId, $value);
            IPS_SetParent($categoryId,  $parentRootId);
            IPS_SetPosition($categoryId, preg_replace('~\D~', '', $key));
        }

        $gatewayId = $this->ReadPropertyInteger("si_ModebusGatewayID");
        $inverterRegisterConfig = $this->GetInverterRegisterConfig($key);
        $this->CreateModbusInstances($inverterRegisterConfig, $categoryId, $gatewayId, 0);
        $this->CreateIpsVariables($inverterRegisterConfig, $categoryId);

    }


    protected function CreateModbusInstances($configArr, $categoryRootId, $gatewayId, $pollCycle, $uniqueIdent = "") {

        $parentId = @IPS_GetObjectIDByIdent("ModbusDevices", $categoryRootId);
        if ($parentId == false) {
            $parentId = IPS_CreateCategory();
            IPS_SetParent($parentId, $categoryRootId);
            IPS_SetIdent($parentId, "ModbusDevices");
            IPS_SetName($parentId, "ModbusDevices");
        }           

        
        foreach ($configArr as $modbusStartAddress => $configEntry) {       //inverterRegisterConfig > configEntry

            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Create/Update Instance '%s - %s' [Gateway ID: %s]", $modbusStartAddress, $configEntry[IMR_NAME], $gatewayId)); }	

            $datenTyp = $this->getModbusDatatype($configEntry[IMR_TYPE]);
            if("continue" == $datenTyp) { continue; }

            //$profile = $this->getProfile($configEntry[IMR_UNITS], $datenTyp);
            $profile = "";

            $instanceId = @IPS_GetObjectIDByIdent($modbusStartAddress, $parentId);
            $initialCreation = false;
            $applyChanges = false;

            // Modbus-Instanz erstellen, sofern noch nicht vorhanden
            if ($instanceId == false) {
                $instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);

                IPS_SetParent($instanceId, $parentId);
                IPS_SetIdent($instanceId, $modbusStartAddress);
                IPS_SetName($instanceId, sprintf("[%s] %s", $modbusStartAddress, $configEntry[IMR_NAME]));
                IPS_SetInfo($instanceId, $configEntry[IMR_DESCRIPTION_SHORT]);

                $applyChanges = true;
                $initialCreation = true;
            }

            // Gateway setzen
            if (IPS_GetInstance($instanceId)['ConnectionID'] != $gatewayId)	{
                
                // sofern bereits eine Gateway verbunden ist, dieses trennen
                if ( IPS_GetInstance($instanceId)['ConnectionID'] != 0) {
                    IPS_DisconnectInstance($instanceId);
                }

                if($gatewayId > 10000) {
                    // neues Gateway verbinden
                    //IPS_LogMessage("SET ..", $instanceId . " to " . $gatewayId);
                    if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, 
                        sprintf("SET ConnectionID for InstanzId '%s' to '%s'", $instanceId, $gatewayId)); }

                    IPS_ConnectInstance($instanceId, $gatewayId);
                    $applyChanges = true;
                } else {
                    if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("WARN :: no valid Modbus-Gateway configured [%s]", $gatewayId)); }
                }
            }


            // Modbus-Instanz konfigurieren
            if ($datenTyp != IPS_GetProperty($instanceId, "DataType"))	{
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'DataType' for InstanzId '%s' to '%s'", $instanceId, $datenTyp)); }
                IPS_SetProperty($instanceId, "DataType", $datenTyp);
                $applyChanges = true;
            }
            if (false != IPS_GetProperty($instanceId, "EmulateStatus"))	{
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'EmulateStatus' for InstanzId '%s' to 'false'", $instanceId)); }
                IPS_SetProperty($instanceId, "EmulateStatus", false);
                $applyChanges = true;
            }
            if ($pollCycle != IPS_GetProperty($instanceId, "Poller")) {
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'Poller' for InstanzId '%s' to '%s'", $instanceId, $pollCycle)); }
                IPS_SetProperty($instanceId, "Poller", $pollCycle);
                $applyChanges = true;
            }

            //if(0 != IPS_GetProperty($instanceId, "Factor"))	{
            //	IPS_SetProperty($instanceId, "Factor", 0);
            //	$applyChanges = true;
            //}

            if ($modbusStartAddress + MODBUS_REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "ReadAddress")) {

                $tartAddress = $modbusStartAddress + MODBUS_REGISTER_TO_ADDRESS_OFFSET;
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'ReadAddress' for InstanzId '%s' to '%s'", $instanceId, $tartAddress)); }
                IPS_SetProperty($instanceId, "ReadAddress", $tartAddress);
                $applyChanges = true;
            }

            if ($configEntry[IMR_FUNCTION_CODE] != IPS_GetProperty($instanceId, "ReadFunctionCode"))	{
                $readFunctionCode = $configEntry[IMR_FUNCTION_CODE];
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'ReadFunctionCode' for InstanzId '%s' to '%s'", $instanceId, $readFunctionCode)); }
                IPS_SetProperty($instanceId, "ReadFunctionCode", $readFunctionCode);
                $applyChanges = true;
            }

            //if( != IPS_GetProperty($instanceId, "WriteAddress")) {
            //	IPS_SetProperty($instanceId, "WriteAddress", );
            //	$applyChanges = true;
            //}

            if (IPS_GetProperty($instanceId, "WriteFunctionCode") != 0)	{
                $writeFunctionCode = 0;
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("SET Property 'WriteFunctionCode' for InstanzId '%s' to '%s'", $instanceId, $writeFunctionCode)); }
                IPS_SetProperty($instanceId, "WriteFunctionCode", $writeFunctionCode);
                $applyChanges = true;
            }

            if ($applyChanges) {
                if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("ApplyChanges to InstanzId '%s'", $instanceId)); }
                IPS_ApplyChanges($instanceId);
                //IPS_Sleep(100);
            }

            /*
            // Statusvariable der Modbus-Instanz ermitteln
            $varId = IPS_GetObjectIDByIdent("Value", $instanceId);

            // Profil der Statusvariable initial einmal zuweisen
            if ($initialCreation && false != $profile) {
                // Justification Rule 11: es ist die Funktion RegisterVariable...() in diesem Fall nicht nutzbar, da die Variable durch die Modbus-Instanz bereits erstellt wurde
                // --> Custo Profil wird initial einmal beim Instanz-erstellen gesetzt

                IPS_SetVariableCustomProfile($varId, $profile);
            }
            */
        }

    }
		

    protected function createModbusInstances_OLD($inverterRegisterConfigArr, $categoryRootId, $gatewayId, $pollCycle, $uniqueIdent = "") {

        $parentId = @IPS_GetObjectIDByIdent("ModbusDevices", $categoryRootId);
        if ($parentId == false) {
            $parentId = IPS_CreateCategory();
            IPS_SetParent($parentId, $categoryRootId);
            IPS_SetIdent($parentId, "ModbusDevices");
            IPS_SetName($parentId, "ModbusDevices");
        }           

        
        foreach ($inverterRegisterConfigArr as $inverterRegisterConfig) {

            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("CREATE '%s - %s'", $inverterRegisterConfig[IMR_START_REGISTER],$inverterRegisterConfig[IMR_NAME])); }	

            $datenTyp = $this->getModbusDatatype($inverterRegisterConfig[IMR_TYPE]);
            if("continue" == $datenTyp) { continue; }

            //$profile = $this->getProfile($inverterRegisterConfig[IMR_UNITS], $datenTyp);
            $profile = "";

            $instanceId = @IPS_GetObjectIDByIdent($inverterRegisterConfig[IMR_START_REGISTER].$uniqueIdent, $parentId);
            $initialCreation = false;
            $applyChanges = false;

            // Modbus-Instanz erstellen, sofern noch nicht vorhanden
            if ($instanceId == false) {
                $instanceId = IPS_CreateInstance(MODBUS_ADDRESSES);

                IPS_SetParent($instanceId, $parentId);
                IPS_SetIdent($instanceId, $inverterRegisterConfig[IMR_START_REGISTER].$uniqueIdent);
                IPS_SetName($instanceId, sprintf("[%s] %s", $inverterRegisterConfig[IMR_START_REGISTER], $inverterRegisterConfig[IMR_NAME]));
                IPS_SetInfo($instanceId, $inverterRegisterConfig[IMR_DESCRIPTION_SHORT]);

                $applyChanges = true;
                $initialCreation = true;
            }

            // Gateway setzen
            if (IPS_GetInstance($instanceId)['ConnectionID'] != $gatewayId)	{
                
                // sofern bereits eine Gateway verbunden ist, dieses trennen
                if ( IPS_GetInstance($instanceId)['ConnectionID'] != 0) {
                    IPS_DisconnectInstance($instanceId);
                }
                // neues Gateway verbinden

                $logMsg = sprintf("SET Instance '%s' to Gateway '%s'", $instanceId, $gatewayId);
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, $logMsg , 0); }	

                IPS_ConnectInstance($instanceId, $gatewayId);
                $applyChanges = true;
            }


            // Modbus-Instanz konfigurieren
            if ($datenTyp != IPS_GetProperty($instanceId, "DataType"))	{
                IPS_SetProperty($instanceId, "DataType", $datenTyp);
                $applyChanges = true;
            }
            if (false != IPS_GetProperty($instanceId, "EmulateStatus"))	{
                IPS_SetProperty($instanceId, "EmulateStatus", false);
                $applyChanges = true;
            }
            if ($pollCycle != IPS_GetProperty($instanceId, "Poller")) {
                IPS_SetProperty($instanceId, "Poller", $pollCycle);
                $applyChanges = true;
            }

            //if(0 != IPS_GetProperty($instanceId, "Factor"))	{
            //	IPS_SetProperty($instanceId, "Factor", 0);
            //	$applyChanges = true;
            //}

            if ($inverterRegisterConfig[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET != IPS_GetProperty($instanceId, "ReadAddress")) {
                IPS_SetProperty($instanceId, "ReadAddress", $inverterRegisterConfig[IMR_START_REGISTER] + MODBUS_REGISTER_TO_ADDRESS_OFFSET);
                $applyChanges = true;
            }
            if ($inverterRegisterConfig[IMR_FUNCTION_CODE] != IPS_GetProperty($instanceId, "ReadFunctionCode"))	{
                IPS_SetProperty($instanceId, "ReadFunctionCode", $inverterRegisterConfig[IMR_FUNCTION_CODE]);
                $applyChanges = true;
            }

            //if( != IPS_GetProperty($instanceId, "WriteAddress")) {
            //	IPS_SetProperty($instanceId, "WriteAddress", );
            //	$applyChanges = true;
            //}

            if (0 != IPS_GetProperty($instanceId, "WriteFunctionCode"))	{
                IPS_SetProperty($instanceId, "WriteFunctionCode", 0);
                $applyChanges = true;
            }

            if ($applyChanges) {
                IPS_ApplyChanges($instanceId);
                //IPS_Sleep(100);
            }

            // Statusvariable der Modbus-Instanz ermitteln
            $varId = IPS_GetObjectIDByIdent("Value", $instanceId);

            // Profil der Statusvariable initial einmal zuweisen
            if ($initialCreation && false != $profile) {
                // Justification Rule 11: es ist die Funktion RegisterVariable...() in diesem Fall nicht nutzbar, da die Variable durch die Modbus-Instanz bereits erstellt wurde
                // --> Custo Profil wird initial einmal beim Instanz-erstellen gesetzt

                IPS_SetVariableCustomProfile($varId, $profile);
            }
        }

    }
		

    protected function getModbusDatatype($type) {
        // Datentyp ermitteln
        // 0=Bit, 1=Byte, 2=Word, 3=DWord, 4=ShortInt, 5=SmallInt, 6=Integer, 7=Real


        /*    https://www.symcon.de/service/dokumentation/modulreferenz/modbus-rtu-tcp/
        DATENTYP	VORZEICHEN	BIT
        Bit	        unsigned	1       ==  dataType > 0
        Byte	    unsigned	8       ==  dataType > 1
        Word	    unsigned	16      ==  dataType > 2
        DWord	    unsigned	32      ==  dataType > 3
        Char	    signed	    8       ==  dataType > 4
        Short	    signed	    16      ==  dataType > 5
        Integer	    signed	    32      ==  dataType > 6
        Real	    signed	    32      ==  dataType > 7
        Int64	    signed	    64      ==  dataType > 8
        Real64	    signed	    64      ==  dataType > 9
        */




        if ("uint16" == strtolower($type) || "enum16" == strtolower($type) || "uint8+uint8" == strtolower($type)) {
            $datenTyp = 2;
        } elseif ("uint32" == strtolower($type) || "acc32" == strtolower($type)	|| "acc64" == strtolower($type)) {
            $datenTyp = 3;
        } elseif ("sunssf" == strtolower($type)) {
            $datenTyp = 5;      //4;
        } elseif ("int16" == strtolower($type)) {
            $datenTyp = 5;            
        } elseif ("int32" == strtolower($type))	{
            $datenTyp = 6;
        } elseif ("float32" == strtolower($type)) {
            $datenTyp = 7;
        } elseif ("string32" == strtolower($type) || "string16" == strtolower($type) || "string8" == strtolower($type) || "string" == strtolower($type)) {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Datentyp '%s' wird von Modbus in IPS nicht unterstützt -> skip", $type)); }	
            return "continue";
        } else {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Fehler: Unbekannter Datentyp '%s' -> skip", $type)); }	
            return "continue";
        }	
        return $datenTyp;
    }


    protected function CreateIpsVariables($configArr, $categoryRootId) {

        foreach ($configArr as $modbusStartAddress => $configEntry) {       //inverterRegisterConfig > configEntry


            $varId = @IPS_GetObjectIDByIdent($modbusStartAddress, $categoryRootId);
            if($varId === false) {

                $varType = $configEntry[IPS_VARTYPE];
                $varIdent = $modbusStartAddress;
                $varProfile = $configEntry[IPS_VARPROFILE];
                $varName = sprintf("[%s] %s", $modbusStartAddress, $configEntry[IMR_NAME]);

                if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, 
                    sprintf("Create IPS-Variable :: Type: %d | Ident: %s | Profile: %s | Name: %s", $varType, $varIdent, $varProfile, $varName)); }	

                $varId = IPS_CreateVariable($varType);
                IPS_SetParent($varId, $categoryRootId);
                IPS_SetIdent($varId, $varIdent);
                IPS_SetName($varId, sprintf("%s - %s", $varName, $configEntry[IMR_DESCRIPTION_SHORT]));
                IPS_SetInfo($varId, $configEntry[IMR_DESCRIPTION_LONG]);
                IPS_SetVariableCustomProfile ($varId, $varProfile);
            } else {
                if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, 
                    sprintf("IPS-Variable exists :: ParentId: %s | Ident: %s | Name: %s'",$categoryRootId, $modbusStartAddress, IPS_GetName($varId))); }	
            }


        }

    }
   
    protected function UpdateModbusRegisterModel($registerModelIdent, $updateVariables=true) {

        $start_Time = microtime(true);

        $categoryRootId = @IPS_GetObjectIDByIdent($registerModelIdent, IPS_GetParent($this->InstanceID));
        if($categoryRootId === false) {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category for Register-Model '%s' does not exist -> Skip Update ...", $registerModelIdent)); }
        } else {

            $modebusDevicesCategoryId = @IPS_GetObjectIDByIdent("ModbusDevices", $categoryRootId);
            if($modebusDevicesCategoryId === false) {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category 'ModbusDevices' does not exist in '[%s] - %s'", $categoryRootId, IPS_GetLocation($categoryRootId))); }
            } else {

                if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Start Modbus Update for Register-Model '%s' in [%s] - %s' ...", $registerModelIdent, $modebusDevicesCategoryId, IPS_GetLocation($modebusDevicesCategoryId))); }

                $cnt = 0;
                $instanzIDs = IPS_GetChildrenIDs($modebusDevicesCategoryId);
                foreach($instanzIDs as $instanzID) {
                    $cnt++;
                    if(IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleID"] == MODBUS_ADDRESSES) {

                        $startTime_DeviceUpdate = microtime(true);
                        $result = @ModBus_RequestRead($instanzID);  
                        if($result) { 
                            SetValue($this->GetIDForIdent("modbusReadOK"), GetValue($this->GetIDForIdent("modbusReadOK")) + 1);
                            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, 
                                sprintf("Modbus Device updated [%s ms] < '%s' ", $this->CalcDuration_ms($startTime_DeviceUpdate), IPS_GetLocation($instanzID))); }
                        } else {
                            SetValue($this->GetIDForIdent("modbusReadNotOK"), GetValue($this->GetIDForIdent("modbusReadNotOK")) + 1);
                            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, 
                                sprintf("Modbus Device Update FAILD > '%s' ", IPS_GetLocation($instanzID))); }
                        }
                    }
                    IPS_Sleep(10);
                }
                if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, 
                    sprintf("%d Modbus Devices updated in '%s' [%s ms]", $cnt, $registerModelIdent, $this->CalcDuration_ms($start_Time))); }

                if($updateVariables) {
                    $this->UpdateRegisterModelVariables($registerModelIdent, $categoryRootId, $modebusDevicesCategoryId);
                }
            }
        }
    }


    protected function UpdateRegisterModelVariables($registerModelIdent, $categoryRootId=NULL, $modebusDevicesCategoryId=NULL) {

        if(is_null($categoryRootId)) {
            $categoryRootId = @IPS_GetObjectIDByIdent($registerModelIdent, IPS_GetParent($this->InstanceID));
            if($categoryRootId === false) {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category for Register-Model '%s' does not exist -> Skip Update ...", $registerModelIdent)); }
                return false;
            }
        }

        if(is_null($categoryRootId)) {
            $modebusDevicesCategoryId = @IPS_GetObjectIDByIdent("ModbusDevices", $categoryRootId);
            if($modebusDevicesCategoryId === false) {
                if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Category 'ModbusDevices' does not exist in '[%s] - %s'", $categoryRootId, IPS_GetLocation($categoryRootId))); }
                return false;
            } 
        }

        $inverterRegisterConfig = $this->GetInverterRegisterConfig($registerModelIdent);

        $childrenIDs = IPS_GetChildrenIDs($categoryRootId);
        foreach($childrenIDs as $childID) {
            
            $objInfo = IPS_GetObject($childID);
            $objType = $objInfo["ObjectType"];

            if($objType == 2) {
                $objIdent = $objInfo["ObjectIdent"];
                $souceValue = $this->GetModebusDeviceSourceValue($objIdent, $modebusDevicesCategoryId);
                
                if($souceValue == 65535) {
                    // inverter sends a null value of 65535
                    SetValue($childID, 0.001);
                } else if(!is_null($souceValue)) {
                    $scalFactorConfigEntry = $inverterRegisterConfig[$objIdent][IMR_SF];
                    $multiplierVal = $inverterRegisterConfig[$objIdent][IPS_VARMULTIPLIER];
                    $roundVal = $inverterRegisterConfig[$objIdent][IPS_VARROUND];                    
                    if(is_null($scalFactorConfigEntry) OR ($scalFactorConfigEntry < 10000)) {

                        if(!is_null($multiplierVal)) { $souceValue = $souceValue * $multiplierVal; }
                        if(!is_null($roundVal)) { $souceValue = round($souceValue, $roundVal); }

                        if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, 
                            sprintf("SetValue '%s' to Variable [%s] - %s", $souceValue, $childID, IPS_GetLocation($childID))); }
                        SetValue($childID, $souceValue);
                    } else {
                        $scaleFactorValue = $this->GetModebusDeviceSourceValue($scalFactorConfigEntry, $modebusDevicesCategoryId);
                        if(!is_null($scaleFactorValue)) {
                            $value = $souceValue * pow(10, $scaleFactorValue);
                            if(!is_null($multiplierVal)) { $value = $value * $multiplierVal; }
                            if(!is_null($roundVal)) { $value = round($value, $roundVal); }
                            if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, 
                                sprintf("SetValue '%s' [Raw-Value: %s | SF-Config: %s | SF-Value: %s] to Variable [%s] - %s", $value, $souceValue, $scalFactorConfigEntry, $scaleFactorValue, $childID, IPS_GetLocation($childID))); }
                            SetValue($childID, $value);
                        } else {
                            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, 
                                sprintf("Scale-Fator-Value Outdated > Skip Update  [varID: %s | Raw-Value: %s | SF-Config: %s | SF Value: %s | varName: %s]", $childID, $souceValue, $scalFactorConfigEntry, $scaleFactorValue, IPS_GetLocation($childID))); }
                        }
                    }
                }
            }
        }

    }

    protected function GetModebusDeviceSourceValue($objIdent, $modebusDevicesCategoryId) {

        $sourceValue = null;
        $instanceId = @IPS_GetObjectIDByIdent($objIdent, $modebusDevicesCategoryId);
        if($instanceId !== false) {
            $varId = IPS_GetObjectIDByIdent("Value", $instanceId);
            if($varId !== false) {
                $varLastUpdate  = time() - round(IPS_GetVariable($varId)['VariableUpdated']);
                if($varLastUpdate < 600) {
                    $sourceValue = GetValue($varId);
                    if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Read Raw-Value '%s' for Modbus Instance '%s' (Ident: %s | ObjId: %s | VarId: %s)", $sourceValue, IPS_GetName($instanceId), $objIdent, $instanceId, $varId)); }
                } else {
                    if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Outdated Value (%s sec) for Modbus Instance '%s' (Ident: %s | ObjId: %s | VarId: %s) > Skip Variable Update...", $varLastUpdate, IPS_GetName($instanceId), $objIdent, $instanceId, $varId)); }
                }
            }
        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Modbus Instance not found (Ident: %s | Categorie: %s)", $objIdent, $modebusDevicesCategoryId)); } 
        }
        return $sourceValue;

    }


    public function GetConnectionState(int $instanceID) {
        $connectionState = -1;
        $conID = IPS_GetInstance($instanceID)['ConnectionID'];
        if($conID > 0) {
            $connectionState = IPS_GetInstance($conID)['InstanceStatus'];
        } else {
            $connectionState = 0;
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s [%s]' has NO Gateway/Connection [ConnectionID=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $conID)); }
        }
        //SetValue($this->GetIDForIdent("connectionState"), $connectionState);
        return $connectionState;
    }

}
