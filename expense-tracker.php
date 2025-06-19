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
    $array["description"] = $argv[3] ?? null;
    $array["amount"] = floatval($argv[5] ?? null);
    $array["date"] = date("Y-m-d");
    if(isset($argv[7])) {
        $array["idUpdate"] = $argv[7] ?? null;
    }

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
    case "update":
        update($args, $argv);
        break;
    case "delete":
        delete($argv);
        break;
    case "summary":
        summary($argv);
        break;
    default:
        echo "Comandos disponibles: add, list, summary, delete, update, set-budget, export\n";
        break;
}

function validate($args, $argv) {
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
    return true;
}

function addExpense($args, $argv) {
    
    $validate = validate($args, $argv);

    if($validate) {
        $expensesJson = getFileExpenses();
        $expensesJson[] = $args;
        $expenses = json_encode($expensesJson, JSON_PRETTY_PRINT);

        file_put_contents(EXPENSES_FILE, $expenses);

        echo "\033[32m Expense added successfully\033[0m (ID: {$args['id']})";
    }
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

function update($args, $argv) {
    //Validate if exist ID in console;
    if(!array_key_exists("idUpdate", $args)) {
        echo "\n\033[31m Error:\033[0m Para actualizar necesitas agregar un id \n";
        echo "Ejemplo: \033[33mphp\033[0m expense-tracker.php update \033[90m--description\033[0m \033[36m\"Lunch\"\033[0m \033[90m--amount\033[0m 20 \033[90m--id\033[0m 1 \n\n";
        return;
    }
    $validate = validate($args, $argv);

    if($validate) {
        $expenses = getFileExpenses();
        $existIdOnJsonFile = false;

        foreach ($expenses as $key => $value) {
            if($value["id"] == $args["idUpdate"]) {
                $expenses[$key]["description"] = $args["description"];
                $expenses[$key]["amount"] = $args["amount"];
                $existIdOnJsonFile = true;
            }
        }
        if($existIdOnJsonFile) {
            $expensesEncode = json_encode($expenses, JSON_PRETTY_PRINT);
            file_put_contents(EXPENSES_FILE, $expensesEncode);
    
            echo "\033[32m Expense updated successfully\033[0m (ID: {$args['idUpdate']})";
        }
    }       
}

function delete($argv) {

    $expenses = getFileExpenses();
    foreach ($expenses as $key => $value) {
        if($value["id"] == $argv[3]) {
            unset($expenses[$key]);
        }
    }
    $expensesEncode = json_encode($expenses, JSON_PRETTY_PRINT);
    file_put_contents(EXPENSES_FILE, $expensesEncode);

    echo "\033[32m Expense deleted successfully\033[0m (ID: {$argv[3]})";
}

function summary($argv) {
    $range = range(1, 12);

    $expenses = getFileExpenses();
    $amount = 0;

    if(isset($argv[2]) && $argv[2] === "--month" && isset($argv[3])) {
        foreach ($range as $key => $value) {
            if(intval($argv[3]) === $value) {
                foreach ($expenses as $key => $value) {
                    $date = $value["date"];
                    $position = strpos($date, $argv[3]);
                    if($position) {
                        $amount += $value["amount"];
                    }
                }
                echo "\nTotal Expenses of month ${argv[3]}: \033[32m$${amount}\033[0m\n\n";
            }
        }
        if(!in_array($argv[3], $range)) {
            echo "\n\033[31m Error:\033[0m El mes ingresado es incorrecto, el valor debe ser entre 1 y 12 \n\n";
        }
    }

    if(count($argv) === 2) {
        foreach ($expenses as $key => $value) {
            $amount += $value["amount"];
        }
        echo "\nTotal Expenses: \033[32m$${amount}\033[0m\n\n";
    }
}