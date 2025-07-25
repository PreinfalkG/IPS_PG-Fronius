<?php
require_once __DIR__ . '/../libs/FRONIUS_COMMON.php'; 
require_once __DIR__ . '/../libs/GEN24_COMMON.php'; 
include_once("GEN24_PrivateAPI.php");

class GEN24_SolarAPI extends IPSModule {

	use GEN24_COMMON;
	use GEN24_PrivateAPI;

	const CATEGORY_NAME_PowerFlowRealTimeData = "PowerFlow_RealTimeData";
	const CATEGORY_NAME_PowerFlow = "Powerflow";
	const CATEGORY_NAME_PowerMeters = "PowerMeters";
	const CATEGORY_NAME_BatteryManagementSystem = "BatteryManagementSystem";
	const CATEGORY_NAME_Ohmpilot = "Ohmpilot";
	const CATEGROY_NAME_Devices = "Devices";
	const CATEGROY_NAME_Cache = "Cache";

	
	private $logLevel = 3;		// WARN = 3;
	private $logCnt = 0;
	private $enableIPSLogOutput = false;		
	private $GEN24_IP;

	public function __construct($InstanceID) {
	
		parent::__construct($InstanceID);		// Diese Zeile nicht löschen

		$this->logLevel = @$this->ReadPropertyInteger("LogLevel"); 
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel)); }	
	}


	public function Create() {
		
		parent::Create();				//Never delete this line!

		$logMsg = sprintf("Create Modul '%s [%s]'...", IPS_GetName($this->InstanceID), $this->InstanceID);
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, $logMsg); }
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, $logMsg);

		$logMsg = sprintf("KernelRunlevel '%s'", IPS_GetKernelRunlevel());
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }	
		
		$this->RegisterPropertyString('GEN24_IP', "10.0.11.160");

		$this->RegisterPropertyBoolean('EnableAutoUpdate', false);
		$this->RegisterPropertyInteger('AutoUpdateInterval', 15);	
		$this->RegisterPropertyInteger('LogLevel', 3);

		$this->RegisterPropertyBoolean('cb_PowerFlowRealtimeData', false);
		$this->RegisterPropertyBoolean('cb_Powerflow', false);
		$this->RegisterPropertyBoolean('cb_PowerMeter', false);
		$this->RegisterPropertyBoolean('cb_BatteryManagementSystem', false);
		$this->RegisterPropertyBoolean('cb_Ohmpilot', false);
		$this->RegisterPropertyBoolean('cb_Devices', false);
		$this->RegisterPropertyBoolean('cb_Cache', false);
		$this->RegisterPropertyBoolean('cb_Events', false);
		$this->RegisterPropertyBoolean('cb_ActiveEvents', false);
		$this->RegisterPropertyBoolean('cb_PowerFlowRealtimeData_SetRaw', false);

		$this->RegisterTimer('TimerAutoUpdate_GEN24', 0, 'GEN24_TimerAutoUpdate_GEN24($_IPS[\'TARGET\']);');
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	public function Destroy() {
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
		parent::Destroy();						//Never delete this line!
	}

	public function ApplyChanges() {
		
		parent::ApplyChanges();					//Never delete this line!

		$this->logLevel = $this->ReadPropertyInteger("LogLevel");
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel)); }

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

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)	{
		$logMsg = sprintf("TimeStamp: %s | SenderID: %s | Message: %s | Data: %s", $TimeStamp, $SenderID, $Message, json_encode($Data));
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }
		//IPS_LogMessage(__CLASS__."_".__FUNCTION__, $logMsg);
	}	

	public function SetUpdateInterval(int $updateInterval) {
		if ($updateInterval == 0) {  
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Auto-Update stopped [TimerIntervall = 0]"); }	
		}else if ($updateInterval < 5) { 
			$updateInterval = 5; 
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval)); }	
		} else {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval)); }
		}
		$this->SetTimerInterval("TimerAutoUpdate_GEN24", $updateInterval * 1000);	
	}


	public function TimerAutoUpdate_GEN24() {
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "called ..."); }
		$this->Update();
	}

	public function Update() {

		$skipUdateSec = 600;
		$lastUpdate  = time() - round(IPS_GetVariable($this->GetIDForIdent("ErrorCnt"))["VariableUpdated"]);
		$errorCnt = GetValueInteger($this->GetIDForIdent("ErrorCnt"));
		if (($lastUpdate > $skipUdateSec) || ($errorCnt == 0)) {

			$this->GEN24_IP = $this->ReadPropertyString('GEN24_IP');
			$currentStatus = $this->GetStatus();
			if($currentStatus == 102) {		
			
				$start_Time = microtime(true);

				if($this->ReadPropertyBoolean("cb_PowerFlowRealtimeData")) 	{ $this->RequestPowerFlowRealtimeData(); }

				if($this->ReadPropertyBoolean("cb_Powerflow")) 	{ $this->RequestPowerFlow(); }
				if($this->ReadPropertyBoolean("cb_PowerMeter")) { $this->RequesPowerMeters(); }
				if($this->ReadPropertyBoolean("cb_BatteryManagementSystem")) { $this->RequestBatteryManagementSystem(); }
				if($this->ReadPropertyBoolean("cb_Ohmpilot")) 	{ $this->RequestOhmpilot(); }
				if($this->ReadPropertyBoolean("cb_Devices")) 	{ $this->RequestDevices(); }
				if($this->ReadPropertyBoolean("cb_Cache"))		{ $this->RequestCache(); }

				$duration = $this->CalcDuration_ms($start_Time);
				SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), $duration); 

			} else {
				SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s - [%s]' not activ [Status=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $currentStatus)); }
			}
		} else {
			SetValue($this->GetIDForIdent("updateSkipCnt"), GetValue($this->GetIDForIdent("updateSkipCnt")) + 1);
			$logMsg =  sprintf("WARNING :: Skip Update for %d sec for Instance '%s' >> last error %d seconds ago...", $skipUdateSec, $this->InstanceID, $lastUpdate);
			if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, $logMsg); }
		}
	}

	public function ResetCounterVariables() {
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'RESET Counter Variables', 0); }
		
		SetValue($this->GetIDForIdent("requestCnt"), 0);
		SetValue($this->GetIDForIdent("receiveCnt"), 0);
		SetValue($this->GetIDForIdent("updateSkipCnt"), 0);
		SetValue($this->GetIDForIdent("ErrorCnt"), 0); 
		SetValue($this->GetIDForIdent("LastError"), "-"); 
		SetValue($this->GetIDForIdent("instanzInactivCnt"), 0); 
		SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), 0); 
		SetValue($this->GetIDForIdent("LastDataReceived"), 0); 
	}

	protected function RegisterVariables() {

		$parentRootId = IPS_GetParent($this->InstanceID);

		$cb_PowerFlowRealtimeData = $this->ReadPropertyBoolean("cb_PowerFlowRealtimeData");	
		if($cb_PowerFlowRealtimeData) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerFlowRealTimeData, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGORY_NAME_PowerFlowRealTimeData);
				IPS_SetName($categoryId, self::CATEGORY_NAME_PowerFlowRealTimeData);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 100);
			}
		}

		$cb_Powerflow = $this->ReadPropertyBoolean("cb_Powerflow");	
		if($cb_Powerflow) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerFlow, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGORY_NAME_PowerFlow);
				IPS_SetName($categoryId, self::CATEGORY_NAME_PowerFlow);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 110);
			}
		}

		$cb_PowerMeter = $this->ReadPropertyBoolean("cb_PowerMeter");	
		if($cb_PowerMeter) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_PowerMeters, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGORY_NAME_PowerMeters);
				IPS_SetName($categoryId, self::CATEGORY_NAME_PowerMeters);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 120);
			}
		}

		$cb_BatteryManagementSystem = $this->ReadPropertyBoolean("cb_BatteryManagementSystem");	
		if($cb_BatteryManagementSystem) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_BatteryManagementSystem, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGORY_NAME_BatteryManagementSystem);
				IPS_SetName($categoryId, self::CATEGORY_NAME_BatteryManagementSystem);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 130);
			}
		}

		$cb_Ohmpilot = $this->ReadPropertyBoolean("cb_Ohmpilot");	
		if($cb_Ohmpilot) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_NAME_Ohmpilot, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGORY_NAME_Ohmpilot);
				IPS_SetName($categoryId, self::CATEGORY_NAME_Ohmpilot);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 140);
			}
		}

		$cb_Devices = $this->ReadPropertyBoolean("cb_Devices");	
		if($cb_Devices) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGROY_NAME_Devices, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGROY_NAME_Devices);
				IPS_SetName($categoryId, self::CATEGROY_NAME_Devices);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 150);
			}
		}			
		
		$cb_Cache = $this->ReadPropertyBoolean("cb_Cache");	
		if($cb_Cache) {
			$categoryId = @IPS_GetObjectIDByIdent(self::CATEGROY_NAME_Cache, $parentRootId);
			if($categoryId === false) {
				$categoryId = IPS_CreateCategory();
				IPS_SetIdent($categoryId, self::CATEGROY_NAME_Cache);
				IPS_SetName($categoryId, self::CATEGROY_NAME_Cache);
				IPS_SetParent($categoryId,  $parentRootId);
				IPS_SetPosition($categoryId, 160);
			}
		}				

		$this->RegisterVariableInteger("requestCnt", "Request Cnt", "", 900);
		$this->RegisterVariableInteger("receiveCnt", "Receive Cnt", "", 910);
		$this->RegisterVariableInteger("updateSkipCnt", "Update Skip Cnt", "", 915);
		$this->RegisterVariableInteger("ErrorCnt", "Error Cnt", "", 920);
		$this->RegisterVariableString("LastError", "Last Error", "", 920);
		$this->RegisterVariableInteger("instanzInactivCnt", "Instanz Inactiv Cnt", "", 930);
		$this->RegisterVariableFloat("lastProcessingTotalDuration", "Last Processing Duration [ms]", "", 940);	
		$this->RegisterVariableInteger("LastDataReceived", "Last Data Received", "~UnixTimestamp", 950);
	
		$scriptScr = sprintf("<?php GEN24_Update(%s); ?>",$this->InstanceID);
		$this->RegisterScript("UpdateScript", "Update", $scriptScr, 990);

		$archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
		IPS_ApplyChanges($archivInstanzID);
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variables registered"); }

	}


	protected function AddLog($name, $daten, $format=0, $ipsLogOutput=false) {
		$this->logCnt++;
		$logSender = "[".__CLASS__."] - " . $name;
		if($this->logLevel >= LogLevel::DEBUG) {
			$logSender = sprintf("%02d-T%2d [%s] - %s", $this->logCnt, $_IPS['THREAD'], __CLASS__, $name);
		} 
		$this->SendDebug($logSender, $daten, $format); 	
	
		if($ipsLogOutput or $this->enableIPSLogOutput) {
			if($format == 0) {
				IPS_LogMessage($logSender, $daten);	
			} else {
				IPS_LogMessage($logSender, $this->String2Hex($daten));			
			}
		}
	}

}