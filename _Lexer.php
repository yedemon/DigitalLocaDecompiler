<?php  

  

class Token {  

    public $type;  

    public $value;  

  

    public function __construct($type, $value) {  

        $this->type = $type;  

        $this->value = $value;  

    }  

}  

  

class Lexer {  

    const TYPE_IDENTIFIER = 'IDENTIFIER';  

    const TYPE_NUMBER = 'NUMBER';  

    const TYPE_SYMBOL = 'SYMBOL';  

    const TYPE_STRING = 'STRING';  

    const TYPE_WHITESPACE = 'WHITESPACE';  

  

    private $source;  

    private $position;  

  

    public function __construct($source) {  

        $this->source = $source;  

        $this->position = 0;  

    }  

  

    public function lex() {  

        $tokens = [];  

        while ($this->position < strlen($this->source)) {  

            $char = $this->source[$this->position];  

  

            if (ctype_space($char)) {  

                $this->skipWhitespace();  

                continue;  

            }  

  

            if (ctype_digit($char)) {  

                $number = $this->readNumber();  

                $tokens[] = new Token(self::TYPE_NUMBER, $number);  

                continue;  

            }  

  

            if (ctype_alpha($char) || $char === '_') {  

                $identifier = $this->readIdentifier();  

                $tokens[] = new Token(self::TYPE_IDENTIFIER, $identifier);  

                continue;  

            }  

  

            if ($char === '"' || $char === '\'') {  

                $string = $this->readString($char);  

                $tokens[] = new Token(self::TYPE_STRING, $string);  

                continue;  

            }  

  

            if (in_array($char, ['(', ')', '+', '-', '*', '/'])) {  

                $tokens[] = new Token(self::TYPE_SYMBOL, $char);  

                $this->position++;  

                continue;  

            }  

  

            // 如果遇到未知字符，可以抛出异常或记录错误  

            throw new Exception("Unknown character: '{$char}' at position {$this->position}");  

        }  

  

        return $tokens;  

    }  

  

    private function skipWhitespace() {  

        while ($this->position < strlen($this->source) && ctype_space($this->source[$this->position])) {  

            $this->position++;  

        }  

    }  

  

    private function readNumber() {  

        $start = $this->position;  

        while ($this->position < strlen($this->source) && ctype_digit($this->source[$this->position])) {  

            $this->position++;  

        }  

        return substr($this->source, $start, $this->position - $start);  

    }  

  

    private function readIdentifier() {  

        $start = $this->position;  

        while ($this->position < strlen($this->source) && (ctype_alpha($this->source[$this->position]) || ctype_digit($this->source[$this->position]) || $this->source[$this->position] === '_')) {  

            $this->position++;  

        }  

        return substr($this->source, $start, $this->position - $start);  

    }  

  

    private function readString($delimiter) {  

        $start = $this->position + 1;  

        $escape = false;  

        while ($this->position < strlen($this->source)) {  

            if ($escape) {  

                $escape = false;  

            } elseif ($this->source[$this->position] === $delimiter) {  

                break;  

            } elseif ($this->source[$this->position] === '\\') {  

                $escape = true;  

            }  

            $this->position++;  

        }  

        $this->position++; // Move past the closing delimiter  

        return substr($this->source, $start, $this->position - $start - 1);  

    }  

}  

$a = null;
$b = [];
$b[] = $a;
$b[] = 12;

// 示例用法  

$source = "var x: Integer; begin x := 5 * (10 + 2); WriteLn('Hello, world!'); end.";  

$lexer = new Lexer($source);  

$tokens = $lexer->lex();