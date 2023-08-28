# netatmo-kindle
Use the Amazon Kindle as a display for your Netatmo Weather Station

This project is an update from the project created by Stephan Kunkel as described here http://kunkel-online.net/netatmo-kindle/ (only in German).
Here so far only my code is given which creates a picture called "weather-script-output.png". Please check the mentioned website to see how you can setup your kindle to load this picture.

However as there were any updates on that page since 2015 I decided to start to update the project a little bit due to the fact that the login at Netatmo did change.

The login solution is based on the solution made in https://github.com/larknafets/php-weather-de .

There is still a lot of work in progress and the code is not neither well structured nor clean.

Requirements to run this script as it is without changes:
- Download the Netatmo API PHP on https://github.com/Netatmo/Netatmo-API-PHP and put the src folder on your webdrive.
- A MySQL Database with 4 columns
    $column0    = "zeit";
    $column1    = "humi_base";
    $column2    = "humi_mod0";
    $column3    = "CO2_base";
- A webserver with PHP and MySQL.
- A cronjob opening the wettercheck.php every 15 minutes.
- Roboto-Regular.ttf Font (https://fonts.google.com/specimen/Roboto)
