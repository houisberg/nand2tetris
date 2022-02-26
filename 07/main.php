<?php
require_once("parser.php");
require_once("code_writer.php");
require_once("constants.php");

$vmFile = $argv[1];
$parser = new Parser($vmFile);

$outputFileName = "test.asm";
$codeWriter = new CodeWriter($outputFileName);
$codeWriter->setFileName($outputFileName);
$twoArgsCommands = [C_PUSH, C_POP, C_FUNCTION, C_CALL];
// foreach ($filePaths as $filePath) {
    while ($parser->hasMoreCommands()) {
        $parser->advance();

        if ($parser->currentLine === '') {
            continue;
        }

        if ($parser->commandType() !== C_RETURN) {
            if (in_array($parser->commandType(), $twoArgsCommands, true)) {
                $command = $parser->commandType() === C_PUSH ? PUSH : POP;
                $codeWriter->writePushPop($command, $parser->arg1(), (int) $parser->arg2());
            } else {
                $codeWriter->writeArithmetic($parser->arg1());
            }
        }
    }
// }

$codeWriter->close();
echo '変換終了';