<?php

class CodeWriter {

    public $filePointer;

    public string $currentLine;

    public string $vmFileName;

    /**
     * 二変数関数
     *
     * @var array
     */
    private array $binaryFuncs = [
        ADD => 'D=D+M',
        SUB => 'D=M-D',
        A_AND => 'D=D&M',
        A_OR => 'D=D|M',
    ];

    /**
     * 二変数の比較関数
     *
     * @var array
     */
    private array $binaryCompareFuncs = [
        EQ => 'JEQ',
        GT => 'JGT',
        LT => 'JLT',
    ];
    
    /**
     * 一変数関数
     *
     * @var array
     */
    private array $unaryFuncs = [
        NEG => 'M=-M',
        A_NOT => 'M=!M',
    ];

    /**
     * 定義済みポインタ
     *
     * @var array
     */
    private array $definedPointers = [
        LOCAL => 'LCL',
        ARGUMENT => 'ARG',
        THIS => 'THIS',
        THAT => 'THAT',
        POINTER => 3,
        TEMP => 5,
    ];

    private int $labelIndex = 0;

    public function __construct($file)
    {
        $this->filePointer = fopen($file, 'w');
    }

    /**
     * vmファイル名を指定
     * staticの実装に使う
     *
     * @param [type] $fileName
     * @return void
     */
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
        } else if (in_array($command, [NEG, A_NOT])) {
            $this->writeCode([
                '@SP',
                'A=M-1',
            ]);
            $this->writeCode([$this->unaryFuncs[$command]]);
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
        } else if($command === POP) {
            $this->pop($segment, $index);
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
        } else if(in_array($segment, [LOCAL, ARGUMENT, THIS, THAT])) {
            $this->writeCode([
                '@'. $this->definedPointers[$segment],
                'A=M',
            ]);
            $this->writeCode($this->getAddressIncrementArray($index));
            $this->writeCode(['D=M']);
        } else if (in_array($segment, [POINTER, TEMP])) {
            $this->writeCode([
                '@'. $this->definedPointers[$segment],
            ]);
            $this->writeCode($this->getAddressIncrementArray($index));
            $this->writeCode(['D=M']);
        } else if ($segment === S_STATIC) {
            $this->writeCode([
                '@'. $this->vmFileName.'.'.$index,
                'D=M',
            ]);
        }
        $this->incrementStackPointer();
    }

    /**
     * スタックからポップ 実行前にSPを-1する
     *
     * @param string $segment
     * @param integer $index
     * @return void
     */
    private function pop(string $segment, int $index)
    {
        $this->decrementStackPointer();
        if (in_array($segment, [LOCAL, ARGUMENT, THIS, THAT])) {
            $this->writeCode([
                'D=M',
                '@'. $this->definedPointers[$segment],
                'A=M'
            ]);
            $this->writeCode($this->getAddressIncrementArray($index));
            $this->writeCode(['M=D']);
        } else if(in_array($segment, [POINTER, TEMP])) {
            $this->writeCode([
                'D=M',
                '@'. $this->definedPointers[$segment],
            ]);
            $this->writeCode($this->getAddressIncrementArray($index));
            $this->writeCode(['M=D']);
        } else if ($segment === S_STATIC) {
            $this->writeCode([
                'D=M',
                '@'. $this->vmFileName.'.'.$index,
                'M=D'
            ]);
        }
    }

    /**
     * Aレジスタのアドレスを指定した数だけ移動
     *
     * @param integer $index
     * @return array
     */
    private function getAddressIncrementArray(int $index): array
    {
        $CommandArray = [];
        for($i = 0; $i < $index; $i++) {
            $CommandArray[] = 'A=A+1';
        }
        return $CommandArray;
    }

    /**
     * SPを+1
     *
     * @return void
     */
    private function incrementStackPointer()
    {
        $this->writeCode([
            '@SP',
            'A=M',
            'M=D',
            '@SP',
            'M=M+1'
        ]);
    }

    /**
     * SPを-1
     *
     * @return void
     */
    private function decrementStackPointer()
    {
        $this->writeCode([
            '@SP',
            'M=M-1',
            'A=M',
        ]);
    }

    /**
     * ラベル用文字列を取得
     *
     * @param string $title
     * @return string
     */
    private function getLabel(string $title): string
    {
        $label = $title. $this->labelIndex;
        $this->labelIndex++;
        return $label;
    }

    /**
     * Hack機械語を出力
     *
     * @param array $codes
     * @return void
     */
    private function writeCode(array $codes) {
        $code = implode("\n", $codes);
        fwrite($this->filePointer, $code. "\n");
    }

    /**
     * 出力ファイルを閉じる
     *
     * @return void
     */
    public function close()
    {
        fclose($this->filePointer);
    }
}