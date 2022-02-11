<?php

require_once("parser.php");
require_once("code.php");
require_once("symbol_table.php");

$assemblyProg = $argv[1];

$romAddress = 0;
$symbolTable = new SymbolTable();
$firstParser = new Parser($assemblyProg);
while ($firstParser->hasMoreCommands()) {
    $firstParser->advance();

    if ($firstParser->currentLine === '') {
        continue;
    }
    $command = $firstParser->commandType();
    if ($command === $firstParser::L_COMMAND){
        $symbolTable->addEntry($firstParser->symbol(), $romAddress);
    } else {
        $romAddress++;
    }
}
fclose($firstParser->filePointer);

$secondParser = new Parser($assemblyProg);
$code = new Code();
$hack = fopen('Prog.hack', 'w');
$ramAddress = 16;
while ($secondParser->hasMoreCommands()) {
    $secondParser->advance();
    
    if ($secondParser->currentLine === '') {
        continue;
    }
    $commandType = $secondParser->commandType();
    $bin = '';
    if ($commandType === $secondParser::C_COMMAND) {
        $dest = $code->dest($secondParser->dest());
        $comp = $code->comp($secondParser->comp());
        $jump = $code->jump($secondParser->jump());
        $bin = '111'. $comp. $dest. $jump;
    } else if($commandType === $secondParser::A_COMMAND) {
        $symbol = $secondParser->symbol();
        if (is_numeric($symbol)) {
            $bin = '0'. sprintf('%015d', decbin((int)$symbol));
        } else {
            if ($symbolTable->contains($symbol)) {
                $address = $symbolTable->getAddress($symbol);
                $bin = sprintf('%016d', decbin($address));
            } else {
                $symbolTable->addEntry($symbol, $ramAddress);
                $bin = sprintf('%016d', decbin($ramAddress));
                $ramAddress++;
            }
        }
    } else {
        continue;
    }
    fwrite($hack, $bin. "\n");
}

fclose($secondParser->filePointer);
fclose($hack);

echo '変換終了';