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
$dinnerChoices = readCSV("data/data.csv");
$master = readCSV("data/master.csv");
$checkins = createCheckins("data");
$counter = 0;

$printer = new Printer($connector);

while (($input = readline("Scan QR code: ")) != null) {
    if(($attendee = findAttendee($input, $csv)) != null){
        echo("\033[34m [ATTENDEE FOUND] \033[0m \n");
        if(strtolower(readline($attendee[1] . ": y/n? ")) === "y"){
            echo("\033[33m [CHECKED IN] \033[0m \n");
            writeCheckin($checkins, $attendee[5]);
            echo("\033[32m [PRINTING...] \033[0m \n");
            genSticker($printer, $attendee[1], $attendee[2], $attendee[3], $attendee[4]);
        } else {
            echo("\033[33m [NOT CHECKED IN] \033[0m \n");
        }
        $counter++;
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

function findAttendee($qr){
    if(($record = checkMasterSheet($qr)) != null){
        if(($choice = findDinnerChoice($record[7])) != null){
            $choice[4] = getNextSitting();
            return $choice;
        } else {
            echo("\033[33m [NO DINNER CHOICE] \033[0m \n");
            $choice[1] = $record[7];
            $choice[2] = onFlyChoice();
        }
    } else {
        //QR does not exist
        return null;
    }
}

function onFlyChoice(){
    echo("
    [0] Beef Burger & Chips \n 
    [1] Chicken Burger & Chips (Halal) \n
    [2] Beanie Burger & Chips (Halal & vegetarian, vegan on request) \n
    [3] Chicken Burger & Side Salad (Gluten Free) \n
    [4] Vegan Curry \n");

    switch (readline("Enter Choice Number: ")) {
        case 0:
            return "Beef Burger & Chips";
            break;
        
        case 1:
            return "Chicken Burger & Chips (Halal)";
            break;
        case 2:
            return "Beanie Burger & Chips (Halal & vegetarian, vegan on request)";
            break;
        case 3:
            return "Chicken Burger & Side Salad (Gluten Free)";
            break;
        case 4:
            return "Vegan Curry";
            break;
        default:
            return onFlyChoice();
            break;
    }
}

function getNextSitting(){
    if($counter % 3 == 0){
        return 'C';
    } elseif ($counter % 2 == 0){
        return 'B';
    } else {
        return 'A';
    }
    return null;
}

function checkMasterSheet($qr){
    global $master;

    foreach ($master as $key => $value){
        //Check the QR value against the sanitised unique ticket URL
        if(strcasecmp(preg_replace("^(https:\/\/ti\.to\/tickets\/)", "", $value[16]), $qr) == 0){
            //Return the record 
            return $value;
        }
    }
    return NULL;
}

function findDinnerChoice($email){
    global $dinnerChoices;

    foreach ($dinnerChoices as $key => $value) {
        if(strcasecmp($value[0], $email) == 0){
            return $dinnerChoices[$key];
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