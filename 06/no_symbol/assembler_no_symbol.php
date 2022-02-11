<?php

require_once("parser.php");
require_once("code.php");

$parser = new Parser($argv[1]);
$code = new Code();
$hack = fopen('Prog.hack', 'w');

while ($parser->hasMoreCommands()) {
    $parser->advance();
    
    if ($parser->currentLine === '') {
        continue;
    }
    $commandType = $parser->commandType();
    $bin = '';
    if ($commandType == $parser::C_COMMAND) {
        $dest = $code->dest($parser->dest());
        $comp = $code->comp($parser->comp());
        $jump = $code->jump($parser->jump());
        $bin = '111'. $comp. $dest. $jump;
    } else {
        $symbol = $parser->symbol();
        $bin = '0'. $symbol;
    }
    fwrite($hack, $bin. "\n");
}
fclose($parser->filePointer);
fclose($hack);