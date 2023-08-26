<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/FRONIUS_COMMON.php'; 
include_once("IFCard.php");

	class IFCard_easy extends IPSModule	{

		//use FRONIUS_COMMON;
		use IFCard;

		const CATEGORY_Data = "Data";

		private $logLevel = 3;
		private $logCnt = 0;
		private $parentRootId;
		private $archivInstanzID;	
		private $deviceOption;			// 0=IFcard | 1=Wechselrichter
		private $IGNr;					// laut Einstellung am Wechselrichter

		const BUFFER_RECEIVE_EVENT = "receiveEvent";
		const BUFFER_RECEIVED_DATA = "receiveBuffer";

		public function __construct(int $InstanceID) {
		
			parent::__construct($InstanceID);		// Diese Zeile nicht löschen

			$this->archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
			$this->parentRootId = IPS_GetParent($this->InstanceID);

			$currentStatus = $this->GetStatus();
			if($currentStatus == 102) {				//Instanz ist aktiv
				$this->logLevel = $this->ReadPropertyInteger("LogLevel");

				$this->deviceOption = 1;
				$this->IGNr = $this->ReadPropertyInteger("IG_Nr");


				if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel), 0); }


			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Current Status is '%s'", $currentStatus), 0); }	
			}
		}


		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RequireParent('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');

			$this->RegisterPropertyBoolean('EnableAutoUpdate', 	 false);
			$this->RegisterPropertyInteger('AutoUpdateInterval', 15);	
			$this->RegisterPropertyInteger('IG_Nr', 			 1);	
			$this->RegisterPropertyInteger('LogLevel', 			 3);

			$this->RegisterPropertyBoolean('cb_IFC_Info', 		true);
			$this->RegisterPropertyBoolean('cb_IFC_DeviceTyp', 	false);

			$this->RegisterPropertyBoolean('cb_Power', 			true);
			$this->RegisterPropertyBoolean('cb_DcV', 			true);
			$this->RegisterPropertyBoolean('cb_DcA', 			true);

			$this->RegisterPropertyBoolean('cb_AcV', 			true);
			$this->RegisterPropertyBoolean('cb_AcA', 			true);
			$this->RegisterPropertyBoolean('cb_AcF', 			true);

			$this->RegisterPropertyBoolean('cb_Day_Energy', 	false);
			$this->RegisterPropertyBoolean('cb_Day_Yield', 		false);
			$this->RegisterPropertyBoolean('cb_Day_Pmax', 		false);
			$this->RegisterPropertyBoolean('cb_Day_AcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Day_AcVMin', 	false);
			$this->RegisterPropertyBoolean('cb_Day_DcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Day_oHours',	 	false);
					
			$this->RegisterPropertyBoolean('cb_Year_Energy', 	false);
			$this->RegisterPropertyBoolean('cb_Year_Yield',		false);
			$this->RegisterPropertyBoolean('cb_Year_Pmax', 		false);
			$this->RegisterPropertyBoolean('cb_Year_AcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Year_AcVMin', 	false);
			$this->RegisterPropertyBoolean('cb_Year_DcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Year_oHours',	false);

			$this->RegisterPropertyBoolean('cb_Total_Energy', 	false);
			$this->RegisterPropertyBoolean('cb_Total_Yield', 	false);
			$this->RegisterPropertyBoolean('cb_Total_Pmax', 	false);
			$this->RegisterPropertyBoolean('cb_Total_AcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Total_AcVMin', 	false);
			$this->RegisterPropertyBoolean('cb_Total_DcVmax', 	false);
			$this->RegisterPropertyBoolean('cb_Total_oHours',	false);			
	
			$this->RegisterTimer('Timer_AutoUpdate', 0, 'IFC_Timer_AutoUpdate($_IPS[\'TARGET\']);');

		}

		public function Destroy() {
			$this->SetUpdateInterval(0);		//Stop Auto-Update Timer
			parent::Destroy();					//Never delete this line!
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();

			$this->logLevel = $this->ReadPropertyInteger("LogLevel");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel), 0); }

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
			} else if ($updateInterval < 5) { 
               	$updateInterval = 5; 
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval), 0); }	
			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %ss", $updateInterval), 0); }
			}
			$this->SetTimerInterval("Timer_AutoUpdate", $updateInterval * 1000);	
		}

		public function Timer_AutoUpdate() {
		
			$masterOnOff = GetValue($this->GetIDForIdent("masterOnOff"));
			if($masterOnOff) {
            	if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "called ...", 0); }
				$this->Update(); 
			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("AutoUpate CANCELED > Master Swich is OFF > Connection State '%s' ...", $this->GetConnectionState()), 0); }
			}			
		}


		public function GetConnectionState() {
			$connectionState = -1;
			$conID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
			if($conID > 0) {
				$connectionState = IPS_GetInstance($conID)['InstanceStatus'];
			} else {
				$connectionState = 0;
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s [%s]' has NO Gateway/Connection [ConnectionID=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $conID), 0); }
			}
			SetValue($this->GetIDForIdent("connectionState"), $connectionState);
			return $connectionState;
		}

		public function Update() {

			$currentStatus = $this->GetStatus();
			$connectionState = $this->GetConnectionState();
			SetValue($this->GetIDForIdent("connectionState"), $connectionState);

			if($currentStatus == 102) {		
				if($connectionState == 102) {
	
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Request/Update Inverter Data via Interface Card ...", 0); }

					$activInverters = $this->Request_ActivInverterNumbers();
					if($activInverters > 0) {
						if($this->ReadPropertyBoolean("cb_IFC_Info")) 		{ $this->Request_InterfaceInfo(); }
						if($this->ReadPropertyBoolean("cb_IFC_DeviceTyp")) 	{ $this->Request_DeviceTyp(); }
		
						//if($this->ReadPropertyBoolean("cb_Power")) 		{ $this->Request_Power(); }
						//if($this->ReadPropertyBoolean("cb_DcV")) 			{ $this->Request_DcVoltage(); }
						//if($this->ReadPropertyBoolean("cb_DcA")) 			{ $this->Request_DcCurrent(); }
		
						//if($this->ReadPropertyBoolean("cb_AcV")) 			{ $this->Request_AcVoltage(); }
						//if($this->ReadPropertyBoolean("cb_AcA")) 			{ $this->Request_AcCurrent(); }
						//if($this->ReadPropertyBoolean("cb_AcF")) 			{ $this->Request_AcFrequency(); }	
						
						if($this->ReadPropertyBoolean("cb_Power")) 			{ $this->UpdateInverterData(WR_POWER, "WR_POWER"); }
						if($this->ReadPropertyBoolean("cb_DcV")) 			{ $this->UpdateInverterData(DC_VOLTAGE, "DC_VOLTAGE"); }
						if($this->ReadPropertyBoolean("cb_DcA")) 			{ $this->UpdateInverterData(DC_CURRENT, "DC_CURRENT"); }
						
						if($this->ReadPropertyBoolean("cb_AcV")) 			{ $this->UpdateInverterData(AC_VOLTAGE, "AC_VOLTAGE"); }
						if($this->ReadPropertyBoolean("cb_AcA")) 			{ $this->UpdateInverterData(AC_CURRENT, "AC_CURRENT"); }
						if($this->ReadPropertyBoolean("cb_AcF")) 			{ $this->UpdateInverterData(AC_FREQUENCY, "AC_FREQUENCY"); }							

					}


				} else {
					if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Connection NOT activ [Status=%s]", $connectionState), 0); }
				}
			} else {
				SetValue($this->GetIDForIdent("instanzInactivCnt"), GetValue($this->GetIDForIdent("instanzInactivCnt")) + 1);
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Instanz '%s - [%s]' not activ [Status=%s]", $this->InstanceID, IPS_GetName($this->InstanceID), $currentStatus), 0); }

			}
		
		}

		public function ResetCounterVariables() {
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'RESET Counter Variables', 0); }
            
			SetValue($this->GetIDForIdent("connectionState"), 0); 
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

			/*
				$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_Data, $this->parentRootId);
				if($categoryId === false) {
					$categoryId = IPS_CreateCategory();
					IPS_SetIdent($categoryId, self::CATEGORY_Data);
					IPS_SetName($categoryId, self::CATEGORY_Data);
					IPS_SetParent($categoryId,  $this->parentRootId);
					IPS_SetPosition($categoryId, 100);
					if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Category '%s' created with ID '%s'", self::CATEGORY_Data, $categoryId), 0); }
				}

				$instanceIdent_IFCard = "Devices";
				$instanzName_IFCard = "Interface Card"
                $instanzId_IFCard = @IPS_GetObjectIDByIdent($instanceIdent_IFCard, $categoryId);
                if($instanzId_IFCard === false) {
                    $instanzId_IFCard = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                    IPS_SetIdent($instanzId_IFCard, $instanceIdent_IFCard);
                    IPS_SetName($instanzId_IFCard, $instanzName_IFCard);
                    IPS_SetParent($instanzId_IFCard,  $categoryId);
                    IPS_SetPosition($instanzId_IFCard, 100);
                    if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Instance '%s' created with ID '%s'", $instanzName_IFCard, $instanzId_IFCard), 0); }
                }
			*/
				


			$varId = $this->RegisterVariableBoolean("masterOnOff", "MASTER ON / OFF", "~Switch", 10);

			$scriptContent = '<? $varId=$_IPS["VARIABLE"]; SetValue($varId, $_IPS["VALUE"]); ?>';
			$scriptId = $this->RegisterScript("aktionsSkriptOnOff", "Aktionsskript On/Off", $scriptContent, 11);
			IPS_SetParent($scriptId, $this->InstanceID);
			IPS_SetHidden($scriptId, true);
			IPS_SetDisabled($scriptId, true);

			IPS_SetVariableCustomAction($varId, $scriptId);

			$varId = $this->RegisterVariableInteger("connectionState", "Connection STATE", "IPS_ModulConnectionState", 20);
			AC_SetLoggingStatus ($this->archivInstanzID, $varId, true);

			if($this->ReadPropertyBoolean("cb_IFC_Info")) { $this->RegisterVariableString("IFC_Info", "Interface", "", 100); }
			$this->RegisterVariableInteger("IFC_ActivInverterCnt", "Activ Inverters", "", 110);
			if($this->ReadPropertyBoolean("cb_IFC_DeviceTyp")) { $this->RegisterVariableString("IFC_DeviceType", "Device Type", "", 120); }

			if($this->ReadPropertyBoolean ("cb_Power")) 		{ $this->RegisterVariableFloat("P", 			"POWER", 					"~Watt", 200); }
			if($this->ReadPropertyBoolean ("cb_DcV")) 			{ $this->RegisterVariableFloat("DcV", 			"DC Voltage", 				"~Volt", 250); }
			if($this->ReadPropertyBoolean ("cb_DcA")) 			{ $this->RegisterVariableFloat("DcA", 			"DC Current", 				"~Ampere", 260); }

			if($this->ReadPropertyBoolean ("cb_AcV")) 			{ $this->RegisterVariableFloat("AcV", 			"AC Voltage", 				"~Volt", 300); }
			if($this->ReadPropertyBoolean ("cb_AcA")) 			{ $this->RegisterVariableFloat("AcA",			"AC Current", 				"~Ampere", 310); }
			if($this->ReadPropertyBoolean ("cb_AcF")) 			{ $this->RegisterVariableFloat("AcF", 			"AC Frequency", 			"~Hertz.50", 320); }

			if($this->ReadPropertyBoolean ("cb_Total_Energy")) 	{ $this->RegisterVariableFloat("total_E", 		"TOTAL Energy", 			"~Electricity", 600); }
			if($this->ReadPropertyBoolean ("cb_Day_Energy")) 	{ $this->RegisterVariableFloat("day_E", 		"DAY Energy", 				"~Electricity", 400); }
			if($this->ReadPropertyBoolean ("cb_Year_Energy")) 	{ $this->RegisterVariableFloat("year_E", 		"YEAR Energy",				"~Electricity", 500); }

			if($this->ReadPropertyBoolean ("cb_Day_Yield")) 	{ $this->RegisterVariableFloat("day_Yield", 	"DAY Yield", 	 			"", 	 410); }
			if($this->ReadPropertyBoolean ("cb_Day_Pmax")) 		{ $this->RegisterVariableFloat("day_Pmax", 		"DAY max. Power", 	 		"~Watt", 420); }
			if($this->ReadPropertyBoolean ("cb_Day_AcVmax")) 	{ $this->RegisterVariableFloat("day_AcVmax", 	"DAY max. AC Voltage", 		"~Volt", 430); }
			if($this->ReadPropertyBoolean ("cb_Day_AcVMin")) 	{ $this->RegisterVariableFloat("day_AcVmin", 	"DAY min. AC Voltage", 		"~Volt", 440); }
			if($this->ReadPropertyBoolean ("cb_Day_DcVmax")) 	{ $this->RegisterVariableFloat("day_DcVmax", 	"DAY max. DC Voltage", 		"~Volt", 450); }
			if($this->ReadPropertyBoolean ("cb_Day_oHours")) 	{ $this->RegisterVariableInteger("day_oHours", 	"DAY Operating Hours", 		"~UnixTimestampTime", 460); }

			if($this->ReadPropertyBoolean ("cb_Year_Yield")) 	{ $this->RegisterVariableFloat("year_Yield", 	"YEAR Yield", 	 			"", 	 510); }
			if($this->ReadPropertyBoolean ("cb_Year_Pmax")) 	{ $this->RegisterVariableFloat("year_Pmax", 	"YEAR max. Power", 	 		"~Watt", 520); }
			if($this->ReadPropertyBoolean ("cb_Year_AcVmax")) 	{ $this->RegisterVariableFloat("year_AcVmax", 	"YEAR max. AC Voltage", 	"~Volt", 530); }
			if($this->ReadPropertyBoolean ("cb_Year_AcVMin")) 	{ $this->RegisterVariableFloat("year_AcVmin", 	"YEAR min. AC Voltage", 	"~Volt", 540); }
			if($this->ReadPropertyBoolean ("cb_Year_DcVmax")) 	{ $this->RegisterVariableFloat("year_DcVmax", 	"YEAR max. DC Voltage", 	"~Volt", 550); }
			if($this->ReadPropertyBoolean ("cb_Year_oHours")) 	{ $this->RegisterVariableInteger("year_oHours", "YEAR Operating Hours", 	"~UnixTimestampTime", 560); }

			if($this->ReadPropertyBoolean ("cb_Total_Yield")) 	{ $this->RegisterVariableFloat("total_Yield", 	"TOTAL Yield", 	 			"", 	 610); }
			if($this->ReadPropertyBoolean ("cb_Total_Pmax")) 	{ $this->RegisterVariableFloat("total_Pmax", 	"TOTAL max. Power", 	 	"~Watt", 620); }
			if($this->ReadPropertyBoolean ("cb_Total_AcVmax")) 	{ $this->RegisterVariableFloat("total_AcVmax", 	"TOTAL max. AC Voltage", 	"~Volt", 630); }
			if($this->ReadPropertyBoolean ("cb_Total_AcVMin")) 	{ $this->RegisterVariableFloat("total_AcVmin", 	"TOTAL min. AC Voltage", 	"~Volt", 640); }
			if($this->ReadPropertyBoolean ("cb_Total_DcVmax")) 	{ $this->RegisterVariableFloat("total_DcVmax", 	"TOTAL max. DC Voltage", 	"~Volt", 650); }
			if($this->ReadPropertyBoolean ("cb_Total_oHours")) 	{ $this->RegisterVariableInteger("total_oHours","TOTAL Operating Hours", 	"~UnixTimestampTime", 660); }

			$this->RegisterVariableInteger("ERR_Nr", "Error Number", "", 680);
			$this->RegisterVariableString("ERR_Info", "Error Info", "", 681);
			$this->RegisterVariableInteger("ERR_Cnt", "Error Cnt", "", 682);

			$this->RegisterVariableInteger("requestCnt", "Request Cnt", "", 900);
			$this->RegisterVariableInteger("receiveCnt", "Receive Cnt", "", 910);
			$this->RegisterVariableInteger("updateSkipCnt", "Update Skip Cnt", "", 915);
			$this->RegisterVariableInteger("ErrorCnt", "Error Cnt", "", 920);
			$this->RegisterVariableString("LastError", "Last Error", "", 920);
			$this->RegisterVariableInteger("instanzInactivCnt", "Instanz Inactiv Cnt", "", 930);
			$this->RegisterVariableFloat("lastProcessingTotalDuration", "Last Processing Duration [ms]", "", 940);	
			$this->RegisterVariableInteger("LastDataReceived", "Last Data Received", "~UnixTimestamp", 950);

			IPS_ApplyChanges($this->archivInstanzID);
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variables registered", 0); }
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
		
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variable Profiles registered", 0); }			
		}



		protected function SendPacketArr(array $packetArr) {
			$packetStr = implode(array_map("chr", $packetArr));
			$this->Send($packetStr);
		}

		public function Send(string $Text) {
			if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, $this->String2Hex($Text), 0); }
			$Text = utf8_encode($Text);		
			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1);  

			$this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
			$SendOk = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', "Buffer" => $Text]));

			if ($SendOk) {
				
			}
			
		}


		public function ReceiveTestData(string $data) {
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $this->String2Hex($data), 0); }
			return true;
		}


		public function ReceiveData($JSONString) {
			$data = json_decode($JSONString);
			$dataBuffer = utf8_decode($data->Buffer);

			
			//$receiveBuffer = $this->GetBuffer(self::BUFFER_RECEIVED_DATA) . $dataBuffer;
			//$this->SetBuffer(self::BUFFER_RECEIVED_DATA, $receiveBuffer);

			if($this->logLevel >= LogLevel::COMMUNICATION ) { $this->AddLog(__FUNCTION__ . "_dataBuffer: ",  $this->String2Hex($dataBuffer)); }
			//if($this->logLevel >= LogLevel::TRACE	) { $this->AddLog(__FUNCTION__, "receiveBuffer: " . $this->String2Hex($receiveBuffer)); }
            SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
            SetValue($this->GetIDForIdent("LastDataReceived"), time()); 
			
			//$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, true);

			$rpacketsArr = explode("\x80\x80\x80", $dataBuffer);

			if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__ . "_rpacketsArr", print_r($rpacketsArr, true)); }

			foreach($rpacketsArr as $rpacket) {
				$rpacketArr = unpack('C*', $rpacket);
				$rpacketLenIST = count($rpacketArr);
				if($rpacketLenIST > 3) {

					//$this->AddLog(__FUNCTION__, "\r\n - rpacket: " . $this->String2Hex($rpacket));
					//$this->AddLog(__FUNCTION__, "   arrLen is: " . $rpacketLen);
					//$this->SendDebug("loop", print_r($rpacketArr, true), 1); 	

					$rpacketLenByte = 	$rpacketArr[1];
					$rpacketLenSOLL = $rpacketLenByte + 5;  // Länge | Gerät | Number | Befehl | dATa | CRC

					if($rpacketLenIST == $rpacketLenSOLL) {

						$this->ParsePacket($rpacketArr);

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


		private function ParsePacket(array $rpacketArr) {
			if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($rpacketArr)); }
		
			$rpacketCommand = $rpacketArr[4];
			switch( $rpacketCommand )  {

				case 0x0E:

					$errSource = $rpacketArr[5];
					$errNr = $rpacketArr[6];
					$errInfo = "n.a";
					switch($errNr) {
						case 0x01:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - unknown Command", $errSource, $errNr);
							break;
						case 0x03:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - wrong data structure", $errSource, $errNr);
							break;							
						case 0x04:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - queue full", $errSource, $errNr);							
							break;
						case 0x05:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - device/option not available", $errSource, $errNr);
							break;
						case 0x09:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - wrong command for device/option", $errSource, $errNr);
							break;							
						default:
							$errInfo = sprintf("SrcCommand: 0x%02X | Error: 0x%02X - unknown {case default}", $errSource, $errNr);
							break;
					}

					SetValue($this->GetIDForIdent("ERR_Nr"), $errNr);
					SetValue($this->GetIDForIdent("ERR_Info"), $errInfo);
					$varIdErrCnt = $this->GetIDForIdent("ERR_Cnt");
					SetValueInteger($varIdErrCnt, GetValueInteger($varIdErrCnt) + 1);

					if($this->logLevel >= LogLevel::ERROR ) { $this->AddLog(__FUNCTION__ . "ERR", sprintf("Error Received :: SrcCommand: 0x%02X | ErrorNr: %d", $errSource, $errNr)); }
					break;

				case IFC_INFO:
					$ifc_Type = $rpacketArr[5];
					if($ifc_Type == 2) { $ifc_Type = "RS232 Interface Card easy"; } else { $ifc_Type = $this->byte2hex($ifc_Type); }
					$ifc_version_major = $rpacketArr[6];
					$ifc_version_minor = $rpacketArr[7];
					$ifc_version_release = $rpacketArr[8];
					$IFCinfo = sprintf("%s v%d.%d.%d", $ifc_Type, $ifc_version_major, $ifc_version_minor, $ifc_version_release); 
					SetValue($this->GetIDForIdent("IFC_Info"), $IFCinfo);
					if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_INFO: %s {%s}", $IFCinfo, $this->ByteArr2HexStr($rpacketArr))); }
					break;
				case IFC_DEVICETYPE:
					$device = "n.a.";
					$deviceType = $rpacketArr[5];
					if($deviceType == 0xfd) { $device = "Fronius IG 20"; }
					SetValue( $this->GetIDForIdent("IFC_DeviceType"), sprintf("%s  [0x%02X]", $device, $deviceType) ); 
					if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_DEVICETYPE: %s {%s}", $device, $this->ByteArr2HexStr($rpacketArr))); }
					break;
				case IFC_ACTIVINVERTERNUMBER:
				  		$activInvNumbers = $rpacketArr[5];
						SetValue($this->GetIDForIdent("IFC_ActivInverterCnt"), $activInvNumbers ); 
						if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_ACTIVINVERTERNUMBER: %d {%s}", $activInvNumbers, $this->ByteArr2HexStr($rpacketArr))); }						
						break;															
				
				case ENERGY_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_TOTAL", "total_E", 0.001 );
					break;
				case ENERGY_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_DAY", "day_E", 0.001 );
					break;
				case ENERGY_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_YEAR", "year_E", 0.001 );
					break;

				case WR_POWER:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "WR_POWER", "P" );
					break;
				case DC_VOLTAGE:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "DC_VOLTAGE", "DcV" );
					break;
				case DC_CURRENT:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "DC_CURRENT", "DcA" );
					break;

				case AC_VOLTAGE:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_VOLTAGE", "AcV" );
					break;
				case AC_CURRENT:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_CURRENT", "AcA" );
					break;
				case AC_FREQUENCY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_FREQUENCY", "AcF" );
					break;


				case YIELD_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_DAY", "day_Yield" );
					break;
				case MAX_POWER_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_DAY", "day_Pmax" );
					break;
				case MAX_AC_VOLTAGE_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_DAY", "day_AcVmax" );
					break;
				case MIN_AC_VOLTAGE_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_DAY", "day_AcVmin" );
					break;
				case MAX_DC_VOLTAGE_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_DAY", "day_DcVmax" );
				break;
				case OPERATING_HOURS_DAY:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_DAY", "day_oHours", 60, -3600 );
					break;


				case YIELD_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_YEAR", "year_Yield" );
					break;
				case MAX_POWER_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_YEAR", "year_Pmax" );
					break;
				case MAX_AC_VOLTAGE_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_YEAR", "year_AcVmax" );
					break;
				case MIN_AC_VOLTAGE_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_YEAR", "year_AcVmin" );
					break;
				case MAX_DC_VOLTAGE_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_YEAR", "year_DcVmax" );
				break;
				case OPERATING_HOURS_YEAR:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_YEAR", "year_oHours", 60, -3600 );
					break;

				case YIELD_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_TOTAL", "total_Yield" );
					break;
				case MAX_POWER_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_TOTAL", "total_Pmax" );
					break;
				case MAX_AC_VOLTAGE_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_TOTAL", "total_AcVmax" );
					break;
				case MIN_AC_VOLTAGE_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_TOTAL", "total_AcVmin" );
					break;
				case MAX_DC_VOLTAGE_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_TOTAL", "total_DcVmax" );
				break;
				case OPERATING_HOURS_TOTAL:
					$value = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_TOTAL", "total_oHours", 60, -3600 );
					break;					

				default:
					SetValue($this->GetIDForIdent("ERR_Nr"), 99);
					if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", sprintf("Received Packet not evaluated > Command BYTE: 0x%02X", $rpacketCommand)); }
					break;
			}

		}


		private function ExtractSaveMeteringValue(array $rpacketArr, string $command, string $varIdent, float $faktor=1, float $offset=0 ) {	 
			$value = 0;
			$byte1 = $rpacketArr[5];
			$byte2 = $rpacketArr[6];
			$exp = $rpacketArr[7];

			if ($exp >= 0b10000000) { $exp = $exp - 0xFF - 1; }
			$valueRaw =  $byte1 * 256 + $byte2;
			if ( $exp <= 10 && $exp >= -3 ) {
				$value =  $valueRaw * pow( 10, $exp );
				
				$value = $value * $faktor;
				$value = $value + $offset;

				if($this->logLevel >= LogLevel::DEBUG ) { 
					$logMsg = sprintf("%s: %.02f [Byte_1: %d | Byte_2: %d | ValueRaw: %d | Exp: %d] {%s}", $command, $value, $byte1, $byte2, $valueRaw, $exp, $this->ByteArr2HexStr($rpacketArr));
					$this->AddLog(__FUNCTION__, $logMsg);
				}
			 } else {
				$value = $valueRaw * -1;
				if($this->logLevel >= LogLevel::WARN ) {
					$logMsg = sprintf("%s !Over- or underflow of exponent Value! : %f [Byte_1: %d | Byte_2: %d | ValueRaw: %d | Exp: %d] {%s}", $command, $value, $byte1, $byte2, $valueRaw, $exp, $this->ByteArr2HexStr($rpacketArr));
					$this->AddLog(__FUNCTION__ . "_WARN", $logMsg); 
				}				
			 }

			$varId = @$this->GetIDForIdent($varIdent);
			if($varId !== false) {
				SetValue($varId, $value); 
			} else {
				if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", sprintf("VarIdent '%s' not found!", $varIdent), 0, true); }
			}
			 return $value;
		  }


		private function WaitForResponse(int $timeout)
		{
			for ($i = 0; $i < $timeout / 5; $i++) {
				if ($this->GetBuffer(self::BUFFER_RECEIVE_EVENT)) {
					$this->SetBuffer(self::BUFFER_RECEIVE_EVENT, false);
					return true;
				} else {
					IPS_Sleep(5);
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

		
		protected function CalcDuration_ms(float $timeStart) {
			$duration =  microtime(true)- $timeStart;
			return round($duration*1000, 2);
		}	

		protected function AddLog(string $name, string $daten, int $format=0, bool $enableIPSLogOutput=false) {

			$this->logCnt++;
			$logsender = sprintf("#%02d {%d} [%s] - %s", $this->logCnt, $_IPS['THREAD'], __CLASS__, $name);
			$this->SendDebug($logsender, $daten, $format); 	

			if($enableIPSLogOutput) {
				if($format == 0) {
					IPS_LogMessage($logsender, $daten);	
				} else {
					IPS_LogMessage($logsender, $this->String2Hex($daten));			
				}
			}
		}

	}