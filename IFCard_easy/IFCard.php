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
const AC_FREQUENCY           = 0x16;

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
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__,$this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    }

    protected function Request_DeviceTyp() {
        $packetArr = $this->BuildPacket( IFC_DEVICETYPE, 1, 1 );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
        $this->Send();
    }  

    protected function Request_ActivInverterNumbers() {
        //$packetArr = $this->BuildPacket( IFC_ACTIVINVERTERNUMBER, 0, 0 );
        //if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        //$this->SendPacketArr($packetArr);
        return 1;
    } 


    protected function Request_Power() {
        $packetArr = $this->BuildPacket( WR_POWER, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    } 
    protected function Request_DcVoltage() {
        $packetArr = $this->BuildPacket( DC_VOLTAGE, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    } 
    protected function Request_DcCurrent() {
        $packetArr = $this->BuildPacket( DC_CURRENT, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    }     

    protected function Request_AcVoltage() {
        $packetArr = $this->BuildPacket( AC_VOLTAGE, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    } 
    protected function Request_AcCurrent() {
        $packetArr = $this->BuildPacket( AC_CURRENT, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    } 
    protected function Request_AcFrequency() {
        $packetArr = $this->BuildPacket( AC_FREQUENCY, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $this->ByteArr2HexStr($packetArr)); }
        $this->SendPacketArr($packetArr);
    }    


    protected function UpdateInverterData(int $command, string $comandTxt) {
        $packetArr = $this->BuildPacket( $command, $this->deviceOption, $this->IGNr );
        if($this->logLevel >= LogLevel::COMMUNICATION) { 
            $logMsg =  sprintf("Request :: %s [0x%02X] > %s", $comandTxt, $command, $this->ByteArr2HexStr($packetArr));
            $this->AddLog(__FUNCTION__, $logMsg); 
        }
        $this->SendPacketArr($packetArr);  
        
        IPS_Sleep(10);

        /*
        if ($this->WaitForResponse(800)) { 
            $buffer = $this->GetBuffer(self::BUFFER_RECEIVED_DATA);
            $this->SetBuffer(self::BUFFER_RECEIVED_DATA, "");
            $bufferArr = unpack('C*', $buffer);

            if($this->logLevel >= LogLevel::COMMUNICATION) { 
                $logMsg = sprintf("Receive :: %s [0x%02X] > %s", $comandTxt, $command, $this->ByteArr2HexStr($bufferArr));
                $this->AddLog(__FUNCTION__, $logMsg); 
            }
        } else {

            $buffArr = $this->GetBufferList();
            $this->AddLog(__FUNCTION__ . "..", print_r($buffArr, true));


            if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("Receive :: WARN Receive Timeout on '%s'", $comandTxt)); }
        }        
        */
    }

     
    protected function BuildPacket(int $command, $deviceOption, $igNr) {
       // Packet: Startsequenz - Länge - Gerät/Option - Nummer - Befehl - CheckSumme
       $dataArr = [0x00, $deviceOption, $igNr, $command];
       $checksum = $this->CalcCRC($dataArr);
      
       if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("START: 0x80 0x80 0x80 | DEVICE: 0x%02X | NUMBER: 0x%02X | COMMAND: 0x%02X | CRC: 0x%02X", $deviceOption, $igNr, $command, $checksum)); }

       array_unshift($dataArr, 0x80, 0x80, 0x80);
       array_push($dataArr, $checksum);     
       return $dataArr;
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