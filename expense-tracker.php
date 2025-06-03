#!/usr/bin/env php
<?php

function dd($debug) {
    echo "<pre>";
    var_dump($debug);
    echo "</pre>";
}

define('EXPENSES_FILE', __DIR__ . '/expenses.json');

function loadFileIfNotExist() {
    if(!file_exists(EXPENSES_FILE)) {
        file_put_contents(EXPENSES_FILE, json_encode([]));
    }
}

function getFileExpenses() {
    loadFileIfNotExist();
    //Obtener los datos anteriores
    $array = json_decode(file_get_contents(EXPENSES_FILE), true);
    return $array;
}

function parseArgs($argv) {
    $array = [];

    $expensesJson = getFileExpenses();
    $id = end($expensesJson)["id"] + 1;

    $array["id"] = $id ?? 1;
    $array["description"] = $argv[3];
    $array["amount"] = floatval($argv[5] ?? null);
    $array["date"] = date("Y-m-d H:i:s a");

    return $array;
}

$command = $argv[1] ?? '';
$args = parseArgs($argv);

switch ($command) {
    case 'add':
        addExpense($args, $argv); 
        break;
    case "list":
        viewAllExpenses();
        break;
    case "summary":
        summary();
        break;
    default:
        echo "Comandos disponibles: add, list, summary, delete, update, set-budget, export\n";
        break;
}

function addExpense($args, $argv) {
    
    if (
        !isset($argv[2]) || !isset($argv[4]) || 
        !isset($args["description"]) || !isset($args["amount"]) || 
        $argv[2] != "--description" || $argv[4] != "--amount" || 
        !isset($argv[5])
    ) {
        echo "\n\033[31m Tienes que agregar una descripcion y un monto \033[0m \n";

        echo "Ejemplo: \033[33mphp\033[0m expense-tracker.php add \033[90m--description\033[0m \033[36m\"Lunch\"\033[0m \033[90m--amount\033[0m 20 \n\n";

        return;
    }

    $expensesJson = getFileExpenses();
    $expensesJson[] = $args;
    $expenses = json_encode($expensesJson, JSON_PRETTY_PRINT);

    file_put_contents(EXPENSES_FILE, $expenses);
}

function viewAllExpenses() {
    loadFileIfNotExist();

    $expensesJson = getFileExpenses();
    $rows = [];
    foreach ($expensesJson as $key => $value) {
        $array = [];
        
        $array[] = $value["id"];
        $array[] = $value["date"];
        $array[] = $value["description"];
        $array[] = $value["amount"];

        $rows[] = $array;
    }

    listFormated($rows);
}

function listFormated($rows) {
    echo "\n";
    echo "\033[33m" . str_pad("# ID", 6);
    echo str_pad("Date", 25);
    echo str_pad("Description", 52);
    echo "Amount\033[0m\n";

    // Línea divisoria en gris
    echo "\033[90m" . str_repeat("-", 90) . "\033[0m\n";

    foreach ($rows as $row) {
        echo "\033[34m" . str_pad("# " . $row[0], 6) . "\033[0m";
        echo "\033[36m" . str_pad($row[1], 25) . "\033[0m";
        echo "\033[37m" . str_pad($row[2], 52) . "\033[0m";
        echo "\033[32m$" . number_format($row[3], 2) . "\033[0m\n";
    }
    echo "\n";
}

function summary() {

}