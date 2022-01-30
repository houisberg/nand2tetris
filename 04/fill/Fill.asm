// This file is part of www.nand2tetris.org
// and the book "The Elements of Computing Systems"
// by Nisan and Schocken, MIT Press.
// File name: projects/04/Fill.asm

// Runs an infinite loop that listens to the keyboard input.
// When a key is pressed (any key), the program blackens the screen,
// i.e. writes "black" in every pixel;
// the screen should remain fully black as long as the key is pressed. 
// When no key is pressed, the program clears the screen, i.e. writes
// "white" in every pixel;
// the screen should remain fully clear as long as no key is pressed.

    @8192   // 8K
    D=A
    @SCREEN
    D=D+A   // スクリーン開始アドレス + 総塗りつぶしビット数 = スクリーンアドレス終端
    @screen_last_bit_address
    M=D
(LOOP)
    // スクリーンの開始アドレスを@addressに保持
    @SCREEN
    D=A
    @address
    M=D

    // キー入力判定
    @KBD
    D=M
    
    @SETWH
    D;JEQ
    @SETBK
    D;JGT
    (SETBK)
        @color
        M=-1
        @FILL
        0;JMP
    (SETBKEND)
    (SETWH)
        @color
        M=0
        @FILL
        0;JMP
    (SETWHEND)
    (FILL)
        // @colorの値に塗りつぶし
        @color
        D=M
        @address
        A=M     // @addressに保持していたアドレスに移動
        M=D     // アドレス位置の値を@colorに

        D=A+1   // アドレスを+1移動
        @address
        M=D     // 変数を+1したアドレス位置に更新

        @screen_last_bit_address
        D=M-D   // スクリーン終端なら0
        @FILL
        D;JNE

        // 無限ループ
        @LOOP
        0;JMP