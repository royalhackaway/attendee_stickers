<?php

$dinnerChoices = readCSV("data/choices.csv");
$output = createOutput("data");

$deduped = [];
foreach ($dinnerChoices as $key => $row) {
    $matches = [];
    foreach ($dinnerChoices as $key1 => $value) {
        if($value[1] == $row[1]){
            array_push($matches, $value);
        }
    }

    echo $matches[0][1] . "\n";

    if(sizeof($matches) != 1){
        if(in_array($matches[0][1], $deduped)){
            echo "Already deduped \n";
        } else {
            foreach ($matches as $key2 => $value) {
                echo "[" . $key2 . "] " . $value[2] . " | " . $value[3] . "\n"; 
            }
    
            if(in_array(($input = readline("Line number: ")), range(0, sizeof($matches) -1))){
                writeOutput($output, $matches[$input]);
            } else {
                throw new exception("Choice out of bounds");
            }
            array_push($deduped, $matches[0][1]);
        }
    } else {
        writeOutput($output, $matches[0]);
    }
}

function readCSV($fname){
    if(file_exists($fname)){
        return array_map('str_getcsv', file($fname));
    } else {
        throw new exception("Missing CSV to import");
    }
}

function createOutput($dir){
    return fopen($dir . "/choices-output_" . time() . ".csv", "x");
}

function writeOutput($handle, $row){
    fputcsv($handle, $row);
}