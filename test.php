<?php
//Composer
require __DIR__ . '/vendor/autoload.php';
//init printer
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

$connector = new FilePrintConnector("/dev/usb/lp1");
$printer = new Printer($connector);

$printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->text("Royal Hackaway v2 \n");

$printer->cut();

$printer->close();
?>