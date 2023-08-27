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

    
    protected function Request_InterfaceInfo() {
        $packetArr = $this->BuildPacket( IFC_INFO, 0, 0 );
        return $this->RequestData($packetArr, IFC_INFO, "IFC_INFO");
        //if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__,$this->ByteArr2HexStr($packetArr)); }
        //$this->SendPacketArr($packetArr);
    }

    protected function Request_DeviceTyp() {
        $packetArr = $this->BuildPacket( IFC_DEVICETYPE, 1, 1 );
        return $this->RequestData($packetArr, IFC_DEVICETYPE, "IFC_DEVICETYPE");
        //if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        //$this->SendPacketArr($packetArr); 
    }  

    protected function Request_ActivInverterNumbers() {
        $packetArr = $this->BuildPacket( IFC_ACTIVINVERTERNUMBER, 0, 0 );
        return $this->RequestData($packetArr, IFC_ACTIVINVERTERNUMBER, "IFC_ACTIVINVERTERNUMBER");
        //if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        //$this->SendPacketArr($packetArr);
    } 


    protected function UpdateInverterData(int $command, string $comandTxt) {
        $packetArr = $this->BuildPacket( $command, $this->deviceOption, $this->IGNr );      
        return $this->RequestData($packetArr, $command, $comandTxt);
    }

    protected function RequestData(array $packetArr, int $command, string $comandTxt) {
        if($this->logLevel >= LogLevel::DEBUG) { 
            $logMsg =  sprintf("%s [command: 0x%02X] {%s}", $comandTxt, $command, $this->ByteArr2HexStr($packetArr));
            $this->AddLog(__FUNCTION__, $logMsg); 
        }
        $this->SendPacketArr($packetArr);  
        
        IPS_Sleep(10);

        if ($this->WaitForResponse(800)) { 

			$receiveBuffer = $this->GetBuffer(self::BUFFER_RECEIVED_DATA);
            if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Receive DONE '%s' {%s}", $comandTxt, $this->String2Hex($receiveBuffer))); }

            SetValue($this->GetIDForIdent("receiveCnt"), GetValue($this->GetIDForIdent("receiveCnt")) + 1);  											
            SetValue($this->GetIDForIdent("LastDataReceived"), time()); 

            $rpacketArr = unpack('C*', $receiveBuffer);
            return $this->ParsePacket($rpacketArr);


        } else {
            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive :: WARN Receive Timeout on '%s'", $comandTxt)); }
            return false;
        }
    }

    /*
    // not Used
    protected function RequestData_v1(array $packetArr, int $command, string $comandTxt) {
        if($this->logLevel >= LogLevel::COMMUNICATION) { 
            $logMsg =  sprintf("Request :: %s [0x%02X] > %s", $comandTxt, $command, $this->ByteArr2HexStr($packetArr));
            $this->AddLog(__FUNCTION__, $logMsg); 
        }
        $this->SendPacketArr($packetArr);  
        
        IPS_Sleep(250);

        if ($this->WaitForResponse(800)) { 
            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive DONE for '%s'", $comandTxt)); }

        } else {
            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive :: WARN Receive Timeout on '%s'", $comandTxt)); }
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



    protected function ParsePacket(array $rpacketArr) {
        if($this->logLevel >= LogLevel::TRACE ) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($rpacketArr)); }

        $byteOffset = 3;
        $returnValue = false;
       
        $rpacketCommand = $rpacketArr[4 + $byteOffset];
        switch( $rpacketCommand )  {

            case 0x0E:

                $errSource = $rpacketArr[5 + $byteOffset];
                $errNr = $rpacketArr[6 + $byteOffset];
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
                $returnValue = false;
                break;

            case IFC_INFO:
                $ifc_Type = $rpacketArr[5 + $byteOffset];
                if($ifc_Type == 2) { $ifc_Type = "RS232 Interface Card easy"; } else { $ifc_Type = $this->byte2hex($ifc_Type); }
                $ifc_version_major = $rpacketArr[6 + $byteOffset];
                $ifc_version_minor = $rpacketArr[7 + $byteOffset];
                $ifc_version_release = $rpacketArr[8 + $byteOffset];
                $IFCinfo = sprintf("%s v%d.%d.%d", $ifc_Type, $ifc_version_major, $ifc_version_minor, $ifc_version_release); 
                SetValue($this->GetIDForIdent("IFC_Info"), $IFCinfo);
                if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_INFO: %s {%s}", $IFCinfo, $this->ByteArr2HexStr($rpacketArr))); }
                $returnValue = $IFCinfo;
                break;
            case IFC_DEVICETYPE:
                $device = "n.a.";
                $deviceType = $rpacketArr[5 + $byteOffset];
                if($deviceType == 0xfd) { $device = "Fronius IG 20"; }
                SetValue( $this->GetIDForIdent("IFC_DeviceType"), sprintf("%s  [0x%02X]", $device, $deviceType) ); 
                if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_DEVICETYPE: %s {%s}", $device, $this->ByteArr2HexStr($rpacketArr))); }
                $returnValue = $device;
                break;
            case IFC_ACTIVINVERTERNUMBER:
                    $activInvNumbers = 0;
                    $dataLenIST = $rpacketArr[1 + $byteOffset];
                    if ($dataLenIST == 1) {
                        $activInvNumbers = $rpacketArr[5 + $byteOffset];
                    }
                    SetValue($this->GetIDForIdent("IFC_ActivInverterCnt"), $activInvNumbers ); 
                    if($this->logLevel >= LogLevel::DEBUG ) { $this->AddLog(__FUNCTION__, sprintf("IFC_ACTIVINVERTERNUMBER: %d {%s}", $activInvNumbers, $this->ByteArr2HexStr($rpacketArr))); }						
                    $returnValue = $activInvNumbers;
                    break;															
            
            case ENERGY_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_TOTAL", "total_E", 0.001 );
                break;
            case ENERGY_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_DAY", "day_E", 0.001 );
                break;
            case ENERGY_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "ENERGY_YEAR", "year_E", 0.001 );
                break;

            case WR_POWER:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "WR_POWER", "P" );
                break;
            case DC_VOLTAGE:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "DC_VOLTAGE", "DcV" );
                break;
            case DC_CURRENT:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "DC_CURRENT", "DcA" );
                break;

            case AC_VOLTAGE:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_VOLTAGE", "AcV" );
                break;
            case AC_CURRENT:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_CURRENT", "AcA" );
                break;
            case AC_FREQUENCY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "AC_FREQUENCY", "AcF" );
                break;


            case YIELD_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_DAY", "day_Yield" );
                break;
            case MAX_POWER_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_DAY", "day_Pmax" );
                break;
            case MAX_AC_VOLTAGE_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_DAY", "day_AcVmax" );
                break;
            case MIN_AC_VOLTAGE_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_DAY", "day_AcVmin" );
                break;
            case MAX_DC_VOLTAGE_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_DAY", "day_DcVmax" );
                break;
            case OPERATING_HOURS_DAY:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_DAY", "day_oHours", 60, -3600 );
                break;


            case YIELD_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_YEAR", "year_Yield" );
                break;
            case MAX_POWER_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_YEAR", "year_Pmax" );
                break;
            case MAX_AC_VOLTAGE_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_YEAR", "year_AcVmax" );
                break;
            case MIN_AC_VOLTAGE_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_YEAR", "year_AcVmin" );
                break;
            case MAX_DC_VOLTAGE_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_YEAR", "year_DcVmax" );
                break;
            case OPERATING_HOURS_YEAR:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_YEAR", "year_oHours", 60, -3600 );
                break;

            case YIELD_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "YIELD_TOTAL", "total_Yield" );
                break;
            case MAX_POWER_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_POWER_TOTAL", "total_Pmax" );
                break;
            case MAX_AC_VOLTAGE_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_AC_VOLTAGE_TOTAL", "total_AcVmax" );
                break;
            case MIN_AC_VOLTAGE_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MIN_AC_VOLTAGE_TOTAL", "total_AcVmin" );
                break;
            case MAX_DC_VOLTAGE_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "MAX_DC_VOLTAGE_TOTAL", "total_DcVmax" );
                break;
            case OPERATING_HOURS_TOTAL:
                $returnValue = $this->ExtractSaveMeteringValue( $rpacketArr, "OPERATING_HOURS_TOTAL", "total_oHours", 60, -3600 );
                break;					

            default:

                $errInfo = sprintf("Received Packet not evaluated > Command BYTE: 0x%02X", $rpacketCommand);
                SetValue($this->GetIDForIdent("ERR_Nr"), 98);
                SetValue($this->GetIDForIdent("ERR_Info"), $errInfo);
                $varIdErrCnt = $this->GetIDForIdent("ERR_Cnt");
                SetValueInteger($varIdErrCnt, GetValueInteger($varIdErrCnt) + 1);
                if($this->logLevel >= LogLevel::WARN ) { $this->AddLog(__FUNCTION__ . "_WARN", $errInfo); }
                $returnValue = false;
                break;
        }
        return $returnValue;
    }


    protected function ExtractSaveMeteringValue(array $rpacketArr, string $command, string $varIdent, float $faktor=1, float $offset=0 ) {	 
        $value = 0;
        $byteOffset = 3;
        $byte1 = $rpacketArr[5 + $byteOffset];
        $byte2 = $rpacketArr[6 + $byteOffset];
        $exp = $rpacketArr[7 + $byteOffset];

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