<?php

class SymbolTable {

    public $table = [];

    /**
     * 初期化時、テーブルに定義済みシンボルをあらかじめ追加
     */
    public function __construct()
    {
        $this->addEntry('SP', 0);
        $this->addEntry('LCL', 1);
        $this->addEntry('ARG', 2);
        $this->addEntry('THIS', 3);
        $this->addEntry('THAT', 4);
        for ($i = 0; $i <= 15; $i++) {
            $this->addEntry('R'.$i, $i);
        }
        $this->addEntry('SCREEN', 16384);
        $this->addEntry('KBD', 24576);
    }

    /**
     * シンボルテーブルに追加
     *
     * @param string $symbol
     * @param integer $address
     * @return void
     */
    public function addEntry(string $symbol, int $address): void
    {
        $this->table[$symbol] = $address;
    }

    /**
     * テーブルが指定したシンボルを含むか
     *
     * @param string $symbol
     * @return boolean
     */
    public function contains(string $symbol): bool
    {
        return array_key_exists($symbol, $this->table);
    }

    /**
     * シンボルに紐づくアドレス値を返す
     *
     * @param string $symbol
     * @return integer
     */
    public function getAddress(string $symbol): int
    {
        if ($this->contains($symbol)) {
            return $this->table[$symbol];
        }
    }


}