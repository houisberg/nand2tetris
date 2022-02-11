<?php



class Parser {
    const A_COMMAND = 1;
    const C_COMMAND = 2;
    const L_COMMAND = 3;


    public $filePointer;

    public string $currentLine;    

    /**
     * 初期化
     */
    public function __construct($file)
    {
        $this->filePointer = fopen($file, 'r');
    }

    /**
     * コマンドがまだ残っているか
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
     * @return string
     */
    public function commandType(): string
    {
        if (strpos($this->currentLine, '@') !== false) {
            return self::A_COMMAND;
        } else if(strpos($this->currentLine, '=') !== false || strpos($this->currentLine, ';') !== false) {
            return self::C_COMMAND;
        } else {
            return self::L_COMMAND;
        }
    }

    /**
     * シンボルを返す（数値のみ）
     *
     * @return string
     */
    public function symbol(): string
    {
        $num = str_replace('@', '', $this->currentLine);
        return sprintf('%015d', decbin((int)$num));
    }

    /**
     * destニーモニック取得
     *
     * @return string
     */
    public function dest(): string
    {
        return strstr($this->currentLine, '=', true);
    }
    
    /**
     * compニーモニック取得
     *
     * @return string
     */
    public function comp(): string
    {
        if ($this->dest() !== '') {
            return substr($this->currentLine, strpos($this->currentLine, '=') + 1);
        } else if(strpos($this->currentLine, ';') !== false) {
            return strstr($this->currentLine, ';', true) ;
        }
        
        return '';
    }

    /**
     * jumpニーモニック取得
     *
     * @return string
     */
    public function jump(): string
    {
        if (strpos($this->currentLine, ';') !== false) {
            return substr($this->currentLine, strpos($this->currentLine, ';') + 1);
        }
        return '';
    }
}
