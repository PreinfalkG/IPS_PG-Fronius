<?php
require_once __DIR__ . '/../libs/FRONIUS_COMMON.php'; 
require_once __DIR__ . '/../libs/GEN24_COMMON.php'; 
include_once("GEN24_Modbus.php");
include_once("GEN24_ModbusConfig.php");


	class GEN24_Modebus extends IPSModule {

		use GEN24_COMMON;
		use GEN24_Modbus;
		use GEN24_ModbusConfig;

		CONST INVERTER_CategoryArr = array(
			"IC120" => "IC120 Nameplate",
			"IC121" => "IC121 Basic Settings",
			"IC122" => "IC122 Extended Measurements & Status",
			"IC123" => "IC123 Immediate Controls",
			"IC124" => "IC124 Basic Storage Control",
			"IC160" => "IC160 Multiple MPPT Inverter Extension",
		);


		private $logLevel = 3;		// WARN = 3;
		private $parentRootId;
		//private $gatewayId;
		private $GEN24_IP;
		private $GEN24_PORT;

		public function __construct($InstanceID) {
		
			parent::__construct($InstanceID);		// Diese Zeile nicht löschen

			$this->parentRootId = IPS_GetParent($this->InstanceID);

			$kernelRunlevel = IPS_GetKernelRunlevel(); 
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("KernelRunlevel is: %s", $kernelRunlevel), 0); }
			if($kernelRunlevel == 10103) {

				$currentStatus = $this->GetStatus();
				if($currentStatus == 102) {				//Instanz ist aktiv
					$this->logLevel = $this->ReadPropertyInteger("LogLevel");
					if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel), 0); }

					$gatewayId = $this->ReadPropertyInteger("si_ModebusGatewayID");
					if($gatewayId > 10000) {
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Use Modbus-Gateway '%d - %s'", $gatewayId, IPS_GetLocation($gatewayId )), 0); }
					} else {				
						if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("WARN :: no Modbus-Gateway configured [%s]", $gatewayId), 0); }
					}

				} else {
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Current Status is '%s' | KernelRunlevel is '%s'", $currentStatus, $kernelRunlevel), 0); }	
				}

			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("WARN : KernelRunlevel is '%s'", $kernelRunlevel), 0); }
			}			

		}


		public function Create() {
			//Never delete this line!
			parent::Create();
            
            $this->RegisterPropertyString('GEN24_IP', "10.0.10.160");
			$this->RegisterPropertyString('GEN24_PORT', "502");

			$this->RegisterPropertyBoolean('EnableAutoUpdate', false);
			$this->RegisterPropertyInteger('AutoUpdateInterval', 15);	
			$this->RegisterPropertyInteger('LogLevel', 3);

			$this->RegisterPropertyInteger('si_ModebusGatewayID', 0);
			
			$this->RegisterPropertyBoolean('cb_IC120', false);
			$this->RegisterPropertyBoolean('cb_IC121', false);
			$this->RegisterPropertyBoolean('cb_IC122', false);
			$this->RegisterPropertyBoolean('cb_IC123', false);
			$this->RegisterPropertyBoolean('cb_IC124', false);
			$this->RegisterPropertyBoolean('cb_IC160', false);

			$this->RegisterTimer('TimerAutoUpdate_GEN24MB', 0, 'GEN24MB_TimerAutoUpdate_GEN24MB($_IPS[\'TARGET\']);');

		}

		public function Destroy() {
			//$this->SetUpdateInterval(0);		//Stop Auto-Update Timer
			IPS_LogMessage("[" . __CLASS__ . "] - " . __FUNCTION__, sprintf("Destroy - GEN24_Modbus Modul Instance '%s'", $this->InstanceID));
			parent::Destroy();					//Never delete this line!
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();

			$this->logLevel = $this->ReadPropertyInteger("LogLevel");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel), 0); }

			$gatewayId = $this->ReadPropertyInteger("si_ModebusGatewayID");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Use Modbus-Gateway '%d - %s'", $gatewayId, IPS_GetLocation($gatewayId )), 0); }

			$this->RegisterProfiles();
			$this->RegisterVariables();  

			$enableAutoUpdate = $this->ReadPropertyBoolean("EnableAutoUpdate");		
			if($enableAutoUpdate) {
				$updateInterval = $this->ReadPropertyInteger("AutoUpdateInterval");
			} else {
				$updateInterval = 0;
			}
			$this->SetUpdateInterval($updateInterval);

		}

		public function SetUpdateInterval(int $updateInterval) {
			if ($updateInterval == 0) {  
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Auto-Update stopped [TimerIntervall = 0]", 0); }	
			}else if ($updateInterval < 5) { 
               	$updateInterval = 5; 
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval), 0); }	
			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval), 0); }
			}
			$this->SetTimerInterval("TimerAutoUpdate_GEN24MB", $updateInterval * 1000);	
		}


		public function TimerAutoUpdate_GEN24MB() {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "called ...", 0); }
			$this->Update();
		}

		public function Update() {

			$skipUdateSec = 600;
			$lastUpdate  = time() - round(IPS_GetVariable($this->GetIDForIdent("ErrorCnt"))["VariableUpdated"]);
			$errorCnt = GetValueInteger($this->GetIDForIdent("ErrorCnt"));
			if (($lastUpdate > $skipUdateSec) || ($errorCnt == 0)) {

				$gatewayId = $this->ReadPropertyInteger("si_ModebusGatewayID");

				$this->GEN24_IP = $this->ReadPropertyString('GEN24_IP');
				$this->GEN24_PORT = $this->ReadPropertyString('GEN24_PORT');

				if($gatewayId >= 10000) {
					$currentStatus = $this->GetStatus();
					if($currentStatus == 102) {		
					
						$gatewayStatus = IPS_GetInstance($gatewayId)["InstanceStatus"];
						if($gatewayStatus == 102) {	
							$gatewayConnId = IPS_GetInstance($gatewayId)["ConnectionID"];
							if($gatewayConnId > 0) {
								$ioStatus = IPS_GetInstance($gatewayConnId)["InstanceStatus"];
								if($ioStatus == 102) {	

									$start_Time = microtime(true);

									if($this->ReadPropertyBoolean("cb_IC124")) { $this->UpdateModbusRegisterModel("IC124", true); }
									if($this->ReadPropertyBoolean("cb_IC160")) { $this->UpdateModbusRegisterModel("IC160", true); }
										

									$duration = $this->CalcDuration_ms($start_Time);
									SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), $duration); 

								} else {
									SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
									if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Modbus Client Socket '%s - [%s]' not activ [Status=%s]", $gatewayConnId, IPS_GetName($gatewayConnId), $ioStatus), 0); }	
								}
							} else {
								SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
								if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Modebus Gateway '%s - [%s]' has no Connection ID [Status=%s]", $gatewayId, IPS_GetName($gatewayId), $gatewayConnId), 0); }	
							}
						} else {
							SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
							if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Modebus Gateway '%s - [%s]' not activ [Status=%s]", $gatewayId, IPS_GetName($gatewayId), $gatewayStatus), 0); }
						}

					} else {
						SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
						if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s - [%s]' not activ [Status=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $currentStatus), 0); }
					}
				} else {
					SetValue($this->GetIDForIdent("updateSkipCnt"), GetValue($this->GetIDForIdent("updateSkipCnt")) + 1);
					if($this->logLevel >= LogLevel::ERROR) { $this->AddLog(__FUNCTION__, sprintf("ERROR :: no valid Modbus-Gateway configured [%s]", $gatewayId), 0); }
				}
			} else {
				SetValue($this->GetIDForIdent("updateSkipCnt"), GetValue($this->GetIDForIdent("updateSkipCnt")) + 1);
				$logMsg =  sprintf("WARNING :: Skip Update for %d sec for Instance '%s' >> last error %d seconds ago...", $skipUdateSec, $this->InstanceID, $lastUpdate);
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, $logMsg, 0); }
				IPS_LogMessage("GEN24", $logMsg);
			}
		}

		public function ResetCounterVariables() {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'RESET Counter Variables', 0); }
            
			SetValue($this->GetIDForIdent("modbusReadOK"), 0);
			SetValue($this->GetIDForIdent("modbusReadNotOK"), 0);
			SetValue($this->GetIDForIdent("updateSkipCnt"), 0);
			SetValue($this->GetIDForIdent("ErrorCnt"), 0); 
			SetValue($this->GetIDForIdent("LastError"), "-"); 
			SetValue($this->GetIDForIdent("instanzInactivCnt"), 0); 
			SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), 0); 
			SetValue($this->GetIDForIdent("LastDataReceived"), 0); 
		}


		public function SetStatusAktiv() {
			$currentStatus = $this->GetStatus();		
			if($this->logLevel >= LogLevel::INFO) { 
				$logMsg = sprintf("Current Status is: %s > set Status now to '102 - aktiv' ...", $currentStatus);
				$this->AddLog(__FUNCTION__, $logMsg, 0); 
			}
			$newStatus = $this->SetStatus(102);
			$newStatus = $this->GetStatus();
            if($this->logLevel >= LogLevel::INFO) { 
				$logMsg = sprintf("NEW Status is: %s", $newStatus);
				$this->AddLog(__FUNCTION__, $logMsg, 0); 
			}
		}


		public function SetStatusInaktiv() {
			$currentStatus = $this->GetStatus();		
			if($this->logLevel >= LogLevel::INFO) { 
				$logMsg = sprintf("Current Status is: %s > set Status now to '104 - inaktiv' ...", $currentStatus);
				$this->AddLog(__FUNCTION__, $logMsg, 0); 
			}
			$newStatus = $this->SetStatus(104);
			$newStatus = $this->GetStatus();
            if($this->logLevel >= LogLevel::INFO) { 
				$logMsg = sprintf("NEW Status is: %s", $newStatus);
				$this->AddLog(__FUNCTION__, $logMsg, 0); 
			}
		}
		

		public function InitInverterModel() {

			$cnt = 0;
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "...", 0); }
			foreach(self::INVERTER_CategoryArr as $key => $value) {
				$cb_value = $this->ReadPropertyBoolean("cb_".$key);	
				if($cb_value) {
					$cnt++;
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Init '%s' ...", $value), 0); }
					$this->CreateInverterModel($key, $value);
				}
			}

			if($cnt == 1) {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("initialized %d InverterModel", $cnt), 0); }
			} else if($cnt > 1) {
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("initialized %d InverterModels", $cnt), 0); }			
			} else {
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("WARN :: Kein InverterModel aktiviert", $cnt), 0); }
			}


		}


		protected function RegisterVariables() {

			/*
			foreach(self::INVERTER_CategoryArr as $key => $value) {

				$cb_value= $this->ReadPropertyBoolean("cb_".$key);	
				if($cb_value) {
					$this->CreateInverterModel($key, $value);
				}

			}
			*/


			$this->RegisterVariableInteger("modbusReadOK", "Modbus Read OK", "", 900);
			$this->RegisterVariableInteger("modbusReadNotOK", "Modbus Read FAILD", "", 910);
			$this->RegisterVariableInteger("updateSkipCnt", "Update Skip Cnt", "", 915);
			$this->RegisterVariableInteger("ErrorCnt", "Error Cnt", "", 920);
			$this->RegisterVariableString("LastError", "Last Error", "", 920);
			$this->RegisterVariableInteger("instanzInactivCnt", "Instanz Inactiv Cnt", "", 930);
			$this->RegisterVariableFloat("lastProcessingTotalDuration", "Last Processing Duration [ms]", "", 940);	
			$this->RegisterVariableInteger("LastDataReceived", "Last Data Received", "~UnixTimestamp", 950);

			$scriptScr = sprintf("<?php GEN24MB_Update(%s); ?>",$this->InstanceID);
			$this->RegisterScript("UpdateScript", "Update", $scriptScr, 990);


			$archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
			IPS_ApplyChanges($archivInstanzID);
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variables registered", 0); }

		}

		protected function AddLog($name, $daten, $format, $enableIPSLogOutput=false) {
			$this->SendDebug("[" . __CLASS__ . "] - " . $name, $daten, $format); 	
	
			if($enableIPSLogOutput) {
				if($format == 0) {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $daten);	
				} else {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $this->String2Hex($daten));			
				}
			}
		}



	}