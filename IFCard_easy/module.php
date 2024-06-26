<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/FRONIUS_COMMON.php'; 
include_once("IFCard.php");

class IFCard_easy extends IPSModule	{

	//use FRONIUS_COMMON;
	use IFCard;

	const CATEGORY_Data = "Data";

	private $logLevel = 3;		// WARN = 3;
	private $logCnt = 0;
	private $enableIPSLogOutput = false;
	private $deviceOption;			// 0=IFcard | 1=Wechselrichter

	const BUFFER_RECEIVE_EVENT = "receiveEvent";
	const BUFFER_RECEIVED_DATA = "receiveBuffer";

	public function __construct(int $InstanceID) {
	
		parent::__construct($InstanceID);		// Diese Zeile nicht löschen

		$this->deviceOption = 1;

		$this->logLevel = @$this->ReadPropertyInteger("LogLevel") or $this->logLevel = LogLevel::TRACE; 
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel)); }
	}


	public function Create() {
		
		parent::Create();		//Never delete this line!

		$logMsg = sprintf("Create Modul '%s [%s]'...", IPS_GetName($this->InstanceID), $this->InstanceID);
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, $logMsg); }
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, $logMsg);

		$logMsg = sprintf("KernelRunlevel '%s'", IPS_GetKernelRunlevel());
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }			

		$this->RequireParent('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');

		$this->RegisterPropertyBoolean('EnableAutoUpdate', 	 false);
		$this->RegisterPropertyInteger('AutoUpdateInterval', 15);	
		$this->RegisterPropertyInteger('IG_Nr', 			 1);	
		$this->RegisterPropertyInteger('LogLevel', 			 3);
		$this->RegisterPropertyInteger('ClientSocket4Forwarding', 0);


		$this->RegisterPropertyBoolean('cb_IFC_Info', 			true);
		$this->RegisterPropertyBoolean('cb_IFC_ActivInverters', true);
		$this->RegisterPropertyBoolean('cb_IFC_DeviceTyp', 		true);

		$this->RegisterPropertyBoolean('cb_Power', 			true);
		$this->RegisterPropertyBoolean('cb_DcV', 			true);
		$this->RegisterPropertyBoolean('cb_DcA', 			true);

		$this->RegisterPropertyBoolean('cb_AcV', 			true);
		$this->RegisterPropertyBoolean('cb_AcA', 			true);
		$this->RegisterPropertyBoolean('cb_AcF', 			true);

		$this->RegisterPropertyBoolean('cb_Day_Energy', 	true);
		$this->RegisterPropertyBoolean('cb_Day_Yield', 		false);
		$this->RegisterPropertyBoolean('cb_Day_Pmax', 		true);
		$this->RegisterPropertyBoolean('cb_Day_AcVmax', 	true);
		$this->RegisterPropertyBoolean('cb_Day_AcVMin', 	true);
		$this->RegisterPropertyBoolean('cb_Day_DcVmax', 	true);
		$this->RegisterPropertyBoolean('cb_Day_oHours',	 	true);
				
		$this->RegisterPropertyBoolean('cb_Year_Energy', 	false);
		$this->RegisterPropertyBoolean('cb_Year_Yield',		false);
		$this->RegisterPropertyBoolean('cb_Year_Pmax', 		false);
		$this->RegisterPropertyBoolean('cb_Year_AcVmax', 	false);
		$this->RegisterPropertyBoolean('cb_Year_AcVMin', 	false);
		$this->RegisterPropertyBoolean('cb_Year_DcVmax', 	false);
		$this->RegisterPropertyBoolean('cb_Year_oHours',	false);

		$this->RegisterPropertyBoolean('cb_Total_Energy', 	true);
		$this->RegisterPropertyBoolean('cb_Total_Yield', 	false);
		$this->RegisterPropertyBoolean('cb_Total_Pmax', 	false);
		$this->RegisterPropertyBoolean('cb_Total_AcVmax', 	false);
		$this->RegisterPropertyBoolean('cb_Total_AcVMin', 	false);
		$this->RegisterPropertyBoolean('cb_Total_DcVmax', 	false);
		$this->RegisterPropertyBoolean('cb_Total_oHours',	false);			

		$this->RegisterPropertyBoolean('cb_Total_EnergyCustWh',	false);	
		$this->RegisterPropertyInteger('Total_EnergyCustWh_Offset', 0);	

		$this->RegisterTimer('TimerAutoUpdate_IFC', 0, 'IFC_TimerAutoUpdate_IFC($_IPS[\'TARGET\']);');
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
		} else if ($updateInterval < 5) { 
			$updateInterval = 5; 
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval)); }	
		} else {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval)); }
		}
		$this->SetTimerInterval("TimerAutoUpdate_IFC", $updateInterval * 1000);	
	}

	public function TimerAutoUpdate_IFC() {
	
		$masterOnOff = GetValue($this->GetIDForIdent("masterOnOff"));
		if($masterOnOff) {
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "called ..."); }
			$this->Update("Timer"); 
		} else {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("AutoUpate CANCELED > Master Swich is OFF > Connection State '%s' ...", $this->GetConnectionState())); }
		}			
	}

	public function GetConnectionState() {
		$connectionState = -1;
		$conID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		if($conID > 0) {
			$connectionState = IPS_GetInstance($conID)['InstanceStatus'];
		} else {
			$connectionState = 0;
			if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s [%s]' has NO Gateway/Connection [ConnectionID=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $conID)); }
		}
		SetValue($this->GetIDForIdent("connectionState"), $connectionState);
		return $connectionState;
	}

	public function Update(string $source) {

		$start_Time = microtime(true);

		$currentStatus = $this->GetStatus();
		$connectionState = $this->GetConnectionState();
		SetValue($this->GetIDForIdent("connectionState"), $connectionState);

		if($currentStatus == 102) {		
			if($connectionState == 102) {

				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Request/Update Inverter Data via Interface Card ..."); }

				if($this->ReadPropertyBoolean("cb_IFC_Info")) 			{ $this->Request_InterfaceCardInfo("IFC_Info"); }
				
				$activInverters = $this->Request_ActivInverters("IFC_ActivInverters");
				//if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Activ Inverters: %s", print_r($activInverters, true))); }
				if($activInverters > 0) {

					$igNr = $this->ReadPropertyInteger("IG_Nr");
					$deviceOption = $this->deviceOption;

					if($this->ReadPropertyBoolean("cb_IFC_DeviceTyp")) 		{ $this->Request_DeviceTyp("IFC_DeviceType"); }

					if($this->ReadPropertyBoolean("cb_Power")) 				{ $this->RequestInverterData(WR_POWER, "P", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_DcV")) 				{ $this->RequestInverterData(DC_VOLTAGE, "DcV", $deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_DcA")) 				{ $this->RequestInverterData(DC_CURRENT, "DcA", $deviceOption, $igNr); }
					
					if($this->ReadPropertyBoolean("cb_AcV")) 				{ $this->RequestInverterData(AC_VOLTAGE, "AcV", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_AcA")) 				{ $this->RequestInverterData(AC_CURRENT, "AcA", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_AcF")) 				{ $this->RequestInverterData(AC_FREQUENCY, "AcF", 	$deviceOption, $igNr); }
					
					if($this->ReadPropertyBoolean("cb_Day_Energy")) 		{ $this->RequestInverterData(ENERGY_DAY, "day_Energy", 			$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_Yield")) 			{ $this->RequestInverterData(YIELD_DAY, "day_Yield", 			$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_Pmax")) 			{ $this->RequestInverterData(MAX_POWER_DAY, "day_Pmax", 		$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_AcVmax")) 		{ $this->RequestInverterData(MAX_AC_VOLTAGE_DAY, "day_AcVmax", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_AcVMin")) 		{ $this->RequestInverterData(MIN_AC_VOLTAGE_DAY, "day_AcVmin", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_DcVmax")) 		{ $this->RequestInverterData(MAX_DC_VOLTAGE_DAY, "day_DcVmax", 	$deviceOption, $igNr); }
					if($this->ReadPropertyBoolean("cb_Day_oHours")) 		{ $this->RequestInverterData(OPERATING_HOURS_DAY, "day_oHours", $deviceOption, $igNr); }

					if($this->ReadPropertyBoolean("cb_Total_Energy")) 		{ 
						$total_Energy = $this->RequestInverterData(ENERGY_TOTAL, "total_Energy", $deviceOption, $igNr); 

						if($this->ReadPropertyBoolean("cb_Total_EnergyCustWh")) 		{
							$varId = @$this->GetIDForIdent("total_Energy_CustWh");
							if($varId !== false) {
								$total_EnergyCustWh_Offset = $this->ReadPropertyInteger("Total_EnergyCustWh_Offset");
								SetValueFloat($varId, ($total_Energy + $total_EnergyCustWh_Offset) * 1000);
							}
						}
					}


					$minuteNow = idate('i', time());
					if(($minuteNow == 0) or ($source!="Timer")) {
						if($this->ReadPropertyBoolean("cb_Year_Energy")) 		{ $this->RequestInverterData(ENERGY_YEAR, "year_Energy", 			$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_Yield")) 		{ $this->RequestInverterData(YIELD_YEAR, "year_Yield", 				$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_Pmax")) 			{ $this->RequestInverterData(MAX_POWER_YEAR, "year_Pmax", 			$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_AcVmax")) 		{ $this->RequestInverterData(MAX_AC_VOLTAGE_YEAR, "year_AcVmax", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_AcVMin")) 		{ $this->RequestInverterData(MIN_AC_VOLTAGE_YEAR, "year_AcVmin", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_DcVmax")) 		{ $this->RequestInverterData(MAX_DC_VOLTAGE_YEAR, "year_DcVmax", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Year_oHours")) 		{ $this->RequestInverterData(OPERATING_HOURS_YEAR, "year_oHours",	$deviceOption, $igNr); }
						

						if($this->ReadPropertyBoolean("cb_Total_Energy")) 		{ $this->RequestInverterData(ENERGY_TOTAL, "total_Energy", 			$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_Yield")) 		{ $this->RequestInverterData(YIELD_TOTAL, "total_Yield", 			$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_Pmax")) 		{ $this->RequestInverterData(MAX_POWER_TOTAL, "total_Pmax", 		$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_AcVmax")) 		{ $this->RequestInverterData(MAX_AC_VOLTAGE_TOTAL, "total_AcVmax", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_AcVMin")) 		{ $this->RequestInverterData(MIN_AC_VOLTAGE_TOTAL, "total_AcVmin", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_DcVmax")) 		{ $this->RequestInverterData(MAX_DC_VOLTAGE_TOTAL, "total_DcVmax", 	$deviceOption, $igNr); }
						if($this->ReadPropertyBoolean("cb_Total_oHours")) 		{ $this->RequestInverterData(OPERATING_HOURS_TOTAL, "total_oHours", $deviceOption, $igNr); }

					}					
				
				} else {

					$varId = @$this->GetIDForIdent("IFC_ActivInverters");   if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } };

					$varId = @$this->GetIDForIdent("P");   if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					$varId = @$this->GetIDForIdent("DcV"); if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					$varId = @$this->GetIDForIdent("DcA"); if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					$varId = @$this->GetIDForIdent("AcV"); if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					$varId = @$this->GetIDForIdent("AcA"); if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					$varId = @$this->GetIDForIdent("AcF"); if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 


					$nowHour = idate('H', time());
					if($nowHour == 0) {
						// at midnight set the daily values to 0
						$varId = @$this->GetIDForIdent("day_Energy");   if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_Yield"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_Pmax"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_AcVmax"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_AcVmin"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_DcVmax"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
						$varId = @$this->GetIDForIdent("day_oHours"); 	if($varId !== false) { if(GetValue($varId) != 0 ) { SetValue($varId, 0); } }; 
					}

				}


			} else {
				SetValue($this->GetIDForIdent("updateSkipCnt"), GetValue($this->GetIDForIdent("updateSkipCnt")) + 1);
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Connection NOT activ [Status=%s]", $connectionState)); }
			}
		} else {
			SetValue($this->GetIDForIdent("updateSkipCnt"), GetValue($this->GetIDForIdent("updateSkipCnt")) + 1);
			SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
			if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s - [%s]' not activ [Status=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $currentStatus)); }
		}
		$duration = $this->CalcDuration_ms($start_Time);
		SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), $duration);
	}

	public function ReceiveRawData(string $data) {

		if($data == "IFCInfo") { $data = "\x80\x80\x80\x04\x00\x00\x01\x02\x01\x00\x00\x08"; }	//RawData for 'InterfaceCardInfo'

		if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__,  sprintf("Received RawData: %s", $this->String2Hex($data))); }

		$dataArr = explode("\x80\x80\x80", $data);
		foreach($dataArr as $dataArrElem) {
			$strLen = strlen($dataArrElem);
			if ( $strLen > 0) {
				$dataArrElem = sprintf("\x80\x80\x80%s", $dataArrElem);
				$rpacketArr = unpack('C*', $dataArrElem);
				if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__,  sprintf("Proces RawData Record: %s", $this->ByteArr2HexStr($rpacketArr))); }
				$this->ParsePacket($rpacketArr, -1);
			} else {
				if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__,  "RawData Record Len: 0  (skip Array Element)"); }
			}
		}

	}

	public function ResetCounterVariables() {
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'RESET Counter Variables', 0); }
		
		SetValue($this->GetIDForIdent("ERR_Nr"), 0);
		SetValue($this->GetIDForIdent("ERR_Info"), 0);
		SetValue($this->GetIDForIdent("ERR_Cnt"), 0);
		SetValue($this->GetIDForIdent("connectionState"), 0); 
		SetValue($this->GetIDForIdent("requestCnt"), 0);
		SetValue($this->GetIDForIdent("receiveCnt"), 0);
		SetValue($this->GetIDForIdent("updateSkipCnt"), 0);	
		SetValue($this->GetIDForIdent("CrcErrorCnt"), 0); 
		SetValue($this->GetIDForIdent("InconsistentDataCnt"), 0); 		
		SetValue($this->GetIDForIdent("ErrorCnt"), 0); 
		SetValue($this->GetIDForIdent("LastError"), "-"); 
		SetValue($this->GetIDForIdent("instanzInactivCnt"), 0); 
		SetValue($this->GetIDForIdent("lastProcessingTotalDuration"), 0); 
		SetValue($this->GetIDForIdent("LastDataReceived"), 0); 
	}


	protected function RegisterVariables() {

		$archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];

		$varId = $this->RegisterVariableBoolean("masterOnOff", "MASTER ON / OFF", "~Switch", 10);

		$scriptContent = '<? $varId=$_IPS["VARIABLE"]; SetValue($varId, $_IPS["VALUE"]); ?>';
		$scriptId = $this->RegisterScript("aktionsSkriptOnOff", "Aktionsskript On/Off", $scriptContent, 11);
		IPS_SetParent($scriptId, $this->InstanceID);
		IPS_SetHidden($scriptId, true);
		IPS_SetDisabled($scriptId, true);
		IPS_SetPosition($scriptId, 999);

		IPS_SetVariableCustomAction($varId, $scriptId);

		$varId = $this->RegisterVariableInteger("connectionState", "Connection STATE", "IPS_ModulConnectionState", 20);
		AC_SetLoggingStatus ($archivInstanzID, $varId, true);

		if($this->ReadPropertyBoolean("cb_IFC_Info")) { $this->RegisterVariableString("IFC_Info", "Interface", "", 100); }
		$this->RegisterVariableInteger("IFC_ActivInverters", "Activ Inverters", "", 110);
		if($this->ReadPropertyBoolean("cb_IFC_DeviceTyp")) { $this->RegisterVariableString("IFC_DeviceType", "Device Type", "", 120); }

		if($this->ReadPropertyBoolean("cb_Power")) 		{ $this->RegisterVariableFloat("P", 			"POWER", 					"~Watt", 200); }
		if($this->ReadPropertyBoolean("cb_DcV")) 			{ $this->RegisterVariableFloat("DcV", 			"DC Voltage", 				"~Volt", 250); }
		if($this->ReadPropertyBoolean("cb_DcA")) 			{ $this->RegisterVariableFloat("DcA", 			"DC Current", 				"~Ampere", 260); }

		if($this->ReadPropertyBoolean("cb_AcV")) 			{ $this->RegisterVariableFloat("AcV", 			"AC Voltage", 				"~Volt", 300); }
		if($this->ReadPropertyBoolean("cb_AcA")) 			{ $this->RegisterVariableFloat("AcA",			"AC Current", 				"~Ampere", 310); }
		if($this->ReadPropertyBoolean("cb_AcF")) 			{ $this->RegisterVariableFloat("AcF", 			"AC Frequency", 			"~Hertz", 320); }
		
		if($this->ReadPropertyBoolean("cb_Day_Energy")) 	{ $this->RegisterVariableFloat("day_Energy", 	"DAY Energy", 				"~Electricity", 400); }
		if($this->ReadPropertyBoolean("cb_Day_Yield")) 	{ $this->RegisterVariableFloat("day_Yield", 	"DAY Yield", 	 			"~Euro", 410); }
		if($this->ReadPropertyBoolean("cb_Day_Pmax")) 		{ $this->RegisterVariableFloat("day_Pmax", 		"DAY max. Power", 	 		"~Watt", 420); }
		if($this->ReadPropertyBoolean("cb_Day_AcVmax")) 	{ $this->RegisterVariableFloat("day_AcVmax", 	"DAY max. AC Voltage", 		"~Volt", 430); }
		if($this->ReadPropertyBoolean("cb_Day_AcVMin")) 	{ $this->RegisterVariableFloat("day_AcVmin", 	"DAY min. AC Voltage", 		"~Volt", 440); }
		if($this->ReadPropertyBoolean("cb_Day_DcVmax")) 	{ $this->RegisterVariableFloat("day_DcVmax", 	"DAY max. DC Voltage", 		"~Volt", 450); }
		if($this->ReadPropertyBoolean("cb_Day_oHours")) 	{ $this->RegisterVariableInteger("day_oHours", 	"DAY Operating Hours", 		"~UnixTimestampTime", 460); }

		if($this->ReadPropertyBoolean("cb_Year_Energy")) 	{ $this->RegisterVariableFloat("year_Energy", 	"YEAR Energy",				"~Electricity", 500); }			
		if($this->ReadPropertyBoolean("cb_Year_Yield")) 	{ $this->RegisterVariableFloat("year_Yield", 	"YEAR Yield", 	 			"~Euro", 510); }
		if($this->ReadPropertyBoolean("cb_Year_Pmax")) 	{ $this->RegisterVariableFloat("year_Pmax", 	"YEAR max. Power", 	 		"~Watt", 520); }
		if($this->ReadPropertyBoolean("cb_Year_AcVmax")) 	{ $this->RegisterVariableFloat("year_AcVmax", 	"YEAR max. AC Voltage", 	"~Volt", 530); }
		if($this->ReadPropertyBoolean("cb_Year_AcVMin")) 	{ $this->RegisterVariableFloat("year_AcVmin", 	"YEAR min. AC Voltage", 	"~Volt", 540); }
		if($this->ReadPropertyBoolean("cb_Year_DcVmax")) 	{ $this->RegisterVariableFloat("year_DcVmax", 	"YEAR max. DC Voltage", 	"~Volt", 550); }
		if($this->ReadPropertyBoolean("cb_Year_oHours")) 	{ $this->RegisterVariableFloat("year_oHours", 	"YEAR Operating [years]", 		 "", 560); }
		
		if($this->ReadPropertyBoolean("cb_Total_Energy")) 	{ $this->RegisterVariableFloat("total_Energy", 	"TOTAL Energy", 			"~Electricity", 600); }
		if($this->ReadPropertyBoolean("cb_Total_Yield")) 	{ $this->RegisterVariableFloat("total_Yield", 	"TOTAL Yield", 	 			"~Euro", 610); }
		if($this->ReadPropertyBoolean("cb_Total_Pmax")) 	{ $this->RegisterVariableFloat("total_Pmax", 	"TOTAL max. Power", 	 	"~Watt", 620); }
		if($this->ReadPropertyBoolean("cb_Total_AcVmax")) 	{ $this->RegisterVariableFloat("total_AcVmax", 	"TOTAL max. AC Voltage", 	"~Volt", 630); }
		if($this->ReadPropertyBoolean("cb_Total_AcVMin")) 	{ $this->RegisterVariableFloat("total_AcVmin", 	"TOTAL min. AC Voltage", 	"~Volt", 640); }
		if($this->ReadPropertyBoolean("cb_Total_DcVmax")) 	{ $this->RegisterVariableFloat("total_DcVmax", 	"TOTAL max. DC Voltage", 	"~Volt", 650); }
		if($this->ReadPropertyBoolean("cb_Total_oHours")) 	{ $this->RegisterVariableFloat("total_oHours",	"TOTAL Operating [years]", 		 "", 660); }

		if($this->ReadPropertyBoolean("cb_Total_EnergyCustWh")) 	{ $this->RegisterVariableFloat("total_Energy_CustWh", "TOTAL Energy Cust_Wh", "~Electricity.Wh", 601); }

		$this->RegisterVariableInteger("ERR_Nr", "Error Number", "", 680);
		$this->RegisterVariableString("ERR_Info", "Error Info", "", 681);
		$this->RegisterVariableInteger("ERR_Cnt", "Error Cnt", "", 682);

		$this->RegisterVariableInteger("requestCnt", "Request Cnt", "", 900);
		$this->RegisterVariableInteger("receiveCnt", "Receive Cnt", "", 910);
		$this->RegisterVariableInteger("updateSkipCnt", "Update Skip Cnt", "", 915);
		$this->RegisterVariableInteger("CrcErrorCnt", "CRC Error Cnt", "", 920);
		$this->RegisterVariableInteger("InconsistentDataCnt", "Inconsistent Data Cnt", "", 921);		
		$this->RegisterVariableInteger("ErrorCnt", "Error Cnt", "", 930);
		$this->RegisterVariableString("LastError", "Last Error", "", 931);
		$this->RegisterVariableInteger("instanzInactivCnt", "Instanz Inactiv Cnt", "", 940);
		$this->RegisterVariableFloat("lastProcessingTotalDuration", "Last Processing Duration [ms]", "", 950);	
		$this->RegisterVariableInteger("LastDataReceived", "Last Data Received", "~UnixTimestamp", 960);

		IPS_ApplyChanges($archivInstanzID);
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variables registered"); }
	}

	protected function RegisterProfiles() {

		if ( !IPS_VariableProfileExists('IPS_ModulConnectionState') ) {
			IPS_CreateVariableProfile('IPS_ModulConnectionState', 1 );
			IPS_SetVariableProfileText('IPS_ModulConnectionState', "", "" );
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 100, "[%d] Unknown", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 101, "[%d] wird erstellt", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 102, "[%d] aktiv", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 103, "[%d] wird gelöscht", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 104, "[%d] inaktiv", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 105, "[%d] wurde nicht erstellt", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 106, "[%d] fehlerhaft", "", -1);
			IPS_SetVariableProfileAssociation ('IPS_ModulConnectionState', 200, "[%d] Unknown", "", -1);
		} 
	
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variable Profiles registered"); }			
	}


	public function Send(string $Text) {
		if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->String2Hex($Text)); }
		$Text = utf8_encode($Text);		
		SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1);  

		$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, "no");
		$this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
		
		$SendOk = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', "Buffer" => $Text]));
		//if ($SendOk) {	}		
	}


	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		$dataBuffer = utf8_decode($data->Buffer);
		$receiveBuffer = $this->GetBuffer(self::BUFFER_RECEIVED_DATA);
		if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ . "_dataBuffer_old",  $this->String2Hex($receiveBuffer)); }
		$receiveBuffer .= $dataBuffer;
		if($this->logLevel >= LogLevel::COMMUNICATION ) { $this->AddLog(__FUNCTION__ . "_dataBuffer",  $this->String2Hex($receiveBuffer)); }

		//$rchecksum = ord( substr( $receiveBuffer, strlen( $receiveBuffer ) - 1, 1  ) );

		$rpacketLenIST = strlen($receiveBuffer);
		if($rpacketLenIST <= 6) {
			if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ , "receiveBuffer <= 6" ); }
			$this->SetBuffer(self::BUFFER_RECEIVED_DATA, $receiveBuffer);
		} else if( ord( substr( $receiveBuffer, 0, 3 ) ) != ord( "\x80\x80\x80" ) ) {
			if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ , "no STARTSEQUENCE > skip ReceivedData ..." ); }
			$this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
		} else {
			$rpacketLenBYTE = ord( substr( $receiveBuffer, 3, 1 ) );
			$rpacketLenSOLL = $rpacketLenBYTE + 8;  // Länge | Gerät | Number | Befehl | dATa | CRC

			if($rpacketLenIST < $rpacketLenSOLL) {
				if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ , sprintf("$rpacketLenIST {%d} < $rpacketLenSOLL {%d}", $rpacketLenIST, $rpacketLenSOLL) ); }
				$this->SetBuffer(self::BUFFER_RECEIVED_DATA, $receiveBuffer);
			} else if($rpacketLenIST == $rpacketLenSOLL) {
				if($this->logLevel >= LogLevel::COMMUNICATION ) { $this->AddLog(__FUNCTION__ . "_dataAvailable",  $this->String2Hex($receiveBuffer)); }
				$this->SetBuffer(self::BUFFER_RECEIVED_DATA, $receiveBuffer);
				$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, "yes");
			} else if($rpacketLenIST > $rpacketLenSOLL) {
				if($this->logLevel >= LogLevel::COMMUNICATION ) { $this->AddLog(__FUNCTION__ . "_dataOverflow",  $this->String2Hex($receiveBuffer)); }
				$this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
				$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, "no");	
				//$this->HandleReceivedData($receiveBuffer);									
			} else {
				$this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
				$logMsg = sprintf("WARN :: rpacketLenByte is: %d > rpacketLenSOLL is: %d | rpacketLenIST: %d  {%s}" , $rpacketLenBYTE, $rpacketLenSOLL, $rpacketLenIST, $this->String2Hex($receiveBuffer));
				SetValue($this->GetIDForIdent("ErrorCnt"), GetValue($this->GetIDForIdent("ErrorCnt")) + 1);
				SetValue($this->GetIDForIdent("LastError"), $logMsg);
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, $logMsg); }					
			}				
		}
	}


	/*
	protected function HandleReceivedData(string $receiveBuffer) {

		$rpacketsArr = explode("\x80\x80\x80", $receiveBuffer);
		if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ . "_rpacketsArr", print_r($rpacketsArr, true)); }

		foreach($rpacketsArr as $rpacket) {
			$rpacketArr = unpack('C*', $rpacket);
			$rpacketLenIST = count($rpacketArr);
			if($rpacketLenIST >= 6) {

				$rpacketLenByte = $rpacketArr[1];
				$rpacketLenSOLL = $rpacketLenByte + 5;  // Länge | Gerät | Number | Befehl | dATa | CRC

				if($rpacketLenIST == $rpacketLenSOLL) {
					array_unshift($rpacketArr, 0x80);
					array_unshift($rpacketArr, 0x80);
					array_unshift($rpacketArr, 0x80);
					$this->ParsePacket($rpacketArr);
				} else {
					$logMsg = sprintf("WARN :: rpacketLenByte is: %d > rpacketLenSOLL is: %d | rpacketLenIST: %d  {%s}" , $rpacketLenByte, $rpacketLenSOLL, $rpacketLenIST, $this->ByteArr2HexStr($rpacketArr));
					SetValue($this->GetIDForIdent("ErrorCnt"), GetValue($this->GetIDForIdent("ErrorCnt")) + 1);
					SetValue($this->GetIDForIdent("LastError"), $logMsg);
					if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, $logMsg); }
				}
			}
		}
	}
	*/

	/*
	not used
	public function ReceiveData_v1($JSONString) {
		$data = json_decode($JSONString);
		$dataBuffer = utf8_decode($data->Buffer);
		
		if($this->logLevel >= LogLevel::COMMUNICATION ) { $this->AddLog(__FUNCTION__ . "_dataBuffer: ",  $this->String2Hex($dataBuffer)); }
		//if($this->logLevel >= LogLevel::TRACE	) { $this->AddLog(__FUNCTION__, "receiveBuffer: " . $this->String2Hex($receiveBuffer)); }
		SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
		SetValue($this->GetIDForIdent("LastDataReceived"), time()); 
					
		$rpacketsArr = explode("\x80\x80\x80", $dataBuffer);

		if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ . "_rpacketsArr", print_r($rpacketsArr, true)); }

		foreach($rpacketsArr as $rpacket) {
			$rpacketArr = unpack('C*', $rpacket);
			$rpacketLenIST = count($rpacketArr);
			if($rpacketLenIST >= 6) {

				$rpacketLenByte = $rpacketArr[1];
				$rpacketLenSOLL = $rpacketLenByte + 5;  // Länge | Gerät | Number | Befehl | dATa | CRC

				if($rpacketLenIST == $rpacketLenSOLL) {
					$this->ParsePacket($rpacketArr);
					$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, true);
				} else {
					$logMsg = sprintf("WARN :: rpacketLenByte is: %d > rpacketLenSOLL is: %d | rpacketLenIST: %d  {%s}" , $rpacketLenByte, $rpacketLenSOLL, $rpacketLenIST, $this->ByteArr2HexStr($rpacketArr));
					SetValue($this->GetIDForIdent("ErrorCnt"), GetValue($this->GetIDForIdent("ErrorCnt")) + 1);
					SetValue($this->GetIDForIdent("LastError"), $logMsg);
					if($this->logLevel >= LogLevel::WARN) { 
						$this->AddLog(__FUNCTION__, $logMsg); 
					}
				}
			}
		}
		return true;
	}
	*/

	private function WaitForResponse(int $timeout) {
		for ($i = 0; $i < $timeout / 20; $i++) {
			$event = $this->GetBuffer(self::BUFFER_RECEIVE_EVENT);
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("[%d] Receive Event > %s", $i, $event)); }				
			if ($event == "yes") {
				$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, "no");
				return true;
			} else {
				IPS_Sleep(20);
			}
		}
		return false;
	}


	protected function startsWith(string $haystack, string $needle) {
		return strpos($haystack, $needle) === 0;
	}

	protected function String2Hex(string $string) {
		$hex='';
		for ($i=0; $i < strlen($string); $i++){
			$hex .= sprintf("0x%02X : ", ord($string[$i]));
		}
		return trim($hex);
	}


	protected function ByteArr2String(array $byteArr) {
		return implode(array_map("chr", $byteArr));
	}

	protected function ByteArr2HexStr(array $arr) {
		$hex_str = "";
		foreach ($arr as $byte) {
			$hex_str .= sprintf("0x%02X | ", $byte);
			//$hex_str .= sprintf("0x%02X [%d] - ", $byte, $byte);
		}
		return $hex_str;
	}


	private function byte2hex( $value ){
		$h = dechex( ( $value >> 4 ) & 0x0F );
		$l = dechex( $value & 0x0F );
		return "0x$h$l";
		}

	
	protected function getUserDefinedConstantName($constantNumber) {
		$constants = get_defined_constants(true);
		if (isset($constants['user'])) {
			foreach($constants['user'] as $key => $value) {
				if($value==$constantNumber) {
					return $key;
				}
			}
		}
		return "n.a.";
	}

	protected function CalcDuration_ms(float $timeStart) {
		$duration =  microtime(true)- $timeStart;
		return round($duration*1000, 2);
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