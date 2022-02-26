<?php

class CodeWriter {

    public $filePointer;

    public string $currentLine;

    public string $vmFileName;

    private array $binaryFuncs = [
        ADD => 'D=D+M',
        SUB => 'D=M-D',
        A_AND => 'D=D&M',
        A_OR => 'D=D|M',
    ];

    private array $binaryCompareFuncs = [
        EQ => 'JEQ',
        GT => 'JGT',
        LT => 'JLT',
    ];
    
    private array $unaryFuncs = [
        NEG => 'M=-M',
        A_NOT => 'M=!M',
    ];

    private int $labelIndex = 0;

    public function __construct($file)
    {
        $this->filePointer = fopen($file, 'w');
    }

    public function setFileName($fileName)
    {
        $this->vmFileName = $fileName;
    }

    /**
     * 算術コマンドを変換
     *
     * @param string $command 算術コマンド
     * @return void
     */
    public function writeArithmetic(string $command)
    {

        if (in_array($command, [ADD, SUB, A_AND, A_OR])) {
            $this->decrementStackPointer();
            $this->writeCode(['D=M']);
            $this->decrementStackPointer();
            $this->writeCode([$this->binaryFuncs[$command]]);
            $this->incrementStackPointer();
        } else if (in_array($command, [EQ, GT, LT])) {
            $this->decrementStackPointer();
            $this->writeCode(['D=M']);
            $this->decrementStackPointer();
            // ラベルを用いて二値比較のHackコードを実現する
            $trueLabel = $this->getLabel('true');
            $falseLabel = $this->getLabel('false');
            $this->writeCode([
                'D=M-D',
                '@'. $trueLabel,
                'D;'. $this->binaryCompareFuncs[$command],
                '@'. $falseLabel,
                '0;JMP',
                '('. $trueLabel. ')',
                '@result',
                'M=-1',
                '@BLOCK_END_'. $this->labelIndex,
                '0;JMP',
                '('. $falseLabel. ')',
                '@result',
                'M=0',
                '(BLOCK_END_'. $this->labelIndex.')',
                '@result',
                'D=M',
            ]);
            $this->incrementStackPointer();
            var_dump('comp');
        } else if (in_array($command, [NEG, A_NOT])) {
            // $this->decrementStackPointer();
            $this->writeCode([
                '@SP',
                'A=M-1',
            ]);
            $this->writeCode([$this->unaryFuncs[$command]]);
            // $this->incrementStackPointer();
            var_dump('unary');
        }
    }


    /**
     * メモリアクセスコマンドを変換
     *
     * @param [type] $command
     * @param [type] $segment
     * @param string $index
     * @return void
     */
    public function writePushPop(string $command, string $segment, int $index)
    {
        if ($command === PUSH) {
            $this->push($segment, $index);
        }
    }

    /**
     * スタックにプッシュ 実行後はSPを+1する
     *
     * @param string $segment
     * @param integer $index
     * @return void
     */
    private function push(string $segment, int $index)
    {
        if ($segment === CONSTANT) {
            $this->writeCode([
                '@'. $index,
                'D=A'
            ]);
        }
        $this->incrementStackPointer();
    }
    
    private function incrementStackPointer()
    {
        $this->writeCode([
            "@SP",
            "A=M",
            "M=D",
            "@SP",
            "M=M+1"
        ]);
    }
    private function decrementStackPointer()
    {
        $this->writeCode([
            '@SP',
            'M=M-1',
            'A=M',
        ]);
    }

    private function getLabel(string $title)
    {
        $label = $title. $this->labelIndex;
        $this->labelIndex++;
        return $label;
    }

    private function writeCode(array $codes) {
        $code = implode("\n", $codes);
        fwrite($this->filePointer, $code. "\n");
    }

    public function close()
    {
        fclose($this->filePointer);
    }
}