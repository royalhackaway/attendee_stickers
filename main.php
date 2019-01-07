<?php
//Composer
require __DIR__ . '/vendor/autoload.php';
//init printer
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

if(!file_exists( __DIR__ .  "/config.json")){
    echo "Fatal error: missing config file";
    exit();
} else {
    $config = json_decode(file_get_contents( __DIR__ .  "/config.json"), true);
}

foreach ($config as $key => $value) {
    switch ($key) {
        case 0:
            $news = simplexml_load_file($value['url'], null, LIBXML_NOCDATA);
            break;
        
        case 1:
            $weather = simplexml_load_file($value['url']);
            $synp = explode(",", $weather->{'channel'}->{'item'}->{'description'});
            break;
    }
}
$logo =  __DIR__ .  "/blq-orbit-blocks_grey.png";
$monzo =  __DIR__ .  "/monzo_vrt_whtbg_small.png";
$connector = new FilePrintConnector("/dev/usb/lp0");
$printer = new Printer($connector);


//NEWS
$printer -> setEmphasis(true);
$printer -> setTextSize(3,3);
$printer -> setJustification(Printer::JUSTIFY_CENTER);
//logo
$img = EscposImage::load($logo);
$printer -> graphics($img, Printer::IMG_DOUBLE_WIDTH | Printer::IMG_DOUBLE_HEIGHT);
//header
$printer -> text("News\n");
//reset
$printer -> setEmphasis(false);
$printer -> setTextSize(1,1);

for ($i=0; $i < 10; $i++) { 
    $new = $news->{'channel'}->{'item'}[$i];
    $printer -> setEmphasis(true);
    $printer -> text($new->{'title'} . "\n \n");
}

//WEATHER
$printer -> setEmphasis(true);
$printer -> setTextSize(3,3);
$printer -> setJustification(Printer::JUSTIFY_CENTER);
$printer -> text("Weather\n");
//reset
$printer -> setEmphasis(false);
$printer -> setTextSize(1,1);
foreach ($synp as $key => $value) {
    $printer -> text($value . "\n");
}

/* Disabled until oauth fixed

//MONZO
$printer -> setEmphasis(true);
$printer -> setTextSize(3,3);
$printer -> setJustification(Printer::JUSTIFY_CENTER);
//logo
$img = EscposImage::load($monzo);
$printer -> graphics($img);

//GET MONZO DATA
$ch = curl_init();
$access_token = "";

curl_setopt($ch, CURLOPT_URL, "https://api.monzo.com/balance?account_id=acc_00009NVp08a46H5Sr2jaSH");
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    $printer->text("Boo :( Monzo isn't working");
} else {
    $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resultStatus == 200) {
        $result = json_decode($result, true);
        $printer -> text("Balance: £" . $result['balance']/100 . "\n");
        $printer -> text("Today: £" . $result['spend_today']/100 . "\n");
    } else {
        $printer->text("Boo :( Monzo isn't working - " . $resultStatus);
    }
}


curl_close($ch);
*/

//end
$printer -> cut();
$printer->close();
?>