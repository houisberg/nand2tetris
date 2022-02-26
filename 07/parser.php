<?php

class Parser {

    private const ARITHMETICS = [
        ADD,
        SUB,
        NEG,
        EQ,
        GT,
        LT,
        A_AND,
        A_NOT,
        A_OR
    ];

    public $filePointer;

    public string $currentLine;

    public function __construct($file)
    {
        $this->filePointer = fopen($file, 'r');
    }

    /**
     * さらにコマンドが存在するか
     *
     * @return boolean
     */
    public function hasMoreCommands(): bool
    {
        return !feof($this->filePointer);
    }

    /**
     * コマンドを進める
     *
     * @return void
     */
    public function advance(): void
    {
        $this->currentLine = trim(fgets($this->filePointer));
        
        if (strpos($this->currentLine, '//') !== false) {
            $this->currentLine = strstr($this->currentLine, '//', true);    // コメント以降削除
        }
    }

    /**
     * 今参照している行のコマンドの種類を返す
     *
     * @return int
     */
    public function commandType(): string
    {
        foreach(self::ARITHMETICS as $arithmetic) {
            if (strpos($this->currentLine, $arithmetic) !== false) {
                return C_ARITHMETIC;
            }
        }

        if (strpos($this->currentLine, PUSH) !== false) {
            return C_PUSH;
        }
        if (strpos($this->currentLine, POP) !== false) {
            return C_POP;
        }
    }

    /**
     * 第一引数を返す
     *
     * @return string
     */
    public function arg1(): string
    {
        if ($this->commandType() === C_ARITHMETIC) {
            return $this->currentLine;
        }
        return explode(' ', $this->currentLine)[1];
    }

    /**
     * 第二引数を返す
     *
     * @return integer
     */
    public function arg2(): string
    {
        return explode(' ', $this->currentLine)[2];
    }
}