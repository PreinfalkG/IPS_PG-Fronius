<?

const STARTSEQUENCE = [0x80, 0x80, 0x80];

const IFC_INFO = 0x01;
const IFC_DEVICETYPE = 0x02;
const IFC_ACTIVINVERTERNUMBER  = 0x04;

const WR_POWER              = 0x10;

const ENERGY_TOTAL          = 0x11;
const ENERGY_DAY            = 0x12;
const ENERGY_YEAR           = 0x13;

const AC_CURRENT            = 0x14;
const AC_VOLTAGE            = 0x15;
const AC_FREQUENCY          = 0x16;

const DC_CURRENT            = 0x17;
const DC_VOLTAGE            = 0x18;

const YIELD_DAY             = 0x19;
const MAX_POWER_DAY         = 0x1a;
const MAX_AC_VOLTAGE_DAY    = 0x1b;
const MIN_AC_VOLTAGE_DAY    = 0x1c;
const MAX_DC_VOLTAGE_DAY    = 0x1d;
const OPERATING_HOURS_DAY   = 0x1e;

const YIELD_YEAR            = 0x1f;
const MAX_POWER_YEAR        = 0x20;
const MAX_AC_VOLTAGE_YEAR   = 0x21;
const MIN_AC_VOLTAGE_YEAR   = 0x22;
const MAX_DC_VOLTAGE_YEAR   = 0x23;
const OPERATING_HOURS_YEAR  = 0x24;

const YIELD_TOTAL           = 0x25;
const MAX_POWER_TOTAL       = 0x26;
const MAX_AC_VOLTAGE_TOTAL  = 0x27;
const MIN_AC_VOLTAGE_TOTAL  = 0x28;
const MAX_DC_VOLTAGE_TOTAL  = 0x29;
const OPERATING_HOURS_TOTAL = 0x2a;


trait IFCard {

    
    public function Request_InterfaceCardInfo(string $varIdent="") {
        $packetArr = $this->BuildPacket( IFC_INFO, 0, 0 );
        $ifc_Info = $this->RequestData($packetArr, IFC_INFO);
        if($varIdent != "") { $this->SaveVariable($varIdent, $ifc_Info); }
        return $ifc_Info;
    }
 
    public function Request_DeviceTyp(string $varIdent="") {
        $packetArr = $this->BuildPacket( IFC_DEVICETYPE, 1, 1 );
        $deviceTyp =  $this->RequestData($packetArr, IFC_DEVICETYPE);
        if($varIdent != "") { $this->SaveVariable($varIdent, $deviceTyp); }
        return $deviceTyp;        
    } 

    public function Request_ActivInverters(string $varIdent="") {
        $packetArr = $this->BuildPacket( IFC_ACTIVINVERTERNUMBER, 0, 0 );
        $activInverters = $this->RequestData($packetArr, IFC_ACTIVINVERTERNUMBER);
        if($varIdent != "") { $this->SaveVariable($varIdent, $activInverters); }
        return $activInverters;          
    } 


    public function RequestInverterData(int $command, string $varIdent="", int $deviceOption=1, int $IGNr=1) {
        $packetArr = $this->BuildPacket( $command, $this->deviceOption, $this->IGNr  );
        $value = $this->RequestData($packetArr, $command);
        if($varIdent != "") { $this->SaveVariable($varIdent, $value); }
        return $value;          
    } 



    protected function RequestData(array $packetArr, int $command) {
        if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("[0x%02X] {%s}", $command, $this->ByteArr2HexStr($packetArr))); }
        $this->SendPacketArr($packetArr);  
        //IPS_Sleep(10);

        if ($this->WaitForResponse(800)) { 

			$receiveBuffer = $this->GetBuffer(self::BUFFER_RECEIVED_DATA);
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("[0x%02X] Receive DONE {%s}", $command, $this->String2Hex($receiveBuffer))); }

            SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
            SetValue($this->GetIDForIdent("LastDataReceived"), time()); 

            $rpacketArr = unpack('C*', $receiveBuffer);
            return $this->ParsePacket($rpacketArr, $command);


        } else {
            if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("WARN Receive Timeout for '[0x%02X]'", $command)); }
            return null;
        }
    }

    /*
    // not Used
    protected function RequestData_v1(array $packetArr, int $command, string $commandTxt) {
        if($this->logLevel >= LogLevel::COMMUNICATION) { 
            $logMsg =  sprintf("Request :: %s [0x%02X] > %s", $commandTxt, $command, $this->ByteArr2HexStr($packetArr));
            $this->AddLog(__FUNCTION__, $logMsg); 
        }
        $this->SendPacketArr($packetArr);  
        
        IPS_Sleep(250);

        if ($this->WaitForResponse(800)) { 
            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive DONE for '%s'", $commandTxt)); }

        } else {
            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive :: WARN Receive Timeout on '%s'", $commandTxt)); }
        }
    }
    */
     
    protected function BuildPacket(int $command, $deviceOption, $igNr) {
       // Packet: Startsequenz - Länge - Gerät/Option - Nummer - Befehl - CheckSumme
       $dataArr = [0x00, $deviceOption, $igNr, $command];
       $checksum = $this->CalcCRC($dataArr);
      
       if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("START: 0x80 0x80 0x80 | DEVICE: 0x%02X | NUMBER: 0x%02X | COMMAND: 0x%02X | CRC: 0x%02X", $deviceOption, $igNr, $command, $checksum)); }

       array_unshift($dataArr, 0x80, 0x80, 0x80);
       array_push($dataArr, $checksum);     
       return $dataArr;
    }

    protected function SendPacketArr(array $packetArr) {
        $packetStr = implode(array_map("chr", $packetArr));
        $this->Send($packetStr);
    }

    protected function ParsePacket(array $rpacketArr, int $command) {
        if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__,  sprintf("[0x%02X] Parse {%s}", $command, $this->String2Hex($rpacketArr))); }

        $returnValue = null;      
        $rpacketCommand = $rpacketArr[7];

        if($rpacketCommand != $command) {
            if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__."_WARN",  sprintf("Not expected Command [0x%02X <> 0x%02X]", $command, $rpacketCommand)); }
        }


        
        $data4Check = array_slice($rpacketArr, 3, -1);
        $crcIST = $this->CalcCRC($data4Check);
        $crcSOLL = end($rpacketArr);

        if($crcIST != $crcSOLL) {
            if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__."_WARN",  sprintf("CRC Error [IST: 0x%02X <> SOLL: 0x%02X] {%s}", $crcIST, $crcSOLL, $this->ByteArr2HexStr($data4Check))); }  
            SetValue($this->GetIDForIdent("CrcErrorCnt"), GetValue($this->GetIDForIdent("CrcErrorCnt")) + 1);
        } else {

            switch( $rpacketCommand )  {

                case 0x0E:

                    $errSource = $rpacketArr[8];
                    $errNr = $rpacketArr[9];
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

                    if($this->logLevel >= LogLevel::ERROR ) { $this->AddLog(__FUNCTION__ . "ERR", sprintf("Error Received :: %s", $errInfo)); }
                    $returnValue = $errInfo;
                    break;

                case IFC_INFO:
                    $ifc_Type = $rpacketArr[8];
                    if($ifc_Type == 2) { $ifc_Type = "RS232 Interface Card easy"; } else { $ifc_Type = $this->byte2hex($ifc_Type); }
                    $ifc_version_major = $rpacketArr[9];
                    $ifc_version_minor = $rpacketArr[10];
                    $ifc_version_release = $rpacketArr[11];
                    $IFCinfo = sprintf("%s v%d.%d.%d", $ifc_Type, $ifc_version_major, $ifc_version_minor, $ifc_version_release); 
                    if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_INFO: %s {%s}", $IFCinfo, $this->ByteArr2HexStr($rpacketArr))); }
                    $returnValue = $IFCinfo;
                    break;
                case IFC_DEVICETYPE:
                    $deviceType = $rpacketArr[8];
                    if($deviceType == 0xfd) { $deviceType = "Fronius IG 20"; } else { $deviceType = sprintf("unkown [0x%02X]", $deviceType ); }
                    if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_DEVICETYPE: %s {%s}", $deviceType, $this->ByteArr2HexStr($rpacketArr))); }
                    $returnValue = $deviceType;
                    break;
                case IFC_ACTIVINVERTERNUMBER:
                        $activInvNumbers = 0;
                        $dataLenIST = $rpacketArr[4];
                        if ($dataLenIST == 1) {
                            $activInvNumbers = $rpacketArr[8];
                        }
                        if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_ACTIVINVERTERNUMBER: %d {%s}", $activInvNumbers, $this->ByteArr2HexStr($rpacketArr))); }						
                        $returnValue = $activInvNumbers;
                        break;															
                
                case WR_POWER:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case DC_VOLTAGE:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case DC_CURRENT:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;

                case AC_VOLTAGE:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case AC_CURRENT:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case AC_FREQUENCY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;

                case ENERGY_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 0.001 );
                    break;
                case YIELD_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_POWER_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_AC_VOLTAGE_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MIN_AC_VOLTAGE_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_DC_VOLTAGE_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case OPERATING_HOURS_DAY:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 60, -3600 );
                    break;

                case ENERGY_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 0.001 );
                    break;
                case YIELD_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_POWER_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_AC_VOLTAGE_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MIN_AC_VOLTAGE_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_DC_VOLTAGE_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case OPERATING_HOURS_YEAR:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 1 / (60*24*365) );
                    $returnValue = round($returnValue, 2);
                    break;

                case ENERGY_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 0.001 );
                    break;
                case YIELD_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_POWER_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_AC_VOLTAGE_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MIN_AC_VOLTAGE_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case MAX_DC_VOLTAGE_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr );
                    break;
                case OPERATING_HOURS_TOTAL:
                    $returnValue = $this->ExtractMeteringValue( $command, $rpacketArr, 1 / (60*24*365) );
                    $returnValue = round($returnValue, 2);
                    break;					

                default:
                    $errInfo = sprintf("Received Packet not evaluated > Command BYTE: 0x%02X", $rpacketCommand);
                    SetValue($this->GetIDForIdent("ERR_Nr"), 98);
                    SetValue($this->GetIDForIdent("ERR_Info"), $errInfo);
                    $varIdErrCnt = $this->GetIDForIdent("ERR_Cnt");
                    SetValueInteger($varIdErrCnt, GetValueInteger($varIdErrCnt) + 1);
                    if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", $errInfo); }
                    $returnValue = null;
                    break;
            }

        }
        return $returnValue;
    }

    protected function ExtractMeteringValue(int $command, array $rpacketArr, float $faktor=1, float $offset=0) {	 

        $value = 0;
        $byte1 = $rpacketArr[8];
        $byte2 = $rpacketArr[9];
        $exp = $rpacketArr[10];

        if ($exp >= 0b10000000) { $exp = $exp - 0xFF - 1; }
        $valueRaw =  $byte1 * 256 + $byte2;
        if ( $exp <= 10 && $exp >= -3 ) {
            $value =  $valueRaw * pow( 10, $exp );           
            $value = $value * $faktor;
            $value = $value + $offset;
            if($this->logLevel >= LogLevel::DEBUG ) { 
                $logMsg = sprintf("[0x%02X] %.02f [Byte_1: %d | Byte_2: %d | ValueRaw: %d | Exp: %d] {%s}", $command, $value, $byte1, $byte2, $valueRaw, $exp, $this->ByteArr2HexStr($rpacketArr));
                $this->AddLog(__FUNCTION__, $logMsg);
            }
         } else {
            $value = $valueRaw * -1;
            if($this->logLevel >= LogLevel::WARN ) {
                $logMsg = sprintf("[0x%02X] !Over- or underflow of exponent Value! : %f [Byte_1: %d | Byte_2: %d | ValueRaw: %d | Exp: %d] {%s}", $command, $value, $byte1, $byte2, $valueRaw, $exp, $this->ByteArr2HexStr($rpacketArr));
                $this->AddLog(__FUNCTION__ . "_WARN", $logMsg); 
            }				
         }
         return $value;
    }


    protected function SaveVariable(string $varIdent, $value) {
        if(!is_null($value)) {
            $varId = @$this->GetIDForIdent($varIdent);
            if($varId !== false) {
                SetValue($varId, $value); 
            } else {
                if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", sprintf("VarIdent '%s' not found!", $varIdent), 0, true); }
            }
        } else {
            if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", sprintf("Value for VarIdent '%s' is NULL!", $varIdent), 0, true); }
        }
    }

    protected function CalcCRC($byteArray) {
        $checksum = 0;
        for ($i = 0; $i < sizeof($byteArray); $i++) {
            $checksum += $byteArray[$i];
        }
        $checksum = $checksum & 0xFF;
        return $checksum;
    }

}

?>