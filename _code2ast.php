<?php

/** all gen by GPT-4o */

class ASTNode {
    public $name;
    public $attributes;
    public $children;

    public function __construct($name, $attributes = []) {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->children = [];
    }

    public function addChild($child) {
        $this->children[] = $child;
    }

    public function toXML($level = 0) {
        $indent = str_repeat("  ", $level);
        $xml = $indent . "<" . $this->name;
        foreach ($this->attributes as $key => $value) {
            $xml .= " $key=\"$value\"";
        }
        $xml .= ">\n";
        foreach ($this->children as $child) {
            if ($child instanceof ASTNode) {
                $xml .= $child->toXML($level + 1);
            } else {
                $xml .= $indent . "  " . htmlspecialchars($child) . "\n";
            }
        }
        $xml .= $indent . "</" . $this->name . ">\n";
        return $xml;
    }
}

class Parser {
    private $tokens;
    private $currentToken;
    private $currentIndex;

    public function __construct($code) {
        $this->tokens = $this->tokenize($code);
        $this->currentIndex = 0;
        $this->currentToken = $this->tokens[0];
    }

    private function tokenize($code) {
        $patterns = [
            'procedure' => '/procedure\s+(\w+);/',
            'function' => '/function\s+(\w+)\((.*?)\):\s*(\w+);/',
            'var' => '/var\s+([\w,\s]+);/',
            'const' => '/const\s+([\w,\s]+);/',
            'begin' => '/begin/',
            'if' => '/if\s+(.*?)\s+then/',
            'else' => '/else/',
            'for' => '/for\s+(\w+)\s*=\s*(\d+)\s*To\s*(\w+)\s*by\s*(\d+)\s*do/',
            'case' => '/case\s+(\w+)\s+of/',
            'case_value' => '/(\d+),?/',
            'case_value_range' => '/(\d+)\.\.(\d+):/',
            'assignment' => '/(\w+(\[.*?\])?(\.\w+(\.\w+)?)?)\s*=\s*(.*?);/',
            'function_call' => '/(\w+)\((.*?)\);/',
            'literal' => '/\b(True|False|\d+|\d+\.\d+|\'[^\']*\')\b/',
            'identifier' => '/\b(\w+)\b/',
            'end' => '/end;?/',
        ];

        $tokens = [];
        while ($code) {
            foreach ($patterns as $type => $pattern) {
                if (preg_match($pattern, $code, $matches)) {
                    $tokens[] = [$type, $matches];
                    $code = substr($code, strlen($matches[0]));
                    continue 2;
                }
            }
            $code = substr($code, 1);
        }

        return $tokens;
    }

    private function match($type) {
        if ($this->currentToken[0] == $type) {
            $this->currentToken = $this->tokens[++$this->currentIndex];
            return true;
        }
        return false;
    }

    private function parseAssignment() {
        $node = new ASTNode('assignment');
        if ($this->match('identifier')) {
            $variable = $this->currentToken[1][0];
            $this->currentToken = $this->tokens[++$this->currentIndex];

            if ($this->match('assignment')) {
                $value = $this->currentToken[1][0];
                $node->addChild(new ASTNode('target', ['name' => $variable]));
                $node->addChild(new ASTNode('value', ['type' => 'literal'], [$value]));
                return $node;
            }
        }
        return null;
    }

    private function parseBody() {
        $body = new ASTNode('body');
        while ($this->currentToken[0] != 'end') {
            if ($assignment = $this->parseAssignment()) {
                $body->addChild($assignment);
            } else {
                $this->currentToken = $this->tokens[++$this->currentIndex];
            }
        }
        return $body;
    }

    private function parseProcedure() {
        if ($this->match('procedure')) {
            $name = $this->currentToken[1][1];
            $node = new ASTNode('procedure', ['name' => $name]);
            $this->currentToken = $this->tokens[++$this->currentIndex];

            $node->addChild($this->parseBody());
            return $node;
        }
        return null;
    }

    public function parse() {
        $ast = new ASTNode('program');
        while ($this->currentIndex < count($this->tokens)) {
            if ($procedure = $this->parseProcedure()) {
                $ast->addChild($procedure);
            } else {
                $this->currentToken = $this->tokens[++$this->currentIndex];
            }
        }
        return $ast;
    }
}

$code = <<<CODE
procedure cameraSet;
    TrackProperty[0:came_Num].Puppet = True;
    TrackProperty[0:came_Num].Variable.Visible = True;
end;
CODE;

$parser = new Parser($code);
$ast = $parser->parse();
echo $ast->toXML();

// procedure cameraSet;
//     TrackProperty[0:came_Num].Puppet = True;
//     TrackProperty[0:came_Num].Variable.Visible = True;
// end;
// <procedure name="cameraSet">
//   <body>
//     <assignment>
//       <property_access>
//         <array_access name="TrackProperty">
//           <binary_op operator="+">
//             <literal type="int">0</literal>
//             <variable name="came_Num"/>
//           </binary_op>
//         </array_access>
//         <property name="Puppet"/>
//       </property_access>
//       <literal type="boolean">True</literal>
//     </assignment>
//     <assignment>
//       <property_access>
//         <property_access>
//           <array_access name="TrackProperty">
//             <binary_op operator="+">
//               <literal type="int">0</literal>
//               <variable name="came_Num"/>
//             </binary_op>
//           </array_access>
//           <property name="Variable"/>
//         </property_access>
//         <property name="Visible"/>
//       </property_access>
//       <literal type="boolean">True</literal>
//     </assignment>
//   </body>
// </procedure>

// procedure cameraMain;
//  var sp_xz:Float; 
// begin
//   if keyFlag[key_Up] or padFlag[key_Up] then 
//     came_SP = came_SP + came_SPUP;
//   else if came_SP > 0 then 
//     came_SP = came_SP - came_SPDN;
//   end;
//   if GetKeyState(VK_Escape) then 
//     Halt;
// end;
// <procedure name="cameraMain">
//   <body>
//     <if>
//       <condition>
//         <binary_op operator="or">
//           <variable name="keyFlag[key_Up]"/>
//           <variable name="padFlag[key_Up]"/>
//         </binary_op>
//       </condition>
//       <then>
//         <assignment>
//           <variable name="came_SP"/>
//           <binary_op operator="+">
//             <variable name="came_SP"/>
//             <variable name="came_SPUP"/>
//           </binary_op>
//         </assignment>
//       </then>
//       <else>
//         <if>
//           <condition>
//             <binary_op operator=">">
//               <variable name="came_SP"/>
//               <literal type="float">0</literal>
//             </binary_op>
//           </condition>
//           <then>
//             <assignment>
//               <variable name="came_SP"/>
//               <binary_op operator="-">
//                 <variable name="came_SP"/>
//                 <variable name="came_SPDN"/>
//               </binary_op>
//             </assignment>
//           </then>
//         </if>
//       </else>
//     </if>
//     <if>
//       <condition>
//         <function_call name="GetKeyState">
//           <literal type="identifier">VK_Escape</literal>
//         </function_call>
//       </condition>
//       <then>
//         <function_call name="Halt"/>
//       </then>
//     </if>
//   </body>
// </procedure>

// procedure DemoCamera;
//  var x, y, z, rx: Float;
// begin
//   case GameCounter of
//     000:
//       came_vpx = 200;
//       came_vpy = -250;
//     052..152:
//       MainGameCamera; // MainCamera
//   end;
// end;
// <procedure name="DemoCamera">
//   <body>
//     <case>
//       <variable name="GameCounter"/>
//       <case_clause>
//         <case_value>
//           <literal type="int">000</literal>
//         </case_value>
//         <assignment>
//           <variable name="came_vpx"/>
//           <literal type="int">200</literal>
//         </assignment>
//         <assignment>
//           <variable name="came_vpy"/>
//           <literal type="int">-250</literal>
//         </assignment>
//       </case_clause>
//       <case_clause>
//         <case_value_range>
//           <start><literal type="int">052</literal></start>
//           <end><literal type="int">152</literal></end>
//         </case_value_range>
//         <function_call name="MainGameCamera"/>
//       </case_clause>
//     </case>
//   </body>
// </procedure>

// procedure CollisionReSet;
//  var c: integer;
// begin
//   for c = 0 To Map_Count - 1 by 1 do
//     Map_List[c] = 0;
//   end;
// end;
// <procedure name="CollisionReSet">
//   <body>
//     <for_loop>
//       <initialization>
//         <assignment>
//           <variable name="c"/>
//           <literal type="int">0</literal>
//         </assignment>
//       </initialization>
//       <condition>
//         <binary_op operator="<=">
//           <variable name="c"/>
//           <binary_op operator="-">
//             <variable name="Map_Count"/>
//             <literal type="int">1</literal>
//           </binary_op>
//         </binary_op>
//       </condition>
//       <increment>
//         <assignment>
//           <variable name="c"/>
//           <binary_op operator="+">
//             <variable name="c"/>
//             <literal type="int">1</literal>
//           </binary_op>
//         </assignment>
//       </increment>
//       <body>
//         <assignment>
//           <array_access name="Map_List">
//             <variable name="c"/>
//           </array_access>
//           <literal type="int">0</literal>
//         </assignment>
//       </body>
//     </for_loop>
//   </body>
// </procedure>

// function CollisionSet(x, z: Float; num: integer): integer;
//  var pos, ix, iz: integer;
// begin
//   Result = pos;
// end;
// <function name="CollisionSet" return_type="integer">
//   <parameters>
//     <parameter type="Float" name="x"/>
//     <parameter type="Float" name="z"/>
//     <parameter type="integer" name="num"/>
//   </parameters>
//   <body>
//     <assignment>
//       <variable name="Result"/>
//       <variable name="pos"/>
//     </assignment>
//   </body>
// </function>

// procedure EneCollisionSet(num: integer);
//  var c, n: integer; px, pz: Float;
// begin
//   n = num + Map_EneNum;
//   case Ene_Ai[num] of
//     0,1,2,3,4:
//       Ene_pos1[num] = CollisionSet(Ene_vpx[num]+4, Ene_vpz[num], n);
//   end;
// end;
// <procedure name="EneCollisionSet">
//   <body>
//     <assignment>
//       <variable name="n"/>
//       <binary_op operator="+">
//         <variable name="num"/>
//         <variable name="Map_EneNum"/>
//       </binary_op>
//     </assignment>
//     <case>
//       <variable name="Ene_Ai[num]"/>
//       <case_clause>
//         <case_value>
//           <literal type="int">0</literal>
//           <literal type="int">1</literal>
//           <literal type="int">2</literal>
//           <literal type="int">3</literal>
//           <literal type="int">4</literal>
//         </case_value>
//         <assignment>
//           <array_access name="Ene_pos1">
//             <variable name="num"/>
//           </array_access>
//           <function_call name="CollisionSet">
//             <binary_op operator="+">
//               <variable name="Ene_vpx[num]"/>
//               <literal type="int">4</literal>
//             </binary_op>
//             <variable name="Ene_vpz[num]"/>
//             <variable name="n"/>
//           </function_call>
//         </assignment>
//       </case_clause>
//     </case>
//   </body>
// </procedure>

?>
