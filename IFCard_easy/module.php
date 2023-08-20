<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/FRONIUS_COMMON.php'; 

	class IFCard_easy extends IPSModule	{

		//use FRONIUS_COMMON;

		const CATEGORY_Data = "DATA";

		private $logLevel = 3;
		private $parentRootId;
		private $archivInstanzID;		


		public function __construct(int $InstanceID) {
		
			parent::__construct($InstanceID);		// Diese Zeile nicht löschen

			$this->archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
			$this->parentRootId = IPS_GetParent($this->InstanceID);

			$currentStatus = $this->GetStatus();
			if($currentStatus == 102) {				//Instanz ist aktiv
				$this->logLevel = $this->ReadPropertyInteger("LogLevel");
				if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel), 0); }
			} else {
				if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Current Status is '%s'", $currentStatus), 0); }	
			}
		}


		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->RequireParent('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');

			$this->RegisterPropertyBoolean('EnableAutoUpdate', false);
			$this->RegisterPropertyInteger('AutoUpdateInterval', 15);	
			$this->RegisterPropertyInteger('LogLevel', 3);

			$this->RegisterPropertyBoolean('cb_GetPower_NOW', false);

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
            if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "called ...", 0); }
			if($this->ReadPropertyBoolean("cb_GetPower_NOW")) 	{ $this->Update(); }

		}


		public function Update() {

			SetValue($this->GetIDForIdent("requestCnt"), GetValue($this->GetIDForIdent("requestCnt")) + 1); 

			$COMMAND_GetVersion = "\x80\x80\x80\x00\x00\x00\x01\x01";
			//$COMMAND_GetVersion = "Hallo";
			$this->Send($COMMAND_GetVersion);

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

			$cb_GetPower_NOW = $this->ReadPropertyBoolean("cb_GetPower_NOW");	
			if($cb_GetPower_NOW) {
				$categoryId = @IPS_GetObjectIDByIdent(self::CATEGORY_Data, $this->parentRootId);
				if($categoryId === false) {
					$categoryId = IPS_CreateCategory();
					IPS_SetIdent($categoryId, self::CATEGORY_Data);
					IPS_SetName($categoryId, self::CATEGORY_Data);
					IPS_SetParent($categoryId,  $this->parentRootId);
					IPS_SetPosition($categoryId, 100);
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

			IPS_ApplyChanges($this->archivInstanzID);
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variables registered", 0); }
		}


		protected function RegisterProfiles() {

			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variable Profiles registered", 0); }			
		}


		protected function startsWith(string $haystack, string $needle) {
			return strpos($haystack, $needle) === 0;
		}

		protected function String2Hex(string $string) {
			$hex='';
			for ($i=0; $i < strlen($string); $i++){
				$hex .= sprintf("%02X", ord($string[$i])) . " ";
			}
			return trim($hex);
		}

		protected function ByteArr2HexStr(array $arr) {
			$hex_str = "";
			foreach ($arr as $byte) {
				$hex_str .= sprintf("%02X ", $byte);
			}
			return $hex_str;
		}

		protected function CalcDuration_ms(float $timeStart) {
			$duration =  microtime(true)- $timeStart;
			return round($duration*1000, 2);
		}	

		protected function AddLog(string $name, string $daten, int $format, bool $enableIPSLogOutput=false) {
			$this->SendDebug("[" . __CLASS__ . "] - " . $name, $daten, $format); 	
	
			if($enableIPSLogOutput) {
				if($format == 0) {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $daten);	
				} else {
					IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $this->String2Hex($daten));			
				}
			}
		}

		public function Send(string $Text) {
			$Text = utf8_encode($Text);
			$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', "Buffer" => $Text]));
		}

		public function ReceiveData($JSONString) {
			$data = json_decode($JSONString);
			$dataBuf = 	 utf8_decode($data->Buffer);
			$this->AddLog(__FUNCTION__, $dataBuf, 1);
			
            SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
            SetValue($this->GetIDForIdent("LastDataReceived"), time()); 

			PS_LogMessage('Device RECV', utf8_decode($data->Buffer));
		}
	}