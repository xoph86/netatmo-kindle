<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');

require_once 'src/Netatmo/autoload.php';

include(dirname(__FILE__).'/config.inc.php');

$config = array();
$config['client_id'] = $app_id;
$config['client_secret'] = $app_secret;
$config['scope'] = 'read_station';

$client = new \Netatmo\Clients\NAWSApiClient($config);

$tokens = array("access_token" => $access_token,
                "refresh_token" => $refresh_token);
$client->setTokensFromStore($tokens);

// Get data
try
{
        $data = $client->getData(NULL, TRUE);
        $temperature = isset($data['devices'][0]['dashboard_data']['Temperature']) ? $data['devices'][0]['dashboard_data']['Temperature'] : '? ';
}
catch(Netatmo\Exceptions\NAClientException $ex)
{
        $errormsg = "An error occcured while trying to retrieve your data";
        $error_call=true;
}

// Print the data array
echo '<pre>'; print_r($data); echo '</pre>';

// Messwerte bereitstellen

$device0 = 0; //Basisstation
$module0 = 0; //Außenmodul

$temp_base      = isset($data['devices'][$device0]['dashboard_data']['Temperature']) ? $data['devices'][0]['dashboard_data']['Temperature'] : '? ';
$humi_base      = isset($data['devices'][$device0]['dashboard_data']['Humidity']) ? $data['devices'][0]['dashboard_data']['Humidity'] : '? ';
$CO2_base       = isset($data['devices'][$device0]['dashboard_data']['CO2']) ? $data['devices'][0]['dashboard_data']['CO2'] : '? ';
$min_temp_base  = isset($data['devices'][$device0]['dashboard_data']['min_temp']) ? $data['devices'][0]['dashboard_data']['min_temp'] : '? ';
$max_temp_base  = isset($data['devices'][$device0]['dashboard_data']['max_temp']) ? $data['devices'][0]['dashboard_data']['max_temp'] : '? ';
$min_date_base  = isset($data['devices'][$device0]['dashboard_data']['date_min_temp']) ? $data['devices'][0]['dashboard_data']['date_min_temp'] : '? ';
$max_date_base  = isset($data['devices'][$device0]['dashboard_data']['date_max_temp']) ? $data['devices'][0]['dashboard_data']['date_max_temp'] : '? ';
$pressure       = isset($data['devices'][$device0]['dashboard_data']['Pressure']) ? $data['devices'][0]['dashboard_data']['Pressure'] : '? ';
$temp_mod0      = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['Temperature']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['Temperature'] : '? ';
$humi_mod0      = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['Humidity']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['Humidity'] : '? ';
$min_temp_mod0  = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['min_temp']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['min_temp'] : '? ';
$max_temp_mod0  = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['max_temp']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['max_temp'] : '? ';
$min_date_mod0  = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['date_min_temp']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['date_min_temp'] : '? ';
$max_date_mod0  = isset($data['devices'][$device0]['modules'][$module0]['dashboard_data']['date_max_temp']) ? $data['devices'][$device0]['modules'][$module0]['dashboard_data']['date_max_temp'] : '? ';

echo "temp_base " . $temp_base  . "<br>";
echo "humi_base  " . $humi_base   . "<br>";
echo "CO2_base " . $CO2_base   . "<br>";
echo "min_temp_base  " . $min_temp_base   . "<br>";
echo "max_temp_base  " . $max_temp_base    . "<br>";
echo "pressure  " . $pressure    . "<br>";
echo "temp_mod0 " . $temp_mod0   . "<br>";
echo "humi_mod0  " . $humi_mod0   . "<br>";
echo "min_temp_mod0 " . $min_temp_mod0  . "<br>";
echo "max_temp_mod0" . $max_temp_mod0   . "<br>";
echo "min_date_mod0 " . $min_date_mod0  . "<br>";
echo "max_date_mod0" . $max_date_mod0   . "<br>";


// Taupunkt berechnen

if ($temp_mod0 > 0) {
    $k2	= 17.62;
    $k3	= 243.12;
} else {
    $k2	= 22.46;
    $k3	= 272.62;
}

$dewpoint	= $k3 *(($k2 * $temp_mod0) / ($k3 + $temp_mod0) + log($humi_mod0 / 100));
$dewpoint	= $dewpoint / (($k2 * $k3) / ($k3 + $temp_mod0) - log($humi_mod0 / 100));
$dewpoint	= round($dewpoint, 1);
$dewpoint   = number_format($dewpoint,1,",",""); 

// Messwerte formatieren

$min_date_mod0      = date("H:i",$min_date_mod0);
$max_date_mod0      = date("H:i",$max_date_mod0);
$min_date_base      = date("H:i", $min_date_base);
$max_date_base      = date("H:i", $max_date_base);
$temp_mod0          = number_format($temp_mod0,1,",","");
$temp_base          = number_format($temp_base,1,",","");
$pressure           = round($pressure);
$max_temp_mod0      = number_format($max_temp_mod0,1,",","");
$min_temp_mod0      = number_format($min_temp_mod0,1,",","");
$max_temp_base      = number_format($max_temp_base,1,",","");
$min_temp_base      = number_format($min_temp_base,1,",","");

// --- mySQL Daten bereitstellen und einfügen ---

// Lösche Tabelle einmal am Tag (falls Cronjob nicht funktionieren sollte) - Zuerst wird die Anzahl der Einträge gezählt und danach ggf. gelöscht

$sql = "SELECT count($column1) FROM $table;"; 
$result = $conn->prepare($sql); 
$result->execute(); 
$number_of_rows = $result->fetchColumn(); 
echo $number_of_rows . "<br>";

if ($number_of_rows >= '96') {
    $sql = "TRUNCATE TABLE $table;"; 
    $conn->exec($sql);
}

// Füge aktuelle Messwerte in Tabelle ein

date_default_timezone_set("Europe/Berlin");
$timestamp = time();
$uhrzeit = date("H:i",$timestamp);

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "INSERT INTO $table ($column0, $column1, $column2, $column3)
    VALUES ('$uhrzeit', '$humi_base', '$humi_mod0', '$CO2_base')";
    $conn->exec($sql);
    echo "Neuer Eintrag erstellt. <br>";
}
catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}

// Maximalwert Luftfeuchtigkeit Innen ausgeben

$sql = "SELECT MAX($column1) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$max_humi_base = $statement->fetchColumn();
echo "Maximale Luftfeuchtigkeit Innen: " . $max_humi_base . "%<br>";

// Minimalwert Luftfeuchtigkeit Innen ausgeben

$sql = "SELECT MIN($column1) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$min_humi_base = $statement->fetchColumn();
echo "Minimale Luftfeuchtigkeit Innen: " . $min_humi_base . "% <br>";

// Maximalwert Luftfeuchtigkeit Außen ausgeben

$sql = "SELECT MAX($column2) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$max_humi_mod0 = $statement->fetchColumn();
echo "Maximale Luftfeuchtigkeit Aussen: " . $max_humi_mod0 . "%<br>";

// Minimalwert Luftfeuchtigkeit Außen ausgeben

$sql = "SELECT MIN($column2) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$min_humi_mod0 = $statement->fetchColumn();
echo "Minimale Luftfeuchtigkeit Aussen: " . $min_humi_mod0 . "%<br>";

// Maximalwert CO2 ausgeben

$sql = "SELECT MAX($column3) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$max_CO2_base = $statement->fetchColumn();
echo "Maximaler CO2 Gehalt: " . $max_CO2_base . "ppm<br>";

// Minimalwert CO2 ausgeben

$sql = "SELECT MIN($column3) FROM $table;";
$statement = $conn->prepare($sql);
$statement->execute();
$min_CO2_base = $statement->fetchColumn();
echo "Minimaler CO2 Gehalt: " . $min_CO2_base . "ppm<br>";

// --- Ende mySQL ---

// Aktuelles Datum berechnen

$time       = time();
$weekday    = date("D", $time);
$day        = date("j", $time);
$month      = date("n", $time);
$year       = date("Y", $time);

switch ($weekday) {
    case "Mon":
        $weekday = "Montag";
        break;
    case "Tue":
        $weekday = "Dienstag";
        break;
    case "Wed":
        $weekday = "Mittwoch";
        break;
    case "Thu":
        $weekday = "Donnerstag";
        break;
    case "Fri":
        $weekday = "Freitag";
        break;
    case "Sat":
        $weekday = "Samstag";
        break;
    case "Sun":
        $weekday = "Sonntag";
        break;
}

switch ($month) {
    case 1:
        $month = "Januar";
        break;
    case 2:
        $month = "Februar";
        break;
    case 3:
        $month = "März";
        break;
    case 4:
        $month = "April";
        break;
    case 5:
        $month = "Mai";
        break;
    case 6:
        $month = "Juni";
        break;
    case 7:
        $month = "Juli";
        break;
    case 8:
        $month = "August";
        break;
    case 9:
        $month = "September";
        break;
    case 10:
        $month = "Oktober";
        break;
    case 11:
        $month = "November";
        break;
    case 12:
        $month = "Dezember";
        break;
}

$date   = "$weekday, $day. $month $year";

// Sonnenaufgang/Sonnenuntergang

$latitude       = isset($data['devices'][$device0]['place']['location'][1]) ? $data['devices'][$device0]['place']['location'][1] : '? ';
$longitude      = isset($data['devices'][$device0]['place']['location'][0]) ? $data['devices'][$device0]['place']['location'][0] : '? ';

$time           = time();
$dst            = date("I", $time);

if ($dst) {
    $offset     = 2;
} else {
    $offset     = 1;
}

$zenith         = 50/60;
$zenith         = $zenith + 90;

$sunrise        = date_sunrise($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $offset);
$sunrise        = date("H:i", $sunrise);

$sunset         = date_sunset($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $offset);
$sunset         = date("H:i", $sunset);

// Mondphasenbestimmung
include 'moonphase.php';
$moon = new Solaris\MoonPhase();
$today = intval(gmdate('z'));
$nextnm = gmdate('z', $moon->get_phase('new_moon'));
$nextfm = gmdate('z', $moon->get_phase('full_moon'));
$nextfmint = intval($nextfm);
$nextnmint = intval($nextnm);
if ($nextfmint<$today)
{
    $nextfm = gmdate('j.m.', $moon->get_phase('next_full_moon'));
} 
else
{
    $nextfm = gmdate('j.m.', $moon->get_phase('full_moon'));
}		
if ($nextnmint<$today)
{
    $nextnm = gmdate('j.m.', $moon->get_phase('next_new_moon'));
}
else
{
    $nextnm = gmdate('j.m.', $moon->get_phase('new_moon'));
}	
$moonstatus = round($moon->get('illumination'), 1)*100;

// Bestimme Icon für Mondphase
if ($moon->phase() < 0.5)
    { 
        $moonicon = $pfad . 'moon/waxing/'. $moonstatus . '.png';
    } else
    {
        $moonicon = $pfad . 'moon/waning/'. $moonstatus . '.png';
    }

// Leere PNG-Datei mit weißem Hintergrund erstellen

$image      = ImageCreateTrueColor(600, 800);
$background = ImageColorAllocate($image, 255, 255, 255);

ImageFilledRectangle($image, 0, 0, 600, 800, $background);

// Farbe für Schrift und Hilfslinien festlegen

 $color     = ImageColorAllocate($image, 0, 0, 0);
 
// Datum

ImageTTFText($image, $font3, 0, 15, 30, $color, $font, $date);

// Gitter erstellen

// Temperatur
ImageFilledRectangle($image, 15, 40, 585, 41, $color);      // Horizontale Trennung 1
ImageFilledRectangle($image, 299, 50, 300, 175, $color);   // Vertikaler Strich

// Luftfeuchtigkeit
ImageFilledRectangle($image, 15, 184, 585, 185, $color);    // Horizontale Trennung 2
ImageFilledRectangle($image, 299, 194, 300, 318, $color);   // Vertikaler Strich

// CO2
ImageFilledRectangle($image, 15, 328, 585, 329, $color);    // Horizontaler Strich
//ImageFilledRectangle($image, 299, 338, 300, 462, $color);   // Vertikaler Strich

// Luftdruck / Taupunkt
ImageFilledRectangle($image, 15, 472, 585, 473, $color);    // Horizontaler Strich
ImageFilledRectangle($image, 299, 482, 300, 606, $color);   // Vertikaler Strich

// Mondphase
ImageFilledRectangle($image, 15, 616, 585, 617, $color);    // Horizontaler Strich
// ImageFilledRectangle($image, 299, 626, 300, 750, $color);   // Vertikaler Strich

// Sonnenaufgang
ImageFilledRectangle($image, 15, 760, 585, 761, $color);    // Horizontale Trennung über Sonnenaufgang und Untergang

// Temperaturen

ImageTTFText($image, $font3, 0, 15, 75, $color, $font, "Temperatur innen:");
ImageTTFText($image, $font4, 0, 15, 157, $color, $font, $temp_base);
ImageTTFText($image, $font5, 0, 170, 157, $color, $font, "°C");

ImageTTFText($image, $font1, 0, 225, 124, $color, $font, $min_temp_base);
ImageTTFText($image, $font1, 0, 270, 124, $color, $font, "°C");
ImageTTFText($image, $font1, 0, 225, 157, $color, $font, $max_temp_base);
ImageTTFText($image, $font1, 0, 270, 157, $color, $font, "°C");

ImageTTFText($image, $font3, 0, 315, 75, $color, $font, "Temperatur außen:");
ImageTTFText($image, $font4, 0, 315, 157, $color, $font, $temp_mod0);
ImageTTFText($image, $font5, 0, 470, 157, $color, $font, "°C");

ImageTTFText($image, $font1, 0, 525, 124, $color, $font, $min_temp_mod0);
ImageTTFText($image, $font1, 0, 570, 124, $color, $font, "°C");
ImageTTFText($image, $font1, 0, 525, 157, $color, $font, $max_temp_mod0);
ImageTTFText($image, $font1, 0, 570, 157, $color, $font, "°C");

// Luftfeuchtigkeit

ImageTTFText($image, $font3, 0, 15, 219, $color, $font, "Luftfeuchtigkeit innen:");
ImageTTFText($image, $font4, 0, 15, 300, $color, $font, $humi_base);
ImageTTFText($image, $font5, 0, 110, 300, $color, $font, "%");

ImageTTFText($image, $font1, 0, 225, 267, $color, $font, $min_humi_base);
ImageTTFText($image, $font1, 0, 255, 267, $color, $font, "%");
ImageTTFText($image, $font1, 0, 225, 300, $color, $font, $max_humi_base);
ImageTTFText($image, $font1, 0, 255, 300, $color, $font, "%");

ImageTTFText($image, $font3, 0, 315, 219, $color, $font, "Luftfeuchtigkeit außen:");
ImageTTFText($image, $font4, 0, 315, 300, $color, $font, $humi_mod0);
if ($humi_mod0 > 99) {
    ImageTTFText($image, $font5, 0, 450, 300, $color, $font, "%");
} else {
    ImageTTFText($image, $font5, 0, 410, 300, $color, $font, "%");;
}
ImageTTFText($image, $font1, 0, 525, 267, $color, $font, $min_humi_mod0);
ImageTTFText($image, $font1, 0, 555, 267, $color, $font, "%");
ImageTTFText($image, $font1, 0, 525, 300, $color, $font, $max_humi_mod0);
if ($max_humi_mod0 > 99) {
    ImageTTFText($image, $font1, 0, 565, 300, $color, $font, "%");
} else {
    ImageTTFText($image, $font1, 0, 555, 300, $color, $font, "%");
}

//CO2

ImageTTFText($image, $font3, 0, 15, 363, $color, $font, "CO2 Gehalt:");
ImageTTFText($image, $font4, 0, 15, 445, $color, $font, $CO2_base);
if ($CO2_base > 999) {
    ImageTTFText($image, $font5, 0, 200, 445, $color, $font, "ppm");
} else {
    ImageTTFText($image, $font5, 0, 160, 445, $color, $font, "ppm");
}

if ($min_CO2_base > 999) {
    ImageTTFText($image, $font1, 0, 290, 412, $color, $font, $min_CO2_base);
} else {
    ImageTTFText($image, $font1, 0, 300, 412, $color, $font, $min_CO2_base);
}
ImageTTFText($image, $font1, 0, 350, 412, $color, $font, "ppm");

if ($max_CO2_base > 999) {
    ImageTTFText($image, $font1, 0, 292, 445, $color, $font, $max_CO2_base);
} else {
    ImageTTFText($image, $font1, 0, 300, 445, $color, $font, $max_CO2_base);
}
ImageTTFText($image, $font1, 0, 350, 445, $color, $font, "ppm");

//Luftqualität

if ($CO2_base < 700) {
    $icon   = $pfad . 'icons/good.png';
    $icon   = ImageCreateFromPNG($icon);
    ImageCopy($image, $icon , 450, 350, 0, 0, 100, 100);
} elseif ($CO2_base > 1200) {
    $icon   = $pfad . 'icons/bad.png';
    $icon   = ImageCreateFromPNG($icon);
    ImageCopy($image, $icon , 450, 350, 0, 0, 100, 100);
} else {
    $icon   = $pfad . 'icons/medium.png';
    $icon   = ImageCreateFromPNG($icon);
    ImageCopy($image, $icon , 450, 350, 0, 0, 100, 100);
}

// Luftdruck

ImageTTFText($image, $font3, 0, 15, 507, $color, $font, "Luftdruck:");
ImageTTFText($image, $font4, 0, 15, 589, $color, $font, $pressure);
ImageTTFText($image, $font5, 0, 200, 589, $color, $font, "hPa");

// Taupunkt

ImageTTFText($image, $font3, 0, 315, 507, $color, $font, "Taupunkt:");
ImageTTFText($image, $font4, 0, 315, 589, $color, $font, $dewpoint);
ImageTTFText($image, $font5, 0, 470, 589, $color, $font, "°C");

// Mond

ImageTTFText($image, $font3, 0, 15, 651, $color, $font, "Mond:");
ImageTTFText($image, $font1, 0, 15, 700, $color, $font, "Nächster Vollmond:");
ImageTTFText($image, $font1, 0, 255, 700, $color, $font, $nextfm);
ImageTTFText($image, $font1, 0, 15, 733, $color, $font, "Nächster Neumond:");
ImageTTFText($image, $font1, 0, 255, 733, $color, $font, $nextnm);

$moonicon   = ImageCreateFromPNG($moonicon);
ImageCopy($image, $moonicon , 450, 639, 0, 0, 100, 100);

// Sonnenaufgang und Sonnenuntergang

$icon   = $pfad . 'icons/sunrise.png';
$icon   = ImageCreateFromPNG($icon);
ImageCopy($image, $icon , 20, 760, 0, 0, 40, 40);

$icon   = $pfad . 'icons/sunset.png';
$icon   = ImageCreateFromPNG($icon);
ImageCopy($image, $icon , 540, 760, 0, 0, 40, 40);

ImageTTFText($image, $font2, 0, 70, 788, $color, $font, $sunrise);
ImageTTFText($image, $font2, 0, 495, 788, $color, $font, $sunset);

// Letztes Update

date_default_timezone_set("Europe/Berlin");
$timestamp = time();
$uhrzeit = date("H:i",$timestamp);
echo $uhrzeit," Uhr";
ImageTTFText($image, $font2, 0, 225, 788, $color, $font, "Letztes Update:");
ImageTTFText($image, $font2, 0, 335, 788, $color, $font, $uhrzeit);    

// PNG-erstellen und temporäre Daten löschen

ImagePNG($image, $filename);
ImageDestroy($image);

// Farbraum in Graustufen ändern

$im = new Imagick($filename);
$im->setImageResolution(600,800);
$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im->writeImage($filename); 

// Schließe Verbindung zur mySQL Datenbank
$conn = null;

// HTML
echo '<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="300; url=\'' . $_SERVER['SCRIPT_NAME'] . '\'">
</head>
<body>
<p>Temperature = ' . $temperature . '</p>
</body>
</html>';