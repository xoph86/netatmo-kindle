<?php

// Zugangsdaten mySQL

    $user       = "";
    $pw         = "";
    $servername = "localhost";
    $dbname     = "";
    $table      = "humi";
    $column0    = "zeit";
    $column1    = "humi_base";
    $column2    = "humi_mod0";
    $column3    = "CO2_base";

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $user, $pw);

// Zugangsdaten Netatmo
    
    //$username	= "";
    //$password	= "";
    $app_id		= "";
    $app_secret = "";
    // Generate tokens first with you app: https://dev.netatmo.com/apps/
    $access_token = '';
    $refresh_token = '';
    //$device_id = "";
    //$module_id = "";

// Pfad auf Server

    $pfad ="";

// Variablen

    $filename   = $pfad . "weather-script-output.png";
    $font       = $pfad . "Roboto-Regular.ttf";

// Schriftgrößen definieren

    $font1      = 18;
    $font2      = 12;
    $font3      = 20;
    $font4      = 60;
    $font5      = 35;

?>
