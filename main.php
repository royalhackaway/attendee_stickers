<?php
//Composer
require __DIR__ . '/vendor/autoload.php';
//init printer
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

register_shutdown_function("shutdown");

// PLEASE CHANGE AS NEEDED
$connector = new FilePrintConnector("/dev/usb/lp0");
$csv = readCSV("data/data.csv");
$checkins = createCheckins("data");

$printer = new Printer($connector);

while (($input = readline("Scan QR code: ")) != null) {
    if(($attendee = findAttendee($input, $csv)) != null){
        echo("\033[34m [ATTENDEE FOUND] \033[0m \n");
        if(strtolower(readline($attendee[1] . ": y/n? ")) === "y"){
            echo("\033[33m [CHECKED IN] \033[0m \n");
            writeCheckin($checkins, $attendee[5]);
            echo("\033[32m [PRINTING...] \033[0m \n");
            genSticker($printer, $attendee[5], $attendee[2], $attendee[3], $attendee[4]);
        } else {
            echo("\033[33m [NOT CHECKED IN] \033[0m \n");
        }
    } else {
        echo("\033[31m [ERROR] \033[0m attendee not found \n");
    }
}

function writeCheckin($handle, $email){
    return fwrite($handle, $email . "," . time() . "\n\r");
}

function createCheckins($dir){
    return fopen($dir . "/checkins_" . time() . ".csv", "x");
}

function findAttendee($email, $csv){
    foreach ($csv as $key => $value) {
        if(strcasecmp($value[0], $email) == 0){
            return $csv[$key];
        }
    }
    return null;
}

function readCSV($fname){
    if(file_exists($fname)){
        return array_map('str_getcsv', file($fname));
    } else {
        throw new exception("Missing CSV to import");
    }
}

function genSticker($printer, $email, $choice, $allergy, $pos){
    $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Royal Hackaway v2 \n");

    //Print Sitting letter
    switch (strtolower($pos)) {
        case 'a':
            $logo =  __DIR__ .  "/assets/a.png";
            break;
        
        case 'b':
            $logo =  __DIR__ .  "/assets/b.png";
            break;
        
        case 'c':
            $logo =  __DIR__ .  "/assets/c.png";
            break;
        
        default:
            echo("\033[31m [ERROR] \033[0m Weird meal sitting letter \n");
            return;
    }
    $img = EscposImage::load($logo);
    $printer->bitImage($img);

    //print text
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setEmphasis(false);
    $printer->setTextSize(1,1);

    $printer->text("Email: " . $email . "\n");
    $printer->text("Choice: " . $choice . " \n");
    $printer->text("Allergies: " . $allergy . " \n");

    //end
    $printer->cut();

    return;
}

function shutdown(){
    global $printer;
    global $checkins;
    
    //Close resources
    $printer->close();
    fclose($checkins);

    echo("\033[35m [EXITING] \033[0m");
}   
?>