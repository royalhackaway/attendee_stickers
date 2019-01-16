<?php

$dinnerChoices = readCSV("data/data.csv");
$output = createOutput("data");

foreach ($dinnerChoices as $key => $row) {
    $matches = [];
    foreach ($dinnerChoices as $key1 => $value) {
        array_push($matches, $value);
    }

    echo $matches[0][1];

    if(sizeof($matches) != 1){
        foreach ($matches as $key2 => $value) {
            echo "[" . $key2 . "] " . $value[2] . " | " . $value[3]; 
        }

        if(in_array(($input = readline("Line number: ")), range(0, sizeof($matches) -1))){
            createOutput($output, $matches[$input]);
        } else {
            throw new exception("Choice out of bounds");
        }
    } else {
        createOutput($output, $matches[0]);
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
    return fopen($dir . "/output_" . time() . ".csv", "x");
}

function writeOutput($handle, $row){
    return fwrite($handle, $row . "\n\r");
}