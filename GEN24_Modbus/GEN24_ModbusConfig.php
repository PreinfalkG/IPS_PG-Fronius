<?

trait GEN24_ModbusConfig {

    protected function GetInverterRegisterConfig($invererModelKey) {

        if($invererModelKey == "IC124") {
            return $this->GetConfigArr_IC124();
        } else {
            return array();
        }

      
    }	

    protected function GetConfigArr_IC124() {
        //                               0      1       2       3                 4             5              6       7						 8       9			            10 		11		12                                                                              13
        //              Adress           Size   R/W     fCode   NAME              TYPE          UNIT           SF	  IPS-VAR TYPE	 		     Archiv  Profile 	            MULTIP. Round   Description-Short																Description-Long
        $IC124_ConfigArr = array();	                                                                                  				                                       
        $IC124_ConfigArr[40356]     = array(1,  "R",    0x03, "WchaMax",          "uint16",     "W",           40372,  VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.Watt",		    1,      3,		"Setpoint for maximum charge"								                     ,"");
        $IC124_ConfigArr[40357]     = array(1,  "R",    0x03, "WChaGra",          "uint16",     "%",           40373,  VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.Percent",	    NULL,	NULL,	"Setpoint for maximum charging rate"						                     ,"");
        $IC124_ConfigArr[40358]     = array(1,  "R",    0x03, "WDisChaGra",       "uint16",     "%",           40373,  VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.Percent",	    NULL,	NULL,	"Setpoint for maximum discharge rate"						                     ,"");
        $IC124_ConfigArr[40359]     = array(1,  "RW",   0x03, "StorCtl_Mod",      "uint16",     "bitfield16",  NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.StorCtl_Mod",	NULL,	NULL,	"Activate hold/discharge/charge control mode"		                     ,"");
        //$IC124_ConfigArr[40360]   = array(1,  "R",    0x03, "VAChaMax",         "uint16",     "VA",          40374,  VARIABLE::TYPE_FLOAT,	 false,	 "",		            NULL,	NULL,	"Setpoint for maximum charging VA"							                     ,"");
        $IC124_ConfigArr[40361]     = array(1,  "RW",   0x03, "MinRsvPct",        "uint16",     "%",           40375,  VARIABLE::TYPE_FLOAT,	 false,	 "GEN24.Percent.1",	    NULL,	NULL,	"Setpoint for minimum reserve for storage"					                     ,"");
        $IC124_ConfigArr[40362]     = array(1,  "R",    0x03, "ChaState",         "uint16",     "%",           40376,  VARIABLE::TYPE_FLOAT,	 false,	 "GEN24.Percent.1",	    NULL,	NULL,	"Currently available energy as percent"						                     ,"");
        //$IC124_ConfigArr[40363]   = array(1,  "R",    0x03, "StorAval",         "uint16",     "AH",          40377,  VARIABLE::TYPE_FLOAT,	 false,	 "",		            NULL,	NULL,	"ChaState - MinRsvPct | AhrRtg"								                     ,"State of charge (ChaState) minus storage reserve (MinRsvPct) times capacity rating (AhrRtg).");
        //$IC124_ConfigArr[40364]   = array(1,  "R",    0x03, "InBatV",           "uint16",     "V",           40378,  VARIABLE::TYPE_FLOAT,	 false,	 "",		            NULL,	NULL,	"Internal battery voltage"									                     ,"");
        $IC124_ConfigArr[40365]     = array(1,  "R",    0x03, "ChaSt",            "enum16",     "Enum_ChaSt",  NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.ChaSt",    	    NULL,	NULL,	"Charge status of storage device"							                     ,"The status TESTING is used during battery calibration or service charge.");
        $IC124_ConfigArr[40366]     = array(1,  "RW",   0x03, "OutWRte",          "int16",      "%",           40379,  VARIABLE::TYPE_FLOAT,	 false,	 "GEN24.Percent.2",	    NULL,	NULL,	"Percent of max discharge rate"								                     ,"");
        $IC124_ConfigArr[40367]     = array(1,  "RW",   0x03, "InWRte",           "int16",      "%",           40379,  VARIABLE::TYPE_FLOAT,	 false,	 "GEN24.Percent.2",	    NULL,	NULL,	"Percent of max charge rate"								                     ,"");
        //$IC124_ConfigArr[40368]   = array(1,  "R",    0x03, "InOutWRte_WinTms", "uint16",     "Secs",        NULL,   VARIABLE::TYPE_FLOAT,	 false,	 "",		            NULL,	NULL,	"Time window for charge/discharge rate change"				                     ,"");
        $IC124_ConfigArr[40369]     = array(1,  "R",    0x03, "InOutWRte_RvrtTms","uint16",     "Secs",        NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.Seconds",	    NULL,	NULL,	"Charge status of storage device"							                     ,"");
        //$IC124_ConfigArr[40370]   = array(1,  "R",    0x03, "InOutWRte_RmpTms", "uint16",     "Secs",        NULL,   VARIABLE::TYPE_FLOAT,	 false,	 "",		            NULL,	NULL,	"Ramp time for moving from current setpoint to new setpoint"                     ,"");
        $IC124_ConfigArr[40371]     = array(1,  "RW",   0x03, "ChaGriSet",        "uint16",     "enum16",      NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "GEN24.ChaGriSet",	    NULL,	NULL,	"Charging from grid"                                           ,"0:PV (Charging from grid disabled) | 1:GRID (Charging from grid enabled)");
        $IC124_ConfigArr[40372]     = array(1,  "R",    0x03, "WChaMax_SF",       "sunssf",     "",            0,      VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for maximum charge"							                     ,"");
        $IC124_ConfigArr[40373]     = array(1,  "R",    0x03, "WChaDisChaGra_SF", "sunssf",     "",            0,      VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,   NULL,	"Scale factor for maximum charge and discharge rate"		                     ,"");
        //$IC124_ConfigArr[40374]   = array(1,  "R",    0x03, "VAChaMax_SF",      "sunssf",     "",            NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for maximum charging VA"						                     ,"");
        $IC124_ConfigArr[40375]     = array(1,  "R",    0x03, "MinRsvPct_SF",     "sunssf",     "",            -2,     VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for minimum reserve percentage"				                     ,"");
        $IC124_ConfigArr[40376]     = array(1,  "R",    0x03, "ChaState_SF",      "sunssf",     "",            -2,     VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for available energy percent"					                     ,"");
        //$IC124_ConfigArr[40377]   = array(1,  "R",    0x03, "StorAval_SF",      "sunssf",     "",            NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for state of charge"							                     ,"");
        //$IC124_ConfigArr[40378]   = array(1,  "R",    0x03, "InBatV_SF",        "sunssf",     "",            NULL,   VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for battery voltage"							                     ,"");
        $IC124_ConfigArr[40379]     = array(1,  "R",    0x03, "InOutWRte_SF",     "sunssf",     "",            -2,     VARIABLE::TYPE_INTEGER,	 false,	 "",		            NULL,	NULL,	"Scale factor for percent charge/discharge rate"			                     ,"");

        return $IC124_ConfigArr;

    }


    protected function GetConfigArr_IC124__OLD() {

        //                               0      1       2       3                   4               5               6                       8
        //              Adress           Size   R/W     fCode   NAME                TYPE            UNIT            Scale Factor            Description
        $IC124_ConfigArr__OLD = array();
        $IC124_ConfigArr__OLD[40356]     = array(1,  "R",    0x03, "WchaMax",          "uint16",       "W",            "WChaMax_SF",           "Setpoint for maximum charge");
        $IC124_ConfigArr__OLD[40357]     = array(1,  "R",    0x03, "WChaGra",          "uint16",       "%",            "WChaDisChaGra_SF",     "Setpoint for maximum charging rate");
        $IC124_ConfigArr__OLD[40358]     = array(1,  "R",    0x03, "WDisChaGra",       "uint16",       "%",            "WChaDisChaGra_SF",     "Setpoint for maximum discharge rate");
        $IC124_ConfigArr__OLD[40359]     = array(1,  "RW",   0x03, "StorCtl_Mod",      "uint16",       "bitfield16",   "",                     "Activate hold/discharge/charge storage control mode");
        //$IC124_ConfigArr__OLD[40360]   = array(1,  "R",    0x03, "VAChaMax",         "uint16",       "VA",           "VAChaMax",             "Setpoint for maximum charging VA");
        $IC124_ConfigArr__OLD[40361]     = array(1,  "RW",   0x03, "MinRsvPct",        "uint16",       "%",            "MinRsvPct_SF",         "Setpoint for minimum reserve for storage");
        $IC124_ConfigArr__OLD[40362]     = array(1,  "R",    0x03, "ChaState",         "uint16",       "%",            "ChaState_SF",          "Currently available energy as a percent of the capacity rating");
        //$IC124_ConfigArr__OLD[40363]   = array(1,  "R",    0x03, "StorAval",         "uint16",       "AH",           "StorAval_SF",          "State of charge (ChaState) minus storage reserve (MinRsvPct) times capacity rating (AhrRtg).");
        //$IC124_ConfigArr__OLD[40364]   = array(1,  "R",    0x03, "InBatV",           "uint16",       "V",            "InBatV_SF",            "Internal battery voltage");
        $IC124_ConfigArr__OLD[40365]     = array(1,  "R",    0x03, "ChaSt",            "enum16",       "Enum_ChaSt",   "",                     "Charge status of storage device");
        $IC124_ConfigArr__OLD[40366]     = array(1,  "RW",   0x03, "OutWRte",          "int16",        "%",            "InOutWRte_SF",         "Percent of max discharge rate");
        $IC124_ConfigArr__OLD[40367]     = array(1,  "RW",   0x03, "InWRte",           "int16",        "%",            "InOutWRte_SF",         "Percent of max charge rate");
        //$IC124_ConfigArr__OLD[40368]   = array(1,  "R",    0x03, "InOutWRte_WinTms", "uint16",       "Secs",         "",                     "Time window for charge/discharge rate change");
        $IC124_ConfigArr__OLD[40369]     = array(1,  "R",    0x03, "InOutWRte_RvrtTms","uint16",       "Secs",         "",                     "Charge status of storage device");
        //$IC124_ConfigArr__OLD[40370]   = array(1,  "R",    0x03, "InOutWRte_RmpTms", "uint16",       "Secs",         "",                     "Ramp time for moving from current setpoint to new setpoint");
        $IC124_ConfigArr__OLD[40371]     = array(1,  "RW",   0x03, "ChaGriSet",       "uint16",       "enum16",       "",                     "0: PV (Charging from grid disabled) | 1: GRID (Charging from grid enabled)");
        $IC124_ConfigArr__OLD[40372]     = array(1,  "R",    0x03, "WChaMax_SF",      "sunssf",       "",             0,                      "Scale factor for maximum charge");
        $IC124_ConfigArr__OLD[40373]     = array(1,  "R",    0x03, "WChaDisChaGra_SF","sunssf",       "",             0,                      "Scale factor for maximum charge and discharge rate");
        //$IC124_ConfigArr__OLD[40374]   = array(1,  "R",    0x03, "VAChaMax_SF",     "sunssf",       "",             "",                     "Scale factor for maximum charging VA");
        $IC124_ConfigArr__OLD[40375]     = array(1,  "R",    0x03, "MinRsvPct_SF",    "sunssf",       "",             -2,                     "Scale factor for minimum reserve percentage");
        $IC124_ConfigArr__OLD[40376]     = array(1,  "R",    0x03, "ChaState_SF",     "sunssf",       "",             -2,                     "Scale factor for available energy percent");
        //$IC124_ConfigArr__OLD[40377]   = array(1,  "R",    0x03, "StorAval_SF",     "sunssf",       "",             "",                     "Scale factor for state of charge");
        //$IC124_ConfigArr__OLD[40378]   = array(1,  "R",    0x03, "InBatV_SF",       "sunssf",       "",             "",                     "Scale factor for battery voltage");
        $IC124_ConfigArr__OLD[40379]     = array(1,  "R",    0x03, "InOutWRte_SF",    "sunssf",       "",             -2,                     "Scale factor for percent charge/discharge rate");

        return $IC124_ConfigArr__OLD;
    }

    
    protected function GetConfigArr_IC124__OLD2() {

        // 0 = IMR_START_REGISTER", 0 | 1 = IMR_SIZE | 2 = IMR_RW | 3 = IMR_FUNCTION_CODE| 4 = IMR_NAME | 5 = IMR_TYPE | 6 = IMR_UNITS  | 7 = IMR_SF  | 8 = IMR_DESCRIPTION
        $IC124_RegisterConfigArr = array(
            //    0      1      2       3       4                   5               6               7                       8
            //   Adress  Size   R/W     fCode   NAME                TYPE            UNIT            Scale Factor            Description
            array(40356, 1,     "R",    "0x03", "WchaMax",          "uint16",       "W",            "WChaMax_SF",           "Setpoint for maximum charge"),
            array(40357, 1,     "R",    "0x03", "WChaGra",          "uint16",       "%",            "WChaDisChaGra_SF",     "Setpoint for maximum charging rate"),
            array(40358, 1,     "R",    "0x03", "WDisChaGra",       "uint16",       "%",            "WChaDisChaGra_SF",     "Setpoint for maximum discharge rate"),
            array(40359, 1,     "RW",   "0x03", "StorCtl_Mod",      "uint16",       "bitfield16",   "",                     "Activate hold/discharge/charge storage control mode"),
            //array(40360, 1,   "R",    "0x03", "VAChaMax",         "uint16",       "VA",           "VAChaMax",             "Setpoint for maximum charging VA"),
            array(40361, 1,     "RW",   "0x03", "MinRsvPct",        "uint16",       "%",            "MinRsvPct_SF",         "Setpoint for minimum reserve for storage"),
            array(40362, 1,     "R",    "0x03", "ChaState",         "uint16",       "%",            "ChaState_SF",          "Currently available energy as a percent of the capacity rating"),
            //array(40363, 1,   "R",    "0x03", "StorAval",         "uint16",       "AH",           "StorAval_SF",          "State of charge (ChaState) minus storage reserve (MinRsvPct) times capacity rating (AhrRtg)."),
            //array(40364, 1,   "R",    "0x03", "InBatV",           "uint16",       "V",            "InBatV_SF",            "Internal battery voltage"),
            array(40365, 1,     "R",    "0x03", "ChaSt",            "enum16",       "Enum_ChaSt",   "",                     "Charge status of storage device"),
            array(40366, 1,     "RW",   "0x03", "OutWRte",          "int16",        "%",            "InOutWRte_SF",         "Percent of max discharge rate"),
            array(40367, 1,     "RW",   "0x03", "InWRte",           "int16",        "%",            "InOutWRte_SF",         "Percent of max charge rate"),
            //array(40368, 1,   "R",    "0x03", "InOutWRte_WinTms", "uint16",       "Secs",         "",                     "Time window for charge/discharge rate change"),
            array(40369, 1,     "R",    "0x03", "InOutWRte_RvrtTms","uint16",       "Secs",         "",                     "Charge status of storage device"),
            //array(40370, 1,   "R",    "0x03", "InOutWRte_RmpTms", "uint16",       "Secs",         "",                     "Ramp time for moving from current setpoint to new setpoint"),
            array(40371, 1,     "RW",    "0x03", "ChaGriSet",       "uint16",       "enum16",       "",                     "0: PV (Charging from grid disabled) | 1: GRID (Charging from grid enabled)"),
            array(40372, 1,     "R",     "0x03", "WChaMax_SF",      "sunssf",       "",             0,                      "Scale factor for maximum charge"),
            array(40373, 1,     "R",     "0x03", "WChaDisChaGra_SF","sunssf",       "",             0,                      "Scale factor for maximum charge and discharge rate"),
            //array(40374, 1,   "R",     "0x03", "VAChaMax_SF",     "sunssf",       "",             "",                     "Scale factor for maximum charging VA"),
            array(40375, 1,     "R",     "0x03", "MinRsvPct_SF",    "sunssf",       "",             -2,                     "Scale factor for minimum reserve percentage"),
            array(40376, 1,     "R",     "0x03", "ChaState_SF",     "sunssf",       "",             -2,                     "Scale factor for available energy percent"),
            //array(40377, 1,   "R",     "0x03", "StorAval_SF",     "sunssf",       "",             "",                     "Scale factor for state of charge"),
            //array(40378, 1,   "R",     "0x03", "InBatV_SF",       "sunssf",       "",             "",                     "Scale factor for battery voltage"),
            array(40379, 1,     "R",     "0x03", "InOutWRte_SF",    "sunssf",       "",             -2,                     "Scale factor for percent charge/discharge rate")
        );

        return $IC124_RegisterConfigArr;
    }


   
   
}

?>