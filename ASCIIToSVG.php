<?php
/*
 * This file is a modified version of ASCIIToSVG.php
 *
 * It also includes other slightly modified files from the
 * ASCIIToSVG suite :
 * svg-path.lex.php jlex.php, svg-path.php and colors.php
 * only the <?php opening tag has been commented out.
 *
 * Schplurtz le Déboulonné did the modifications
 */
/*
 * ASCIIToSVG.php: ASCII diagram -> SVG art generator.
 * Copyright © 2012 Devon H. O'Dell <devon.odell@gmail.com>
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  o Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  o Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace dokuwiki\plugin\a2s;

//<?php # vim:ft=php
//<?php # vim:ts=2:sw=2:et:
/*
  Copyright 2006 Wez Furlong, OmniTI Computer Consulting, Inc.
  Based on JLex which is:

       JLEX COPYRIGHT NOTICE, LICENSE, AND DISCLAIMER
  Copyright 1996-2000 by Elliot Joel Berk and C. Scott Ananian 

  Permission to use, copy, modify, and distribute this software and its
  documentation for any purpose and without fee is hereby granted,
  provided that the above copyright notice appear in all copies and that
  both the copyright notice and this permission notice and warranty
  disclaimer appear in supporting documentation, and that the name of
  the authors or their employers not be used in advertising or publicity
  pertaining to distribution of the software without specific, written
  prior permission.

  The authors and their employers disclaim all warranties with regard to
  this software, including all implied warranties of merchantability and
  fitness. In no event shall the authors or their employers be liable
  for any special, indirect or consequential damages or any damages
  whatsoever resulting from loss of use, data or profits, whether in an
  action of contract, negligence or other tortious action, arising out
  of or in connection with the use or performance of this software.
  **************************************************************
*/

class A2S_JLexToken {
  public $line;
  public $col;
  public $value;
  public $type;

  function __construct($type, $value = null, $line = null, $col = null) {
    $this->line = $line;
    $this->col = $col;
    $this->value = $value;
    $this->type = $type;
  }
}

class A2S_JLexBase {
  const YY_F = -1;
  const YY_NO_STATE = -1;
  const YY_NOT_ACCEPT = 0;
  const YY_START = 1;
  const YY_END = 2;
  const YY_NO_ANCHOR = 4;
  const YYEOF = -1;

  protected $YY_BOL;
  protected $YY_EOF;

  protected $yy_reader;
  protected $yy_buffer;
  protected $yy_buffer_read;
  protected $yy_buffer_index;
  protected $yy_buffer_start;
  protected $yy_buffer_end;
  protected $yychar = 0;
  protected $yycol = 0;
  protected $yyline = 0;
  protected $yy_at_bol;
  protected $yy_lexical_state;
  protected $yy_last_was_cr = false;
  protected $yy_count_lines = false;
  protected $yy_count_chars = false;
  protected $yyfilename = null;

  function __construct($stream) {
    $this->yy_reader = $stream;
    $meta = stream_get_meta_data($stream);
    if (!isset($meta['uri'])) {
      $this->yyfilename = '<<input>>';
    } else {
      $this->yyfilename = $meta['uri'];
    }

    $this->yy_buffer = "";
    $this->yy_buffer_read = 0;
    $this->yy_buffer_index = 0;
    $this->yy_buffer_start = 0;
    $this->yy_buffer_end = 0;
    $this->yychar = 0;
    $this->yyline = 1;
    $this->yy_at_bol = true;
  }

  protected function yybegin($state) {
    $this->yy_lexical_state = $state;
  }

  protected function yy_advance() {
    if ($this->yy_buffer_index < $this->yy_buffer_read) {
      if (!isset($this->yy_buffer[$this->yy_buffer_index])) {
        return $this->YY_EOF;
      }
      return ord($this->yy_buffer[$this->yy_buffer_index++]);
    }
    if ($this->yy_buffer_start != 0) {
      /* shunt */
      $j = $this->yy_buffer_read - $this->yy_buffer_start;
      $this->yy_buffer = substr($this->yy_buffer, $this->yy_buffer_start, $j);
      $this->yy_buffer_end -= $this->yy_buffer_start;
      $this->yy_buffer_start = 0;
      $this->yy_buffer_read = $j;
      $this->yy_buffer_index = $j;

      $data = fread($this->yy_reader, 8192);
      if ($data === false || !strlen($data)) return $this->YY_EOF;
      $this->yy_buffer .= $data;
      $this->yy_buffer_read .= strlen($data);
    }

    while ($this->yy_buffer_index >= $this->yy_buffer_read) {
      $data = fread($this->yy_reader, 8192);
      if ($data === false || !strlen($data)) return $this->YY_EOF;
      $this->yy_buffer .= $data;
      $this->yy_buffer_read .= strlen($data);
    }
    return ord($this->yy_buffer[$this->yy_buffer_index++]);
  }

  protected function yy_move_end() {
    if ($this->yy_buffer_end > $this->yy_buffer_start &&
        $this->yy_buffer[$this->yy_buffer_end-1] == "\n")
      $this->yy_buffer_end--;
    if ($this->yy_buffer_end > $this->yy_buffer_start &&
        $this->yy_buffer[$this->yy_buffer_end-1] == "\r")
      $this->yy_buffer_end--;
  }

  protected function yy_mark_start() {
    if ($this->yy_count_lines || $this->yy_count_chars) {
      if ($this->yy_count_lines) {
        for ($i = $this->yy_buffer_start; $i < $this->yy_buffer_index; ++$i) {
          if ("\n" == $this->yy_buffer[$i] && !$this->yy_last_was_cr) {
            ++$this->yyline;
            $this->yycol = 0;
          }
          if ("\r" == $this->yy_buffer[$i]) {
            ++$yyline;
            $this->yycol = 0;
            $this->yy_last_was_cr = true;
          } else {
            $this->yy_last_was_cr = false;
          }
        }
      }
      if ($this->yy_count_chars) {
        $this->yychar += $this->yy_buffer_index - $this->yy_buffer_start;
        $this->yycol += $this->yy_buffer_index - $this->yy_buffer_start;
      }
    }
    $this->yy_buffer_start = $this->yy_buffer_index;
  }

  protected function yy_mark_end() {
    $this->yy_buffer_end = $this->yy_buffer_index;
  }

  protected function yy_to_mark() {
    #echo "yy_to_mark: setting buffer index to ", $this->yy_buffer_end, "\n";
    $this->yy_buffer_index = $this->yy_buffer_end;
    $this->yy_at_bol = ($this->yy_buffer_end > $this->yy_buffer_start) &&
                ("\r" == $this->yy_buffer[$this->yy_buffer_end-1] ||
                 "\n" == $this->yy_buffer[$this->yy_buffer_end-1] ||
                 2028 /* unicode LS */ == $this->yy_buffer[$this->yy_buffer_end-1] ||
                 2029 /* unicode PS */ == $this->yy_buffer[$this->yy_buffer_end-1]);
  }

  protected function yytext() {
    return substr($this->yy_buffer, $this->yy_buffer_start, 
          $this->yy_buffer_end - $this->yy_buffer_start);
  }

  protected function yylength() {
    return $this->yy_buffer_end - $this->yy_buffer_start;
  }

  static $yy_error_string = array(
    'INTERNAL' => "Error: internal error.\n",
    'MATCH' => "Error: Unmatched input.\n"
  );

  protected function yy_error($code, $fatal) {
    print self::$yy_error_string[$code];
    flush();
    if ($fatal) throw new Exception("JLex fatal error " . self::$yy_error_string[$code]);
  }

  /* creates an annotated token */
  function createToken($type = null) {
    if ($type === null) $type = $this->yytext();
    $tok = new A2S_JLexToken($type);
    $this->annotateToken($tok);
    return $tok;
  }

  /* annotates a token with a value and source positioning */
  function annotateToken(A2S_JLexToken $tok) {
    $tok->value = $this->yytext();
    $tok->col = $this->yycol;
    $tok->line = $this->yyline;
    $tok->filename = $this->yyfilename;
  }
}

//<?php # vim:ts=2:sw=2:et:
/* Driver template for the LEMON parser generator.
** The author disclaims copyright to this source code.
*/
/* First off, code is included which follows the "include" declaration
** in the input file. */


/* The following structure represents a single element of the
** parser's stack.  Information stored includes:
**
**   +  The state number for the parser at this level of the stack.
**
**   +  The value of the token stored at this level of the stack.
**      (In other words, the "major" token.)
**
**   +  The semantic value stored at this level of the stack.  This is
**      the information used by the action routines in the grammar.
**      It is sometimes called the "minor" token.
*/
class A2S_SVGPathyyStackEntry {
  var /* int */ $stateno;       /* The state-number */
  var /* int */ $major;         /* The major token value.  This is the code
                     ** number for the token at this stack level */
  var $minor; /* The user-supplied minor token value.  This
                     ** is the value of the token  */
};

/* The state of the parser is completely contained in an instance of
** the following structure */
class A2S_SVGPathParser {
  var /* int */ $yyidx = -1;                    /* Index of top element in stack */
  var /* int */ $yyerrcnt;                 /* Shifts left before out of the error */
  // A2S_SVGPathARG_SDECL                /* A place to hold %extra_argument */
  var /* yyStackEntry */ $yystack = array(
  	/* of YYSTACKDEPTH elements */
	);  /* The parser's stack */

  var $yyTraceFILE = null;
  var $yyTracePrompt = null;



/* Next is all token values, in a form suitable for use by makeheaders.
** This section will be null unless lemon is run with the -m switch.
*/
/* 
** These constants (all generated automatically by the parser generator)
** specify the various kinds of tokens (terminals) that the parser
** understands. 
**
** Each symbol here is a terminal symbol in the grammar.
*/
  const TK_ANY =  1;
  const TK_MCMD =  2;
  const TK_ZCMD =  3;
  const TK_LCMD =  4;
  const TK_HCMD =  5;
  const TK_VCMD =  6;
  const TK_CCMD =  7;
  const TK_SCMD =  8;
  const TK_QCMD =  9;
  const TK_TCMD = 10;
  const TK_ACMD = 11;
  const TK_POSNUM = 12;
  const TK_FLAG = 13;
  const TK_NEGNUM = 14;
/* The next thing included is series of defines which control
** various aspects of the generated parser.
**    YYCODETYPE         is the data type used for storing terminal
**                       and nonterminal numbers.  "unsigned char" is
**                       used if there are fewer than 250 terminals
**                       and nonterminals.  "int" is used otherwise.
**    YYNOCODE           is a number of type YYCODETYPE which corresponds
**                       to no legal terminal or nonterminal number.  This
**                       number is used to fill in empty slots of the hash 
**                       table.
**    YYFALLBACK         If defined, this indicates that one or more tokens
**                       have fall-back values which should be used if the
**                       original value of the token will not parse.
**    YYACTIONTYPE       is the data type used for storing terminal
**                       and nonterminal numbers.  "unsigned char" is
**                       used if there are fewer than 250 rules and
**                       states combined.  "int" is used otherwise.
**    A2S_SVGPathTOKENTYPE     is the data type used for minor tokens given 
**                       directly to the parser from the tokenizer.
**    YYMINORTYPE        is the data type used for all minor tokens.
**                       This is typically a union of many types, one of
**                       which is A2S_SVGPathTOKENTYPE.  The entry in the union
**                       for base tokens is called "yy0".
**    YYSTACKDEPTH       is the maximum depth of the parser's stack.
**    A2S_SVGPathARG_SDECL     A static variable declaration for the %extra_argument
**    A2S_SVGPathARG_PDECL     A parameter declaration for the %extra_argument
**    A2S_SVGPathARG_STORE     Code to store %extra_argument into yypParser
**    A2S_SVGPathARG_FETCH     Code to extract %extra_argument from yypParser
**    YYNSTATE           the combined number of states.
**    YYNRULE            the number of rules in the grammar
**    YYERRORSYMBOL      is the code number of the error symbol.  If not
**                       defined, then do no error processing.
*/
  const YYNOCODE = 48;
  const YYWILDCARD = 1;
#define A2S_SVGPathTOKENTYPE void*
  const YYSTACKDEPTH = 100;
  const YYNSTATE = 74;
  const YYNRULE = 52;
  const YYERRORSYMBOL = 15;

  /* since we cant use expressions to initialize these as class
   * constants, we do so during parser init. */
  var $YY_NO_ACTION;
  var $YY_ACCEPT_ACTION;
  var $YY_ERROR_ACTION;

/* Next are that tables used to determine what action to take based on the
** current state and lookahead token.  These tables are used to implement
** functions that take a state number and lookahead value and return an
** action integer.  
**
** Suppose the action integer is N.  Then the action is determined as
** follows
**
**   0 <= N < YYNSTATE                  Shift N.  That is, push the lookahead
**                                      token onto the stack and goto state N.
**
**   YYNSTATE <= N < YYNSTATE+YYNRULE   Reduce by rule N-YYNSTATE.
**
**   N == YYNSTATE+YYNRULE              A syntax error has occurred.
**
**   N == YYNSTATE+YYNRULE+1            The parser accepts its input.
**
**   N == YYNSTATE+YYNRULE+2            No such action.  Denotes unused
**                                      slots in the yy_action[] table.
**
** The action table is constructed as a single large table named yy_action[].
** Given state S and lookahead X, the action is computed as
**
**      yy_action[ yy_shift_ofst[S] + X ]
**
** If the index value yy_shift_ofst[S]+X is out of range or if the value
** yy_lookahead[yy_shift_ofst[S]+X] is not equal to X or if yy_shift_ofst[S]
** is equal to YY_SHIFT_USE_DFLT, it means that the action is not in the table
** and that yy_default[S] should be used instead.  
**
** The formula above is for computing the action when the lookahead is
** a terminal symbol.  If the lookahead is a non-terminal (as occurs after
** a reduce action) then the yy_reduce_ofst[] array is used in place of
** the yy_shift_ofst[] array and YY_REDUCE_USE_DFLT is used in place of
** YY_SHIFT_USE_DFLT.
**
** The following are the tables generated in this section:
**
**  yy_action[]        A single table containing all actions.
**  yy_lookahead[]     A table containing the lookahead for each entry in
**                     yy_action.  Used to detect hash collisions.
**  yy_shift_ofst[]    For each state, the offset into yy_action for
**                     shifting terminals.
**  yy_reduce_ofst[]   For each state, the offset into yy_action for
**                     shifting non-terminals after a reduce.
**  yy_default[]       Default action for each state.
*/
static $yy_action = array(
 /*     0 */     2,   39,   44,   45,   46,   47,   48,   49,   50,   51,
 /*    10 */    52,   43,   44,   45,   46,   47,   48,   49,   50,   51,
 /*    20 */    52,   53,    7,   20,   16,    4,    5,    3,    9,   26,
 /*    30 */    21,   17,   22,   22,   10,   67,   35,   12,   22,    8,
 /*    40 */    73,   42,    1,   56,   56,   14,   18,   22,   34,   56,
 /*    50 */    22,   11,   70,   40,   19,   30,   63,   22,   56,   15,
 /*    60 */    60,   56,   22,   14,   21,   22,   22,   56,   56,   65,
 /*    70 */    68,   36,    6,   56,   32,  101,   56,   56,   31,  127,
 /*    80 */    25,   41,    1,   17,   27,   22,   57,   58,   59,   29,
 /*    90 */    61,   22,   71,   24,   62,   13,   56,   22,   94,   94,
 /*   100 */    94,   56,   56,   33,   37,   56,   22,  101,   56,   95,
 /*   110 */    95,   95,  101,   66,   69,   22,   22,   56,   64,   23,
 /*   120 */    54,   72,   22,   22,   55,  101,   56,   56,  101,   56,
 /*   130 */   101,  101,  101,   56,   56,   56,   28,   38,
);
static $yy_lookahead = array(
 /*     0 */    20,   21,   22,   23,   24,   25,   26,   27,   28,   29,
 /*    10 */    30,   21,   22,   23,   24,   25,   26,   27,   28,   29,
 /*    20 */    30,    3,    4,    5,    6,    7,    8,    9,   10,   11,
 /*    30 */    33,   33,   35,   35,   37,   38,   33,   13,   35,   41,
 /*    40 */    42,   18,   19,   46,   46,   33,   43,   35,   33,   46,
 /*    50 */    35,   39,   40,   31,   32,   33,   35,   35,   46,   32,
 /*    60 */    33,   46,   35,   33,   33,   35,   35,   46,   46,   38,
 /*    70 */    40,   45,    2,   46,   46,   47,   46,   46,   12,   16,
 /*    80 */    17,   18,   19,   33,   12,   35,   12,   13,   14,   33,
 /*    90 */    35,   35,   42,   34,   35,   33,   46,   35,   12,   13,
 /*   100 */    14,   46,   46,   13,   33,   46,   35,   47,   46,   12,
 /*   110 */    13,   14,   47,   33,   33,   35,   35,   46,   35,   36,
 /*   120 */    33,   33,   35,   35,   35,   47,   46,   46,   47,   46,
 /*   130 */    47,   47,   47,   46,   46,   46,   44,   45,
);
  const YY_SHIFT_USE_DFLT = -1;
  const YY_SHIFT_MAX = 33;
static $yy_shift_ofst = array(
 /*     0 */    70,   18,   18,   74,   74,   74,   74,   74,   74,   74,
 /*    10 */    74,   74,   74,   74,   74,   74,   74,   74,   74,   74,
 /*    20 */    74,   74,   74,   74,   74,   70,   66,   74,   66,   86,
 /*    30 */    97,   72,   90,   24,
);
  const YY_REDUCE_USE_DFLT = -21;
  const YY_REDUCE_MAX = 28;
static $yy_reduce_ofst = array(
 /*     0 */    63,  -20,  -10,   -2,   -3,   12,   22,   27,   50,    3,
 /*    10 */    31,   30,   71,   80,   81,   87,   83,   88,   15,   56,
 /*    20 */    59,   62,   89,   21,   55,   23,   92,   28,   26,
);
static $yy_default = array(
 /*     0 */   126,  126,   77,  126,  126,  126,  126,  126,  110,  126,
 /*    10 */   102,  106,  126,  126,  126,   93,  126,  126,  114,  126,
 /*    20 */   126,  126,  126,   99,   96,   74,  126,  126,  117,   90,
 /*    30 */    91,  126,  126,  126,  115,  116,  118,  120,  119,   79,
 /*    40 */    89,   76,   75,   78,   80,   81,   82,   83,   84,   85,
 /*    50 */    86,   87,   88,   92,   94,  121,  122,  123,  124,  125,
 /*    60 */    95,   97,   98,  100,  101,  103,  105,  104,  107,  109,
 /*    70 */   108,  111,  113,  112,
);

/* The next table maps tokens into fallback tokens.  If a construct
** like the following:
** 
**      %fallback ID X Y Z.
**
** appears in the grammer, then ID becomes a fallback token for X, Y,
** and Z.  Whenever one of the tokens X, Y, or Z is input to the parser
** but it does not parse, the type of the token is changed to ID and
** the parse is retried before an error is thrown.
*/
static $yyFallback = array(
);

/* 
** Turn parser tracing on by giving a stream to which to write the trace
** and a prompt to preface each trace message.  Tracing is turned off
** by making either argument NULL 
**
** Inputs:
** <ul>
** <li> A FILE* to which trace output should be written.
**      If NULL, then tracing is turned off.
** <li> A prefix string written at the beginning of every
**      line of trace output.  If NULL, then tracing is
**      turned off.
** </ul>
**
** Outputs:
** None.
*/
function A2S_SVGPathTrace(/* stream */ $TraceFILE, /* string */ $zTracePrompt){
  $this->yyTraceFILE = $TraceFILE;
  $this->yyTracePrompt = $zTracePrompt;
  if( $this->yyTraceFILE===null ) $this->yyTracePrompt = null;
  else if( $this->yyTracePrompt===null ) $this->yyTraceFILE = null;
}

/* For tracing shifts, the names of all terminals and nonterminals
** are required.  The following table supplies these names */
static $yyTokenName = array( 
  '$',             'ANY',           'MCMD',          'ZCMD',        
  'LCMD',          'HCMD',          'VCMD',          'CCMD',        
  'SCMD',          'QCMD',          'TCMD',          'ACMD',        
  'POSNUM',        'FLAG',          'NEGNUM',        'error',       
  'svg_path',      'moveto_drawto_command_groups',  'moveto_drawto_command_group',  'moveto',      
  'drawto_commands',  'drawto_command',  'closepath',     'lineto',      
  'horizontal_lineto',  'vertical_lineto',  'curveto',       'smooth_curveto',
  'quadratic_bezier_curveto',  'smooth_quadratic_bezier_curveto',  'elliptical_arc',  'moveto_argument_sequence',
  'lineto_argument_sequence',  'coordinate_pair',  'horizontal_lineto_argument_sequence',  'coordinate',  
  'vertical_lineto_argument_sequence',  'curveto_argument_sequence',  'curveto_argument',  'smooth_curveto_argument_sequence',
  'smooth_curveto_argument',  'quadratic_bezier_curveto_argument_sequence',  'quadratic_bezier_curveto_argument',  'smooth_quadratic_bezier_curveto_argument_sequence',
  'elliptical_arc_argument_sequence',  'elliptical_arc_argument',  'number',      
);

/* For tracing reduce actions, the names of all rules are required.
*/
static $yyRuleName = array(
 /*   0 */ "svg_path ::= moveto_drawto_command_groups",
 /*   1 */ "moveto_drawto_command_groups ::= moveto_drawto_command_groups moveto_drawto_command_group",
 /*   2 */ "moveto_drawto_command_groups ::= moveto_drawto_command_group",
 /*   3 */ "moveto_drawto_command_group ::= moveto drawto_commands",
 /*   4 */ "drawto_commands ::= drawto_commands drawto_command",
 /*   5 */ "drawto_commands ::= drawto_command",
 /*   6 */ "drawto_command ::= closepath",
 /*   7 */ "drawto_command ::= lineto",
 /*   8 */ "drawto_command ::= horizontal_lineto",
 /*   9 */ "drawto_command ::= vertical_lineto",
 /*  10 */ "drawto_command ::= curveto",
 /*  11 */ "drawto_command ::= smooth_curveto",
 /*  12 */ "drawto_command ::= quadratic_bezier_curveto",
 /*  13 */ "drawto_command ::= smooth_quadratic_bezier_curveto",
 /*  14 */ "drawto_command ::= elliptical_arc",
 /*  15 */ "moveto ::= MCMD moveto_argument_sequence",
 /*  16 */ "moveto_argument_sequence ::= lineto_argument_sequence coordinate_pair",
 /*  17 */ "moveto_argument_sequence ::= coordinate_pair",
 /*  18 */ "closepath ::= ZCMD",
 /*  19 */ "lineto ::= LCMD lineto_argument_sequence",
 /*  20 */ "lineto_argument_sequence ::= lineto_argument_sequence coordinate_pair",
 /*  21 */ "lineto_argument_sequence ::= coordinate_pair",
 /*  22 */ "horizontal_lineto ::= HCMD horizontal_lineto_argument_sequence",
 /*  23 */ "horizontal_lineto_argument_sequence ::= horizontal_lineto_argument_sequence coordinate",
 /*  24 */ "horizontal_lineto_argument_sequence ::= coordinate",
 /*  25 */ "vertical_lineto ::= VCMD vertical_lineto_argument_sequence",
 /*  26 */ "vertical_lineto_argument_sequence ::= vertical_lineto_argument_sequence coordinate",
 /*  27 */ "vertical_lineto_argument_sequence ::= coordinate",
 /*  28 */ "curveto ::= CCMD curveto_argument_sequence",
 /*  29 */ "curveto_argument_sequence ::= curveto_argument_sequence curveto_argument",
 /*  30 */ "curveto_argument_sequence ::= curveto_argument",
 /*  31 */ "curveto_argument ::= coordinate_pair coordinate_pair coordinate_pair",
 /*  32 */ "smooth_curveto ::= SCMD smooth_curveto_argument_sequence",
 /*  33 */ "smooth_curveto_argument_sequence ::= smooth_curveto_argument_sequence smooth_curveto_argument",
 /*  34 */ "smooth_curveto_argument_sequence ::= smooth_curveto_argument",
 /*  35 */ "smooth_curveto_argument ::= coordinate_pair coordinate_pair",
 /*  36 */ "quadratic_bezier_curveto ::= QCMD quadratic_bezier_curveto_argument_sequence",
 /*  37 */ "quadratic_bezier_curveto_argument_sequence ::= quadratic_bezier_curveto_argument_sequence quadratic_bezier_curveto_argument",
 /*  38 */ "quadratic_bezier_curveto_argument_sequence ::= quadratic_bezier_curveto_argument",
 /*  39 */ "quadratic_bezier_curveto_argument ::= coordinate_pair coordinate_pair",
 /*  40 */ "smooth_quadratic_bezier_curveto ::= TCMD smooth_quadratic_bezier_curveto_argument_sequence",
 /*  41 */ "smooth_quadratic_bezier_curveto_argument_sequence ::= smooth_quadratic_bezier_curveto_argument_sequence coordinate_pair",
 /*  42 */ "smooth_quadratic_bezier_curveto_argument_sequence ::= coordinate_pair",
 /*  43 */ "elliptical_arc ::= ACMD elliptical_arc_argument_sequence",
 /*  44 */ "elliptical_arc_argument_sequence ::= elliptical_arc_argument_sequence elliptical_arc_argument",
 /*  45 */ "elliptical_arc_argument_sequence ::= elliptical_arc_argument",
 /*  46 */ "elliptical_arc_argument ::= POSNUM POSNUM number FLAG FLAG coordinate_pair",
 /*  47 */ "coordinate_pair ::= coordinate coordinate",
 /*  48 */ "coordinate ::= number",
 /*  49 */ "number ::= POSNUM",
 /*  50 */ "number ::= FLAG",
 /*  51 */ "number ::= NEGNUM",
);

/*
** This function returns the symbolic name associated with a token
** value.
*/
function A2S_SVGPathTokenName(/* int */ $tokenType){
  if (isset(self::$yyTokenName[$tokenType]))
    return self::$yyTokenName[$tokenType];
  return "Unknown";
}

/* The following function deletes the value associated with a
** symbol.  The symbol can be either a terminal or nonterminal.
** "yymajor" is the symbol code, and "yypminor" is a pointer to
** the value.
*/
private function yy_destructor($yymajor, $yypminor){
  switch( $yymajor ){
    /* Here is inserted the actions which take place when a
    ** terminal or non-terminal is destroyed.  This can happen
    ** when the symbol is popped from the stack during a
    ** reduce or during error processing or when a parser is 
    ** being destroyed before it is finished parsing.
    **
    ** Note: during a reduce, the only symbols destroyed are those
    ** which appear on the RHS of the rule, but which are not used
    ** inside the C code.
    */
    default:  break;   /* If no destructor action specified: do nothing */
  }
}

/*
** Pop the parser's stack once.
**
** If there is a destructor routine associated with the token which
** is popped from the stack, then call it.
**
** Return the major token number for the symbol popped.
*/
private function yy_pop_parser_stack() {
  if ($this->yyidx < 0) return 0;
  $yytos = $this->yystack[$this->yyidx];
  if( $this->yyTraceFILE ) {
    fprintf($this->yyTraceFILE,"%sPopping %s\n",
      $this->yyTracePrompt,
      self::$yyTokenName[$yytos->major]);
  }
  $this->yy_destructor( $yytos->major, $yytos->minor);
  unset($this->yystack[$this->yyidx]);
  $this->yyidx--;
  return $yytos->major;
}

/* 
** Deallocate and destroy a parser.  Destructors are all called for
** all stack elements before shutting the parser down.
**
** Inputs:
** <ul>
** <li>  A pointer to the parser.  This should be a pointer
**       obtained from A2S_SVGPathAlloc.
** <li>  A pointer to a function used to reclaim memory obtained
**       from malloc.
** </ul>
*/
function __destruct()
{
  while($this->yyidx >= 0)
    $this->yy_pop_parser_stack();
}

/*
** Find the appropriate action for a parser given the terminal
** look-ahead token iLookAhead.
**
** If the look-ahead token is YYNOCODE, then check to see if the action is
** independent of the look-ahead.  If it is, return the action, otherwise
** return YY_NO_ACTION.
*/
private function yy_find_shift_action(
  $iLookAhead     /* The look-ahead token */
){
  $i = 0;
  $stateno = $this->yystack[$this->yyidx]->stateno;
 
  if( $stateno>self::YY_SHIFT_MAX || 
      ($i = self::$yy_shift_ofst[$stateno])==self::YY_SHIFT_USE_DFLT ){
    return self::$yy_default[$stateno];
  }
  if( $iLookAhead==self::YYNOCODE ){
    return $this->YY_NO_ACTION;
  }
  $i += $iLookAhead;
  if( $i<0 || $i>=count(self::$yy_action) || self::$yy_lookahead[$i]!=$iLookAhead ){
    if( $iLookAhead>0 ){
      if (isset(self::$yyFallback[$iLookAhead]) &&
        ($iFallback = self::$yyFallback[$iLookAhead]) != 0) {
        if( $this->yyTraceFILE ){
          fprintf($this->yyTraceFILE, "%sFALLBACK %s => %s\n",
             $this->yyTracePrompt, self::$yyTokenName[$iLookAhead], 
             self::$yyTokenName[$iFallback]);
        }
        return $this->yy_find_shift_action($iFallback);
      }
      {
        $j = $i - $iLookAhead + self::YYWILDCARD;
        if( $j>=0 && $j<count(self::$yy_action) && self::$yy_lookahead[$j]==self::YYWILDCARD ){
          if( $this->yyTraceFILE ){
            fprintf($this->yyTraceFILE, "%sWILDCARD %s => %s\n",
               $this->yyTracePrompt, self::$yyTokenName[$iLookAhead],
               self::$yyTokenName[self::YYWILDCARD]);
          }
          return self::$yy_action[$j];
        }
      }
    }
    return self::$yy_default[$stateno];
  }else{
    return self::$yy_action[$i];
  }
}

/*
** Find the appropriate action for a parser given the non-terminal
** look-ahead token iLookAhead.
**
** If the look-ahead token is YYNOCODE, then check to see if the action is
** independent of the look-ahead.  If it is, return the action, otherwise
** return YY_NO_ACTION.
*/
private function yy_find_reduce_action(
  $stateno,              /* Current state number */
  $iLookAhead     /* The look-ahead token */
){
  $i = 0;
 
  if( $stateno>self::YY_REDUCE_MAX ||
      ($i = self::$yy_reduce_ofst[$stateno])==self::YY_REDUCE_USE_DFLT ){
    return self::$yy_default[$stateno];
  }
  if( $iLookAhead==self::YYNOCODE ){
    return $this->YY_NO_ACTION;
  }
  $i += $iLookAhead;
  if( $i<0 || $i>=count(self::$yy_action) || self::$yy_lookahead[$i]!=$iLookAhead ){
    return self::$yy_default[$stateno];
  }else{
    return self::$yy_action[$i];
  }
}

/*
** Perform a shift action.
*/
private function yy_shift(
  $yyNewState,               /* The new state to shift in */
  $yyMajor,                  /* The major token to shift in */
  $yypMinor         /* Pointer ot the minor token to shift in */
){
  $this->yyidx++;
  if (isset($this->yystack[$this->yyidx])) {
    $yytos = $this->yystack[$this->yyidx];
  } else {
    $yytos = new A2S_SVGPathyyStackEntry;
    $this->yystack[$this->yyidx] = $yytos;
  }
  $yytos->stateno = $yyNewState;
  $yytos->major = $yyMajor;
  $yytos->minor = $yypMinor;
  if( $this->yyTraceFILE) {
    fprintf($this->yyTraceFILE,"%sShift %d\n",$this->yyTracePrompt,$yyNewState);
    fprintf($this->yyTraceFILE,"%sStack:",$this->yyTracePrompt);
    for ($i = 1; $i <= $this->yyidx; $i++) {
      $ent = $this->yystack[$i];
      fprintf($this->yyTraceFILE," %s",self::$yyTokenName[$ent->major]);
    }
    fprintf($this->yyTraceFILE,"\n");
  }
}

private function __overflow_dead_code() {
  /* if the stack can overflow (it can't in the PHP implementation)
   * Then the following code would be emitted */
}

/* The following table contains information about every rule that
** is used during the reduce.
** Rather than pollute memory with a large number of arrays,
** we store both data points in the same array, indexing by
** rule number * 2.
static const struct {
  YYCODETYPE lhs;         // Symbol on the left-hand side of the rule 
  unsigned char nrhs;     // Number of right-hand side symbols in the rule
} yyRuleInfo[] = {
*/
static $yyRuleInfo = array(
  16, 1,
  17, 2,
  17, 1,
  18, 2,
  20, 2,
  20, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  21, 1,
  19, 2,
  31, 2,
  31, 1,
  22, 1,
  23, 2,
  32, 2,
  32, 1,
  24, 2,
  34, 2,
  34, 1,
  25, 2,
  36, 2,
  36, 1,
  26, 2,
  37, 2,
  37, 1,
  38, 3,
  27, 2,
  39, 2,
  39, 1,
  40, 2,
  28, 2,
  41, 2,
  41, 1,
  42, 2,
  29, 2,
  43, 2,
  43, 1,
  30, 2,
  44, 2,
  44, 1,
  45, 6,
  33, 2,
  35, 1,
  46, 1,
  46, 1,
  46, 1,
);

/*
** Perform a reduce action and the shift that must immediately
** follow the reduce.
*/
private function yy_reduce(
  $yyruleno                 /* Number of the rule by which to reduce */
){
  $yygoto = 0;                     /* The next state */
  $yyact = 0;                      /* The next action */
  $yygotominor = null;        /* The LHS of the rule reduced */
  $yymsp = null;            /* The top of the parser's stack */
  $yysize = 0;                     /* Amount to pop the stack */
  
  $yymsp = $this->yystack[$this->yyidx];
  if( $this->yyTraceFILE && isset(self::$yyRuleName[$yyruleno])) {
    fprintf($this->yyTraceFILE, "%sReduce [%s].\n", $this->yyTracePrompt,
      self::$yyRuleName[$yyruleno]);
  }

  switch( $yyruleno ){
  /* Beginning here are the reduction cases.  A typical example
  ** follows:
  **   case 0:
  **  #line <lineno> <grammarfile>
  **     { ... }           // User supplied code
  **  #line <lineno> <thisfile>
  **     break;
  */
      case 15:
#line 26 "svg-path.y"
{
		if (count($this->yystack[$this->yyidx + 0]->minor) == 2) {
			$this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor);
		} else { 
			if ($this->yystack[$this->yyidx + -1]->minor->value == 'm') {
				$arr = array ('value' => 'l');
			} else {
				$arr = array ('value' => 'L');
			}
			$c = array_splice($this->yystack[$this->yyidx + 0]->minor, 2);
			$this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor);
			$this->commands[] = array_merge(array($arr), $c);
		}
	}
#line 604 "svg-path.php"
        break;
      case 16:
      case 20:
      case 29:
      case 33:
      case 35:
      case 37:
      case 39:
      case 41:
      case 44:
#line 42 "svg-path.y"
{ $yygotominor = array_merge($this->yystack[$this->yyidx + -1]->minor, $this->yystack[$this->yyidx + 0]->minor); }
#line 617 "svg-path.php"
        break;
      case 17:
      case 21:
      case 30:
      case 34:
      case 38:
      case 42:
      case 45:
      case 48:
      case 49:
      case 50:
      case 51:
#line 43 "svg-path.y"
{ $yygotominor = $this->yystack[$this->yyidx + 0]->minor; }
#line 632 "svg-path.php"
        break;
      case 18:
#line 45 "svg-path.y"
{ $this->commands[] = array($this->yystack[$this->yyidx + 0]->minor); }
#line 637 "svg-path.php"
        break;
      case 19:
      case 22:
      case 25:
      case 28:
      case 32:
      case 36:
      case 40:
      case 43:
#line 48 "svg-path.y"
{ $this->commands[] = array_merge(array($this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor); }
#line 649 "svg-path.php"
        break;
      case 23:
      case 26:
#line 59 "svg-path.y"
{ $yygotominor = array_merge($this->yystack[$this->yyidx + -1]->minor, array($this->yystack[$this->yyidx + 0]->minor)); }
#line 655 "svg-path.php"
        break;
      case 24:
      case 27:
#line 60 "svg-path.y"
{ $yygotominor = array($this->yystack[$this->yyidx + 0]->minor); }
#line 661 "svg-path.php"
        break;
      case 31:
#line 80 "svg-path.y"
{ $yygotominor = array_merge($this->yystack[$this->yyidx + -2]->minor, $this->yystack[$this->yyidx + -1]->minor, $this->yystack[$this->yyidx + 0]->minor); }
#line 666 "svg-path.php"
        break;
      case 46:
#line 131 "svg-path.y"
{ $yygotominor = array_merge(array($this->yystack[$this->yyidx + -5]->minor, $this->yystack[$this->yyidx + -4]->minor, $this->yystack[$this->yyidx + -3]->minor, $this->yystack[$this->yyidx + -2]->minor, $this->yystack[$this->yyidx + -1]->minor), $this->yystack[$this->yyidx + 0]->minor); }
#line 671 "svg-path.php"
        break;
      case 47:
#line 133 "svg-path.y"
{ $yygotominor = array($this->yystack[$this->yyidx + -1]->minor, $this->yystack[$this->yyidx + 0]->minor); }
#line 676 "svg-path.php"
        break;
  };
  $yygoto = self::$yyRuleInfo[2*$yyruleno];
  $yysize = self::$yyRuleInfo[(2*$yyruleno)+1];

  $state_for_reduce = $this->yystack[$this->yyidx - $yysize]->stateno;
  
  $this->yyidx -= $yysize;
  $yyact = $this->yy_find_reduce_action($state_for_reduce,$yygoto);
  if( $yyact < self::YYNSTATE ){
    $this->yy_shift($yyact, $yygoto, $yygotominor);
  }else if( $yyact == self::YYNSTATE + self::YYNRULE + 1 ){
    $this->yy_accept();
  }
}

/*
** The following code executes when the parse fails
*/
private function yy_parse_failed(
){
  if( $this->yyTraceFILE ){
    fprintf($this->yyTraceFILE,"%sFail!\n",$this->yyTracePrompt);
  }
  while( $this->yyidx>=0 ) $this->yy_pop_parser_stack();
  /* Here code is inserted which will be executed whenever the
  ** parser fails */
}

/*
** The following code executes when a syntax error first occurs.
*/
private function yy_syntax_error(
  $yymajor,                   /* The major type of the error token */
  $yyminor            /* The minor type of the error token */
){
}

/*
** The following is executed when the parser accepts
*/
private function yy_accept(
){
  if( $this->yyTraceFILE ){
    fprintf($this->yyTraceFILE,"%sAccept!\n",$this->yyTracePrompt);
  }
  while( $this->yyidx>=0 ) $this->yy_pop_parser_stack();
  /* Here code is inserted which will be executed whenever the
  ** parser accepts */
}

/* The main parser program.
** The first argument is a pointer to a structure obtained from
** "A2S_SVGPathAlloc" which describes the current state of the parser.
** The second argument is the major token number.  The third is
** the minor token.  The fourth optional argument is whatever the
** user wants (and specified in the grammar) and is available for
** use by the action routines.
**
** Inputs:
** <ul>
** <li> A pointer to the parser (an opaque structure.)
** <li> The major token number.
** <li> The minor token number.
** <li> An option argument of a grammar-specified type.
** </ul>
**
** Outputs:
** None.
*/
function A2S_SVGPath(
  $yymajor,                 /* The major token code number */
  $yyminor = null           /* The value for the token */
){
  $yyact = 0;            /* The parser action. */
  $yyendofinput = 0;     /* True if we are at the end of input */
  $yyerrorhit = 0;   /* True if yymajor has invoked an error */

  /* (re)initialize the parser, if necessary */
  if( $this->yyidx<0 ){
    $this->yyidx = 0;
    $this->yyerrcnt = -1;
    $ent = new A2S_SVGPathyyStackEntry;
    $ent->stateno = 0;
    $ent->major = 0;
    $this->yystack = array( 0 => $ent );

    $this->YY_NO_ACTION = self::YYNSTATE + self::YYNRULE + 2;
    $this->YY_ACCEPT_ACTION  = self::YYNSTATE + self::YYNRULE + 1;
    $this->YY_ERROR_ACTION   = self::YYNSTATE + self::YYNRULE;
  }
  $yyendofinput = ($yymajor==0);

  if( $this->yyTraceFILE ){
    fprintf($this->yyTraceFILE,"%sInput %s\n",$this->yyTracePrompt,
      self::$yyTokenName[$yymajor]);
  }

  do{
    $yyact = $this->yy_find_shift_action($yymajor);
    if( $yyact<self::YYNSTATE ){
      $this->yy_shift($yyact,$yymajor,$yyminor);
      $this->yyerrcnt--;
      if( $yyendofinput && $this->yyidx>=0 ){
        $yymajor = 0;
      }else{
        $yymajor = self::YYNOCODE;
      }
    }else if( $yyact < self::YYNSTATE + self::YYNRULE ){
      $this->yy_reduce($yyact-self::YYNSTATE);
    }else if( $yyact == $this->YY_ERROR_ACTION ){
      if( $this->yyTraceFILE ){
        fprintf($this->yyTraceFILE,"%sSyntax Error!\n",$this->yyTracePrompt);
      }
if (self::YYERRORSYMBOL) {
      /* A syntax error has occurred.
      ** The response to an error depends upon whether or not the
      ** grammar defines an error token "ERROR".  
      **
      ** This is what we do if the grammar does define ERROR:
      **
      **  * Call the %syntax_error function.
      **
      **  * Begin popping the stack until we enter a state where
      **    it is legal to shift the error symbol, then shift
      **    the error symbol.
      **
      **  * Set the error count to three.
      **
      **  * Begin accepting and shifting new tokens.  No new error
      **    processing will occur until three tokens have been
      **    shifted successfully.
      **
      */
      if( $this->yyerrcnt<0 ){
        $this->yy_syntax_error($yymajor, $yyminor);
      }
      $yymx = $this->yystack[$this->yyidx]->major;
      if( $yymx==self::YYERRORSYMBOL || $yyerrorhit ){
        if( $this->yyTraceFILE ){
          fprintf($this->yyTraceFILE,"%sDiscard input token %s\n",
             $this->yyTracePrompt,self::$yyTokenName[$yymajor]);
        }
        $this->yy_destructor($yymajor,$yyminor);
        $yymajor = self::YYNOCODE;
      }else{
         while(
          $this->yyidx >= 0 &&
          $yymx != self::YYERRORSYMBOL &&
          ($yyact = $this->yy_find_reduce_action(
                        $this->yystack[$this->yyidx]->stateno,
                        self::YYERRORSYMBOL)) >= self::YYNSTATE
        ){
          $this->yy_pop_parser_stack();
        }
        if( $this->yyidx < 0 || $yymajor==0 ){
          $this->yy_destructor($yymajor,$yyminor);
          $this->yy_parse_failed();
          $yymajor = self::YYNOCODE;
        }else if( $yymx!=self::YYERRORSYMBOL ){
          $this->yy_shift($yyact,self::YYERRORSYMBOL,0);
        }
      }
      $this->yyerrcnt = 3;
      $yyerrorhit = 1;
} else {  /* YYERRORSYMBOL is not defined */
      /* This is what we do if the grammar does not define ERROR:
      **
      **  * Report an error message, and throw away the input token.
      **
      **  * If the input token is $, then fail the parse.
      **
      ** As before, subsequent error messages are suppressed until
      ** three input tokens have been successfully shifted.
      */
      if( $this->yyerrcnt<=0 ){
        $this->yy_syntax_error($yymajor, $yyminor);
      }
      $this->yyerrcnt = 3;
      $this->yy_destructor($yymajor,$yyminor);
      if( $yyendofinput ){
        $this->yy_parse_failed();
      }
      $yymajor = self::YYNOCODE;
}
    }else{
      $this->yy_accept();
      $yymajor = self::YYNOCODE;
    }
  }while( $yymajor!=self::YYNOCODE && $this->yyidx>=0 );
}

}


class A2S_Yylex extends A2S_JLexBase  {
	const YY_BUFFER_SIZE = 512;
	const YY_F = -1;
	const YY_NO_STATE = -1;
	const YY_NOT_ACCEPT = 0;
	const YY_START = 1;
	const YY_END = 2;
	const YY_NO_ANCHOR = 4;
	const YY_BOL = 128;
	var $YY_EOF = 129;

	/* w/e */

	function __construct($stream) {
		parent::__construct($stream);
		$this->yy_lexical_state = self::YYINITIAL;
	}

	const YYINITIAL = 0;
	static $yy_state_dtrans = array(
		0
	);
	static $yy_acpt = array(
		/* 0 */ self::YY_NOT_ACCEPT,
		/* 1 */ self::YY_NO_ANCHOR,
		/* 2 */ self::YY_NO_ANCHOR,
		/* 3 */ self::YY_NO_ANCHOR,
		/* 4 */ self::YY_NO_ANCHOR,
		/* 5 */ self::YY_NO_ANCHOR,
		/* 6 */ self::YY_NO_ANCHOR,
		/* 7 */ self::YY_NO_ANCHOR,
		/* 8 */ self::YY_NO_ANCHOR,
		/* 9 */ self::YY_NO_ANCHOR,
		/* 10 */ self::YY_NO_ANCHOR,
		/* 11 */ self::YY_NO_ANCHOR,
		/* 12 */ self::YY_NO_ANCHOR,
		/* 13 */ self::YY_NO_ANCHOR,
		/* 14 */ self::YY_NO_ANCHOR,
		/* 15 */ self::YY_NO_ANCHOR,
		/* 16 */ self::YY_NO_ANCHOR,
		/* 17 */ self::YY_NO_ANCHOR,
		/* 18 */ self::YY_NO_ANCHOR,
		/* 19 */ self::YY_NO_ANCHOR,
		/* 20 */ self::YY_NO_ANCHOR,
		/* 21 */ self::YY_NOT_ACCEPT,
		/* 22 */ self::YY_NO_ANCHOR,
		/* 23 */ self::YY_NO_ANCHOR,
		/* 24 */ self::YY_NO_ANCHOR,
		/* 25 */ self::YY_NO_ANCHOR,
		/* 26 */ self::YY_NO_ANCHOR,
		/* 27 */ self::YY_NO_ANCHOR,
		/* 28 */ self::YY_NOT_ACCEPT,
		/* 29 */ self::YY_NO_ANCHOR,
		/* 30 */ self::YY_NOT_ACCEPT,
		/* 31 */ self::YY_NO_ANCHOR,
		/* 32 */ self::YY_NOT_ACCEPT,
		/* 33 */ self::YY_NOT_ACCEPT,
		/* 34 */ self::YY_NOT_ACCEPT,
		/* 35 */ self::YY_NOT_ACCEPT,
		/* 36 */ self::YY_NOT_ACCEPT,
		/* 37 */ self::YY_NOT_ACCEPT,
		/* 38 */ self::YY_NOT_ACCEPT
	);
		static $yy_cmap = array(
 19, 19, 19, 19, 19, 19, 19, 19, 19, 18, 18, 19, 18, 18, 19, 19, 19, 19, 19, 19,
 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 18, 19, 19, 19, 19, 19, 19, 19,
 19, 19, 19, 2, 18, 5, 6, 19, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 19, 19,
 19, 19, 19, 19, 19, 17, 19, 14, 19, 7, 19, 19, 11, 19, 19, 19, 10, 8, 19, 19,
 19, 13, 19, 15, 16, 19, 12, 19, 19, 19, 9, 19, 19, 19, 19, 19, 19, 17, 19, 14,
 19, 7, 19, 19, 11, 19, 19, 19, 10, 8, 19, 19, 19, 13, 19, 15, 16, 19, 12, 19,
 19, 19, 9, 19, 1, 19, 19, 19, 0, 0,);

		static $yy_rmap = array(
 0, 1, 1, 2, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 5, 6, 7,
 8, 9, 3, 10, 11, 12, 13, 14, 15, 9, 16, 1, 17, 11, 18, 19, 12, 13, 14,);

		static $yy_nxt = array(
array(
 1, 2, 3, 22, 4, 23, 29, 31, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 31,

),
array(
 -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 4, 4, -1, 21, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 4, 4, -1, 16, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 18, 18, -1, -1, 30, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 17, 17, -1, 19, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 18, 18, -1, -1, 32, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 20, 20, -1, -1, 34, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 20, 20, -1, -1, 35, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 18, 18, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 17, 17, -1, 28, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 24, 24, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 25, 25, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 26, 26, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 27, 27, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, -1, 20, 20, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, 33, 24, 24, 33, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, 36, 25, 25, 36, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, 37, 26, 26, 37, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
array(
 -1, -1, 38, 27, 27, 38, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,

),
);

	public function /*Yytoken*/ nextToken ()
 {
		$yy_anchor = self::YY_NO_ANCHOR;
		$yy_state = self::$yy_state_dtrans[$this->yy_lexical_state];
		$yy_next_state = self::YY_NO_STATE;
		$yy_last_accept_state = self::YY_NO_STATE;
		$yy_initial = true;

		$this->yy_mark_start();
		$yy_this_accept = self::$yy_acpt[$yy_state];
		if (self::YY_NOT_ACCEPT != $yy_this_accept) {
			$yy_last_accept_state = $yy_state;
			$this->yy_mark_end();
		}
		while (true) {
			if ($yy_initial && $this->yy_at_bol) $yy_lookahead = self::YY_BOL;
			else $yy_lookahead = $this->yy_advance();
			$yy_next_state = self::$yy_nxt[self::$yy_rmap[$yy_state]][self::$yy_cmap[$yy_lookahead]];
			if ($this->YY_EOF == $yy_lookahead && true == $yy_initial) {
				return null;
			}
			if (self::YY_F != $yy_next_state) {
				$yy_state = $yy_next_state;
				$yy_initial = false;
				$yy_this_accept = self::$yy_acpt[$yy_state];
				if (self::YY_NOT_ACCEPT != $yy_this_accept) {
					$yy_last_accept_state = $yy_state;
					$this->yy_mark_end();
				}
			}
			else {
				if (self::YY_NO_STATE == $yy_last_accept_state) {
					throw new Exception("Lexical Error: Unmatched Input.");
				}
				else {
					$yy_anchor = self::$yy_acpt[$yy_last_accept_state];
					if (0 != (self::YY_END & $yy_anchor)) {
						$this->yy_move_end();
					}
					$this->yy_to_mark();
					switch ($yy_last_accept_state) {
						case 1:
							
						case -2:
							break;
						case 2:
							{ return $this->createToken(A2S_SVGPathParser::TK_FLAG); }
						case -3:
							break;
						case 3:
							{ }
						case -4:
							break;
						case 4:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -5:
							break;
						case 5:
							{ return $this->createToken(A2S_SVGPathParser::TK_MCMD); }
						case -6:
							break;
						case 6:
							{ return $this->createToken(A2S_SVGPathParser::TK_ZCMD); }
						case -7:
							break;
						case 7:
							{ return $this->createToken(A2S_SVGPathParser::TK_LCMD); }
						case -8:
							break;
						case 8:
							{ return $this->createToken(A2S_SVGPathParser::TK_HCMD); }
						case -9:
							break;
						case 9:
							{ return $this->createToken(A2S_SVGPathParser::TK_VCMD); }
						case -10:
							break;
						case 10:
							{ return $this->createToken(A2S_SVGPathParser::TK_QCMD); }
						case -11:
							break;
						case 11:
							{ return $this->createToken(A2S_SVGPathParser::TK_CCMD); }
						case -12:
							break;
						case 12:
							{ return $this->createToken(A2S_SVGPathParser::TK_SCMD); }
						case -13:
							break;
						case 13:
							{ return $this->createToken(A2S_SVGPathParser::TK_TCMD); }
						case -14:
							break;
						case 14:
							{ return $this->createToken(A2S_SVGPathParser::TK_ACMD); }
						case -15:
							break;
						case 15:
							{ }
						case -16:
							break;
						case 16:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -17:
							break;
						case 17:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -18:
							break;
						case 18:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -19:
							break;
						case 19:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -20:
							break;
						case 20:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -21:
							break;
						case 22:
							{ return $this->createToken(A2S_SVGPathParser::TK_FLAG); }
						case -22:
							break;
						case 23:
							{ }
						case -23:
							break;
						case 24:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -24:
							break;
						case 25:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -25:
							break;
						case 26:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -26:
							break;
						case 27:
							{ return $this->createToken(A2S_SVGPathParser::TK_POSNUM); }
						case -27:
							break;
						case 29:
							{ }
						case -28:
							break;
						case 31:
							{ }
						case -29:
							break;
						default:
						$this->yy_error('INTERNAL',false);
					case -1:
					}
					$yy_initial = true;
					$yy_state = self::$yy_state_dtrans[$this->yy_lexical_state];
					$yy_next_state = self::YY_NO_STATE;
					$yy_last_accept_state = self::YY_NO_STATE;
					$this->yy_mark_start();
					$yy_this_accept = self::$yy_acpt[$yy_state];
					if (self::YY_NOT_ACCEPT != $yy_this_accept) {
						$yy_last_accept_state = $yy_state;
						$this->yy_mark_end();
					}
				}
			}
		}
	}
}
//<?php

$A2S_colors = array(
  "indian red"   => "#cd5c5c",
  "light coral"  => "#f08080",
  "salmon"       => "#fa8072",
  "dark salmon"  => "#e9967a",
  "light salmon" => "#ffa07a",
  "crimson"      => "#dc143c",
  "red"          => "#ff0000",
  "fire brick"   => "#b22222",
  "dark red"     => "#8b0000",
  
  "pink"              => "#ffc0cb",
  "light pink"        => "#ffb6c1",
  "hot pink"          => "#ff69b4",
  "deep pink"         => "#ff1493",
  "medium violet red" => "#c71585",
  "pale violet red"   => "#db7093",
  
  "light salmon" => "#ffa07a",
  "coral"        => "#ff7f50",
  "tomato"       => "#ff6347",
  "orange red"   => "#ff4500",
  "dark orange"  => "#ff8c00",
  "orange"       => "#ffa500",
  
  "gold"                   => "#ffd700",
  "yellow"                 => "#ffff00",
  "light yellow"           => "#ffffe0",
  "lemon chiffon"          => "#fffacd",
  "light goldenrod yellow" => "#fafad2",
  "papaya whip"            => "#ffefd5",
  "moccasin"               => "#ffe4b5",
  "peach puff"             => "#ffdab9",
  "pale goldenrod"         => "#eee8aa",
  "khaki"                  => "#f0e68c",
  "dark khaki"             => "#bdb76b",
      
  "lavender"        => "#e6e6fa",
  "thistle"         => "#d8bfd8",
  "plum"            => "#dda0dd",
  "violet"          => "#ee82ee",
  "orchid"          => "#da70d6",
  "fuchsia"         => "#ff00ff",
  "magenta"         => "#ff00ff",
  "medium orchid"   => "#ba55d3",
  "medium purple"   => "#9370db",
  "blue violet"     => "#8a2be2",
  "dark violet"     => "#9400d3",
  "dark orchid"     => "#9932cc",
  "dark magenta"    => "#8b008b",
  "purple"          => "#800080",
  "indigo"          => "#4b0082",
  "slate blue"      => "#6a5acd",
  "dark slate blue" => "#483d8b",
  
  "green yellow"        => "#adff2f",
  "chartreuse"          => "#7fff00",
  "lawn green"          => "#7cfc00",
  "lime"                => "#00ff00",
  "lime green"          => "#32cd32",
  "pale green"          => "#98fb98",
  "light  green"        => "#90ee90",
  "medium spring green" => "#00fa9a",
  "spring green"        => "#00ff7f",
  "medium sea green"    => "#3cb371",
  "sea green"           => "#2e8b57",
  "forest green"        => "#228b22",
  "green"               => "#008000",
  "dark green"          => "#006400",
  "yellow green"        => "#9acd32",
  "olive drab"          => "#6b8e23",
  "olive"               => "#808000",
  "dark olive green"    => "#556b2f",
  "medium aquamarine"   => "#66cdaa",
  "dark sea green"      => "#8fbc8f",
  "light sea green"     => "#20b2aa",
  "dark cyan"           => "#008b8b",
  "teal"                => "#008080",
  
  "aqua"              => "#00ffff",
  "cyan"              => "#00ffff",
  "light cyan"        => "#e0ffff",
  "pale turquoise"    => "#afeeee",
  "aquamarine"        => "#7fffd4",
  "turquoise"         => "#40e0d0",
  "medium turquoise"  => "#48d1cc",
  "dark turquoise"    => "#00ced1",
  "cadet blue"        => "#5f9ea0",
  "steel blue"        => "#4682b4",
  "light steel blue"  => "#b0c4de",
  "powder blue"       => "#b0e0e6",
  "light blue"        => "#add8e6",
  "sky blue"          => "#87ceeb",
  "light sky blue"    => "#87cefa",
  "deep sky blue"     => "#00bfff",
  "dodger blue"       => "#1e90ff",
  "cornflower blue"   => "#6495ed",
  "medium slate blue" => "#7b68ee",
  "royal blue"        => "#4169e1",
  "blue"              => "#0000ff",
  "medium blue"       => "#0000cd",
  "dark blue"         => "#00008b",
  "navy"              => "#000080",
  "midnight blue"     => "#191970",
      
  "cornsilk"        => "#fff8dc",
  "blanched almond" => "#ffebcd",
  "bisque"          => "#ffe4c4",
  "navajo white"    => "#ffdead",
  "wheat"           => "#f5deb3",
  "burly wood"      => "#deb887",
  "tan"             => "#d2b48c",
  "rosy brown"      => "#bc8f8f",
  "sandy brown"     => "#f4a460",
  "goldenrod"       => "#daa520",
  "dark goldenrod"  => "#b8860b",
  "peru"            => "#cd853f",
  "chocolate"       => "#d2691e",
  "saddle brown"    => "#8b4513",
  "sienna"          => "#a0522d",
  "brown"           => "#a52a2a",
  "maroon"          => "#800000",
      
  "white"          => "#ffffff",
  "snow"           => "#fffafa",
  "honeydew"       => "#f0fff0",
  "mint cream"     => "#f5fffa",
  "azure"          => "#f0ffff",
  "alice blue"     => "#f0f8ff",
  "ghost white"    => "#f8f8ff",
  "white smoke"    => "#f5f5f5",
  "seashell"       => "#fff5ee",
  "beige"          => "#f5f5dc",
  "old lace"       => "#fdf5e6",
  "floral white"   => "#fffaf0",
  "ivory"          => "#fffff0",
  "antique white"  => "#faebd7",
  "linen"          => "#faf0e6",
  "lavender blush" => "#fff0f5",
  "misty rose"     => "#ffe4e1",
  
  "gainsboro"        => "#dcdcdc",
  "light grey"       => "#d3d3d3",
  "silver"           => "#c0c0c0",
  "dark gray"        => "#a9a9a9",
  "gray"             => "#808080",
  "dim gray"         => "#696969",
  "light slate gray" => "#778899",
  "slate gray"       => "#708090",
  "dark slate gray"  => "#2f4f4f",
  "black"            => "#000000"
);

/* vim:ts=2:sw=2:et:
 *  * */

/*
 * Scale is a singleton class that is instantiated to apply scale
 * transformations on the text -> canvas grid geometry. We could probably use
 * SVG's native scaling for this, but I'm not sure how yet.
 */
class Scale {
  private static $instance = null;

  public $xScale;
  public $yScale;

  private function __construct() {}
  private function __clone() {}

  public static function getInstance() {
    if (self::$instance == null) {
      self::$instance = new Scale();
    }

    return self::$instance;
  }

  public function setScale($x, $y) {
    $o = self::getInstance();
    $o->xScale = $x;
    $o->yScale = $y;
  }
}

/*
 * CustomObjects allows users to create their own custom SVG paths and use
 * them as box types with a2s:type references.
 *
 * Paths must have width and height set, and must not span multiple lines.
 * Multiple paths can be specified, one path per line. All objects must
 * reside in the same directory.
 *
 * File operations are horribly slow, so we make a best effort to avoid
 * as many as possible:
 *
 *  * If the directory mtime hasn't changed, we attempt to load our
 *    objects from a cache file.
 *
 *  * If this file doesn't exist, can't be read, or the mtime has
 *    changed, we scan the directory and update files that have changed
 *    based on their mtime.
 *
 *  * We attempt to save our cache in a temporary directory. It's volatile
 *    but also requires no configuration.
 *
 * We could do a bit better by utilizing APC's shared memory storage, which
 * would help greatly when running on a server.
 *
 * Note that the path parser isn't foolproof, mostly because PHP isn't the
 * greatest language ever for implementing a parser.
 */
class CustomObjects {
  public static $objects = array();

  /*
   * Closures / callable function names / whatever for integrating non-default
   * loading and storage functionality.
   */
  public static $loadCacheFn = null;
  public static $storCacheFn = null;
  public static $loadObjsFn = null;


  public static function loadObjects() {
    global $conf;
    $cacheFile = $conf['cachedir'] . '/plugin.asciitosvg.objcache';
    $dir = dirname(__FILE__) . '/objects';
    if (is_callable(self::$loadCacheFn)) {
      /*
       * Should return exactly what was given to the $storCacheFn when it was
       * last called, or null if nothing can be loaded.
       */
      $fn = self::$loadCacheFn;
      self::$objects = $fn();
      return;
    } else {
      if (is_readable($cacheFile) && is_readable($dir)) {
        $cacheTime = filemtime($cacheFile);

        if (filemtime($dir) <= filemtime($cacheFile)) {
          self::$objects = unserialize(file_get_contents($cacheFile));
          return;
        }
      } else if (file_exists($cacheFile)) {
        return;
      }
    }

    if (is_callable(self::$loadObjsFn)) {
      /*
       * Returns an array of arrays of path information. The innermost arrays
       * (containing the path information) contain the path name, the width of
       * the bounding box, the height of the bounding box, and the path
       * command. This interface does *not* want the path's XML tag. An array
       * returned from here containing two objects that each have 1 line would
       * look like:
       *
       * array (
       *   array (
       *     name => 'pathA',
       *     paths => array (
       *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 0 L 10 10'),
       *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 10 L 10 0'),
       *     ),
       *   ),
       *   array (
       *     name => 'pathB',
       *     paths => array (
       *       array ('width' => 10, 'height' => 10, 'path' => 'M 0 5 L 5 10'),
       *       array ('width' => 10, 'height' => 10, 'path' => 'M 5 10 L 10 5'),
       *     ),
       *   ),
       * );
       */
      $fn = self::$loadObjsFn;
      $objs = $fn();

      $i = 0;
      foreach ($objs as $obj) {
        foreach ($obj['paths'] as $path) {
          self::$objects[$obj['name']][$i]['width'] = $path['width'];
          self::$objects[$obj['name']][$i]['height'] = $path['height'];
          self::$objects[$obj['name']][$i++]['path'] =
            self::parsePath($path['path']);
        }
      }
    } else {
      $ents = scandir($dir);
      foreach ($ents as $ent) {
        $file = "{$dir}/{$ent}";
        $base = substr($ent, 0, -5);
        if (substr($ent, -5) == '.path' && is_readable($file)) {
          if (isset(self::$objects[$base]) &&
              filemtime($file) <= self::$cacheTime) {
            continue;
          }

          $lines = file($file);

          $i = 0;
          foreach ($lines as $line) {
            preg_match('/width="(\d+)/', $line, $m);
            $width = $m[1];
            preg_match('/height="(\d+)/', $line, $m);
            $height = $m[1];
            preg_match('/d="([^"]+)"/', $line, $m);
            $path = $m[1];

            self::$objects[$base][$i]['width'] = $width;
            self::$objects[$base][$i]['height'] = $height;
            self::$objects[$base][$i++]['path'] = self::parsePath($path);
          }
        }
      }
    }

    if (is_callable(self::$storCacheFn)) {
      $fn = self::$storCacheFn;
      $fn(self::$objects);
    } else {
      file_put_contents($cacheFile, serialize(self::$objects));
    }
  }

  private static function parsePath($path) {
    $stream = fopen("data://text/plain,{$path}", 'r');

    $P = new A2S_SVGPathParser();
    $S = new A2S_Yylex($stream);

    while ($t = $S->nextToken()) {
      $P->A2S_SVGPath($t->type, $t);
    }
    /* Force shift/reduce of last token. */
    $P->A2S_SVGPath(0);

    fclose($stream);

    $cmdArr = array();
    $i = 0;
    foreach ($P->commands as $cmd) {
      foreach ($cmd as $arg) {
        $arg = (array)$arg;
        $cmdArr[$i][] = $arg['value'];
      }
      $i++;
    }

    return $cmdArr;
  }
}

/*
 * All lines and polygons are represented as a series of point coordinates
 * along a path. Points can have different properties; markers appear on
 * edges of lines and control points denote that a bezier curve should be
 * calculated for the corner represented by this point.
 */
class Point {
  public $gridX;
  public $gridY;

  public $x;
  public $y;

  public $flags;

  const POINT    = 0x1;
  const CONTROL  = 0x2;
  const SMARKER  = 0x4;
  const IMARKER  = 0x8;
  const TICK     = 0x10;
  const DOT      = 0x20;

  public function __construct($x, $y) {
    $this->flags = 0;

    $s = Scale::getInstance();
    $this->x = ($x * $s->xScale) + ($s->xScale / 2);
    $this->y = ($y * $s->yScale) + ($s->yScale / 2);

    $this->gridX = $x;
    $this->gridY = $y;
  }
}

/*
 * Groups objects together and sets common properties for the objects in the
 * group.
 */
class SVGGroup {
  private $groups;
  private $curGroup;
  private $groupStack;
  private $options;

  public function __construct() {
    $this->groups = array();
    $this->groupStack = array();
    $this->options = array();
  }

  public function getGroup($groupName) {
    return $this->groups[$groupName];
  }

  public function pushGroup($groupName) {
    if (!isset($this->groups[$groupName])) {
      $this->groups[$groupName] = array();
      $this->options[$groupName] = array();
    }

    $this->groupStack[] = $groupName;
    $this->curGroup = $groupName;
  }

  public function popGroup() {
    /*
     * Remove the last group and fetch the current one. array_pop will return
     * NULL for an empty array, so this is safe to do when only one element
     * is left.
     */
    array_pop($this->groupStack);
    $this->curGroup = array_pop($this->groupStack);
  }

  public function addObject($o) {
    $this->groups[$this->curGroup][] = $o;
  }

  public function setOption($opt, $val) {
    $this->options[$this->curGroup][$opt] = $val;
  }

  public function render() {
    $out = '';

    foreach($this->groups as $groupName => $objects) {
      $out .= "<g id=\"{$groupName}\" ";
      foreach ($this->options[$groupName] as $opt => $val) {
        if (strpos($opt, 'a2s:', 0) === 0) {
          continue;
        }
        $out .= "$opt=\"$val\" ";
      }
      $out .= ">\n";

      foreach($objects as $obj) {
        $out .= $obj->render();
      }

      $out .= "</g>\n";
    }

    return $out;
  }
}

/*
 * The Path class represents lines and polygons.
 */
class SVGPath {
  private $options;
  private $points;
  private $ticks;
  private $flags;
  private $text;
  private $name;

  private static $id = 0;

  const CLOSED = 0x1;

  public function __construct() {
    $this->options = array();
    $this->points = array();
    $this->text = array();
    $this->ticks = array();
    $this->flags = 0;
    $this->name = self::$id++;
  }

  /*
   * Making sure that we always started at the top left coordinate 
   * makes so many things so much easier. First, find the lowest Y
   * position. Then, of all matching Y positions, find the lowest X
   * position. This is the top left.
   *
   * As far as the points are considered, they're definitely on the
   * top somewhere, but not necessarily the most left. This could
   * happen if there was a corner connector in the top edge (perhaps
   * for a line to connect to). Since we couldn't turn right there,
   * we have to try now.
   *
   * This should only be called when we close a polygon.
   */
  public function orderPoints() {
    $pPoints = count($this->points);

    $minY = $this->points[0]->y;
    $minX = $this->points[0]->x;
    $minIdx = 0;
    for ($i = 1; $i < $pPoints; $i++) {
      if ($this->points[$i]->y <= $minY) {
        $minY = $this->points[$i]->y;

        if ($this->points[$i]->x < $minX) {
          $minX = $this->points[$i]->x;
          $minIdx = $i;
        }
      }
    }

    /*
     * If our top left isn't at the 0th index, it is at the end. If
     * there are bits after it, we need to cut those and put them at
     * the front.
     */
    if ($minIdx != 0) {
      $startPoints = array_splice($this->points, $minIdx);
      $this->points = array_merge($startPoints, $this->points);
    }
  }

  /*
   * Useful for recursive walkers when speculatively trying a direction.
   */
  public function popPoint() {
    array_pop($this->points);
  }

  public function addPoint($x, $y, $flags = Point::POINT) {
    $p = new Point($x, $y);

    /*
     * If we attempt to add our original point back to the path, the polygon
     * must be closed.
     */
    if (count($this->points) > 0) {
      if ($this->points[0]->x == $p->x && $this->points[0]->y == $p->y) {
        $this->flags |= self::CLOSED;
        return true;
      }

      /*
      * For the purposes of this library, paths should never intersect each
      * other. Even in the case of closing the polygon, we do not store the
      * final coordinate twice.
      */
      foreach ($this->points as $point) {
        if ($point->x == $p->x && $point->y == $p->y) {
          return true;
        }
      }
    }

    $p->flags |= $flags;
    $this->points[] = $p;

    return false;
  }

  /*
   * It's useful to be able to know the points in a shape.
   */
  public function getPoints() {
    return $this->points;
  }

  /*
   * Add a marker to a line. The third argument specifies which marker to use,
   * and this depends on the orientation of the line. Due to the way the line
   * parser works, we may have to use an inverted representation.
   */
  public function addMarker($x, $y, $t) {
    $p = new Point($x, $y);
    $p->flags |= $t;
    $this->points[] = $p;
  }

  public function addTick($x, $y, $t) {
    $p = new Point($x, $y);
    $p->flags |= $t;
    $this->ticks[] = $p;
  }

  /*
   * Is this path closed?
   */
  public function isClosed() {
    return ($this->flags & self::CLOSED);
  }

  public function addText($t) {
    $this->text[] = $t;
  }

  public function getText() {
    return $this->text;
  }

  public function getID() {
    return $this->name;
  }

  /*
   * Set options as a JSON string. Specified as a merge operation so that it
   * can be called after an individual setOption call.
   */
  public function setOptions($opt) {
    $this->options = array_merge($this->options, $opt);
  }

  public function setOption($opt, $val) {
    $this->options[$opt] = $val;
  }

  public function getOption($opt) {
    if (isset($this->options[$opt])) {
      return $this->options[$opt];
    }

    return null;
  }

  /*
   * Does the given point exist within this polygon? Since we can
   * theoretically have some complex concave and convex polygon edges in the
   * same shape, we need to do a full point-in-polygon test. This algorithm
   * seems like the standard one. See: http://alienryderflex.com/polygon/
   */
  public function hasPoint($x, $y) {
    if ($this->isClosed() == false) {
      return false;
    }

    $oddNodes = false;

    $bound = count($this->points);
    for ($i = 0, $j = count($this->points) - 1; $i < $bound; $i++) {
      if (($this->points[$i]->gridY < $y && $this->points[$j]->gridY >= $y ||
           $this->points[$j]->gridY < $y && $this->points[$i]->gridY >= $y) &&
          ($this->points[$i]->gridX <= $x || $this->points[$j]->gridX <= $x)) {
        if ($this->points[$i]->gridX + ($y - $this->points[$i]->gridY) /
            ($this->points[$j]->gridY - $this->points[$i]->gridY) *
            ($this->points[$j]->gridX - $this->points[$i]->gridX) < $x) {
          $oddNodes = !$oddNodes;
        }
      }

      $j = $i;
    }

    return $oddNodes;
  }

  /* 
   * Apply a matrix transformation to the coordinates ($x, $y). The
   * multiplication is implemented on the matrices:
   *
   * | a b c |   | x |
   * | d e f | * | y |
   * | 0 0 1 |   | 1 |
   *
   * Additional information on the transformations and what each R,C in the
   * transformation matrix represents, see:
   *
   * http://www.w3.org/TR/SVG/coords.html#TransformMatrixDefined
   */
  private function matrixTransform($matrix, $x, $y) {
    $xyMat = array(array($x), array($y), array(1));
    $newXY = array(array());

    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j < 1; $j++) {
        $sum = 0;

        for ($k = 0; $k < 3; $k++) {
          $sum += $matrix[$i][$k] * $xyMat[$k][$j];
        }

        $newXY[$i][$j] = $sum;
      }
    }

    /* Return the coordinates as a vector */
    return array($newXY[0][0], $newXY[1][0], $newXY[2][0]);
  }

  /*
   * Translate the X and Y coordinates. tX and tY specify the distance to
   * transform.
   */
  private function translateTransform($tX, $tY, $x, $y) {
    $matrix = array(array(1, 0, $tX), array(0, 1, $tY), array(0, 0, 1));
    return $this->matrixTransform($matrix, $x, $y);
  }

  /*
   * Scale transformations are implemented by applying the scale to the X and
   * Y coordinates. One unit in the new coordinate system equals $s[XY] units
   * in the old system. Thus, if you want to double the size of an object on
   * both axes, you sould call scaleTransform(0.5, 0.5, $x, $y)
   */
  private function scaleTransform($sX, $sY, $x, $y) {
    $matrix = array(array($sX, 0, 0), array(0, $sY, 0), array(0, 0, 1));
    return $this->matrixTransform($matrix, $x, $y);
  }

  /*
   * Rotate the coordinates around the center point cX and cY. If these
   * are not specified, the coordinate is rotated around 0,0. The angle
   * is specified in degrees.
   */
  private function rotateTransform($angle, $x, $y, $cX = 0, $cY = 0) {
    $angle = $angle * (pi() / 180);
    if ($cX != 0 || $cY != 0) {
      list ($x, $y) = $this->translateTransform($cX, $cY, $x, $y);
    }

    $matrix = array(array(cos($angle), -sin($angle), 0),
                    array(sin($angle), cos($angle), 0),
                    array(0, 0, 1));
    $ret = $this->matrixTransform($matrix, $x, $y);

    if ($cX != 0 || $cY != 0) {
      list ($x, $y) = $this->translateTransform(-$cX, -$cY, $ret[0], $ret[1]);
      $ret[0] = $x;
      $ret[1] = $y;
    }

    return $ret;
  }

  /*
   * Skews along the X axis at specified angle. The angle is specified in
   * degrees.
   */
  private function skewXTransform($angle, $x, $y) {
    $angle = $angle * (pi() / 180);
    $matrix = array(array(1, tan($angle), 0), array(0, 1, 0), array(0, 0, 1));
    return $this->matrixTransform($matrix, $x, $y);
  }

  /*
   * Skews along the Y axis at specified angle. The angle is specified in
   * degrees.
   */
  private function skewYTransform($angle, $x, $y) {
    $angle = $angle * (pi() / 180);
    $matrix = array(array(1, 0, 0), array(tan($angle), 1, 0), array(0, 0, 1));
    return $this->matrixTransform($matrix, $x, $y);
  }

  /*
   * Apply a transformation to a point $p.
   */
  private function applyTransformToPoint($txf, $p, $args) {
    switch ($txf) {
    case 'translate':
      return $this->translateTransform($args[0], $args[1], $p->x, $p->y);

    case 'scale':
      return $this->scaleTransform($args[0], $args[1], $p->x, $p->y);

    case 'rotate':
      if (count($args) > 1) {
        return  $this->rotateTransform($args[0], $p->x, $p->y, $args[1], $args[2]);
      } else {
        return  $this->rotateTransform($args[0], $p->x, $p->y);
      }

    case 'skewX':
      return $this->skewXTransform($args[0], $p->x, $p->y);

    case 'skewY':
      return $this->skewYTransform($args[0], $p->x, $p->y);
    }
  }

  /*
   * Apply the transformation function $txf to all coordinates on path $p
   * providing $args as arguments to the transformation function.
   */
  private function applyTransformToPath($txf, &$p, $args) {
    $pathCmds = count($p['path']);
    $curPoint = new Point(0, 0);
    $prevType = null;
    $curType = null;

    for ($i = 0; $i < $pathCmds; $i++) {
      $cmd = &$p['path'][$i];

      $prevType = $curType;
      $curType = $cmd[0];

      switch ($curType) {
      case 'z':
      case 'Z':
        /* Can't transform this */
        break;

      case 'm':
        if ($prevType != null) {
          $curPoint->x += $cmd[1];
          $curPoint->y += $cmd[2];

          list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
          $curPoint->x = $x;
          $curPoint->y = $y;

          $cmd[1] = $x;
          $cmd[2] = $y;
        } else {
          $curPoint->x = $cmd[1];
          $curPoint->y = $cmd[2];

          list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
          $curPoint->x = $x;
          $curPoint->y = $y;

          $cmd[1] = $x;
          $cmd[2] = $y;
          $curType = 'l';
        }

        break;

      case 'M':
        $curPoint->x = $cmd[1];
        $curPoint->y = $cmd[2];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $x;
        $cmd[2] = $y;

        if ($prevType == null) {
          $curType = 'L';
        }
        break;

      case 'l':
        $curPoint->x += $cmd[1];
        $curPoint->y += $cmd[2];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $x;
        $cmd[2] = $y;

        break;

      case 'L':
        $curPoint->x = $cmd[1];
        $curPoint->y = $cmd[2];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $x;
        $cmd[2] = $y;

        break;

      case 'v':
        $curPoint->y += $cmd[1];
        $curPoint->x += 0;

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $y;

        break;

      case 'V':
        $curPoint->y = $cmd[1];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $y;

        break;

      case 'h':
        $curPoint->x += $cmd[1];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $x;

        break;

      case 'H':
        $curPoint->x = $cmd[1];

        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;

        $cmd[1] = $x;

        break;

      case 'c':
        $tP = new Point(0, 0);
        $tP->x = $curPoint->x + $cmd[1]; $tP->y = $curPoint->y + $cmd[2];
        list ($x, $y) = $this->applyTransformToPoint($txf, $tP, $args);
        $cmd[1] = $x;
        $cmd[2] = $y;

        $tP->x = $curPoint->x + $cmd[3]; $tP->y = $curPoint->y + $cmd[4];
        list ($x, $y) = $this->applyTransformToPoint($txf, $tP, $args);
        $cmd[3] = $x;
        $cmd[4] = $y;

        $curPoint->x += $cmd[5];
        $curPoint->y += $cmd[6];
        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);

        $curPoint->x = $x;
        $curPoint->y = $y;
        $cmd[5] = $x;
        $cmd[6] = $y;

        break;
      case 'C':
        $curPoint->x = $cmd[1];
        $curPoint->y = $cmd[2];
        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $cmd[1] = $x;
        $cmd[2] = $y;

        $curPoint->x = $cmd[3];
        $curPoint->y = $cmd[4];
        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $cmd[3] = $x;
        $cmd[4] = $y;

        $curPoint->x = $cmd[5];
        $curPoint->y = $cmd[6];
        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);

        $curPoint->x = $x;
        $curPoint->y = $y;
        $cmd[5] = $x;
        $cmd[6] = $y;

        break;

      case 's':
      case 'S':

      case 'q':
      case 'Q':
        
      case 't':
      case 'T':

      case 'a':
        break;

      case 'A':
        /*
         * This radius is relative to the start and end points, so it makes
         * sense to scale, rotate, or skew it, but not translate it.
         */
        if ($txf != 'translate') {
          $curPoint->x = $cmd[1];
          $curPoint->y = $cmd[2];
          list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
          $cmd[1] = $x;
          $cmd[2] = $y;
        }

        $curPoint->x = $cmd[6];
        $curPoint->y = $cmd[7];
        list ($x, $y) = $this->applyTransformToPoint($txf, $curPoint, $args);
        $curPoint->x = $x;
        $curPoint->y = $y;
        $cmd[6] = $x;
        $cmd[7] = $y;

        break;
      }
    }
  }

  public function render() {
    $startPoint = array_shift($this->points);
    $endPoint = $this->points[count($this->points) - 1];

    $out = "<g id=\"group{$this->name}\">\n";

    /*
     * If someone has specified one of our special object types, we are going
     * to want to completely override any of the pathing that we would have
     * done otherwise, but we defer until here to do anything about it because
     * we need information about the object we're replacing.
     */
    if (isset($this->options['a2s:type']) &&
        isset(CustomObjects::$objects[$this->options['a2s:type']])) {
      $object = CustomObjects::$objects[$this->options['a2s:type']];

      /* Again, if no fill was specified, specify one. */
      if (!isset($this->options['fill'])) {
        $this->options['fill'] = '#fff';
      }

      /*
       * We don't care so much about the area, but we do care about the width
       * and height of the object. All of our "custom" objects are implemented
       * in 100x100 space, which makes the transformation marginally easier.
       */
      $minX = $startPoint->x; $maxX = $minX;
      $minY = $startPoint->y; $maxY = $minY;
      foreach ($this->points as $p) {
        if ($p->x < $minX) {
          $minX = $p->x;
        } elseif ($p->x > $maxX) {
          $maxX = $p->x;
        }
        if ($p->y < $minY) {
          $minY = $p->y;
        } elseif ($p->y > $maxY) {
          $maxY = $p->y;
        }
      }

      $objW = $maxX - $minX;
      $objH = $maxY - $minY;

      $i = 0;
      foreach ($object as $o) {
        $id = self::$id++;
        $out .= "\t<path id=\"path{$this->name}\" d=\"";

        $oW = $o['width'];
        $oH = $o['height'];

        $this->applyTransformToPath('scale', $o, array($objW/$oW, $objH/$oH));
        $this->applyTransformToPath('translate', $o, array($minX, $minY));

        foreach ($o['path'] as $cmd) {
          $out .= join(' ', $cmd) . ' ';
        }
        $out .= '" ';

        /* Don't add options to sub-paths */
        if ($i++ < 1) {
          foreach ($this->options as $opt => $val) {
            if (strpos($opt, 'a2s:', 0) === 0) {
              continue;
            }
            $out .= "$opt=\"$val\" ";
          }
        }

        $out .= " />\n";
      }

      if (count($this->text) > 0) {
        foreach ($this->text as $text) {
          $out .= "\t" . $text->render() . "\n";
        }
      }
      $out .= "</g>\n";

      /* Bazinga. */
      return $out;
    }

    /*
     * Nothing fancy here -- this is just rendering for our standard
     * polygons.
     *
     * Our start point is represented by a single moveto command (unless the
     * start point is curved) as the shape will be closed with the Z command
     * automatically if it is a closed shape. If we have a control point, we
     * have to go ahead and draw the curve.
     */
    if (($startPoint->flags & Point::CONTROL)) {
      $cX = $startPoint->x;
      $cY = $startPoint->y;
      $sX = $startPoint->x;
      $sY = $startPoint->y + 10;
      $eX = $startPoint->x + 10;
      $eY = $startPoint->y;

      $path = "M {$sX} {$sY} Q {$cX} {$cY} {$eX} {$eY} ";
    } else {
      $path = "M {$startPoint->x} {$startPoint->y} ";
    }

    $prevP = $startPoint;
    $bound = count($this->points);
    for ($i = 0; $i < $bound; $i++) {
      $p = $this->points[$i];

      /*
       * Handle quadratic Bezier curves. NOTE: This algorithm for drawing
       * the curves only works if the shapes are drawn in a clockwise
       * manner.
       */
      if (($p->flags & Point::CONTROL)) {
        /* Our control point is always the original corner */
        $cX = $p->x;
        $cY = $p->y;

        /* Need next point to determine which way to turn */
        if ($i == count($this->points) - 1) {
          $nP = $startPoint;
        } else {
          $nP = $this->points[$i + 1];
        }

        if ($prevP->x == $p->x) {
          /*
           * If we are on the same vertical axis, our starting X coordinate
           * is the same as the control point coordinate.
           */
          $sX = $p->x;
        
          /* Offset start point from control point in the proper direction */
          if ($prevP->y < $p->y) {
            $sY = $p->y - 10;
          } else {
            $sY = $p->y + 10;
          }

          $eY = $p->y;
          /* Offset end point from control point in the proper direction */
          if ($nP->x < $p->x) {
            $eX = $p->x - 10;
          } else {
            $eX = $p->x + 10;
          }
        } elseif ($prevP->y == $p->y) {
          /* Horizontal decisions mirror vertical's above */
          $sY = $p->y;
          if ($prevP->x < $p->x) {
            $sX = $p->x - 10;
          } else {
            $sX = $p->x + 10;
          }

          $eX = $p->x;
          if ($nP->y <= $p->y) {
            $eY = $p->y - 10;
          } else {
            $eY = $p->y + 10;
          }
        }

        $path .= "L {$sX} {$sY} Q {$cX} {$cY} {$eX} {$eY} ";
      } else {
        /* The excruciating difficulty of drawing a straight line */
        $path .= "L {$p->x} {$p->y} ";
      }

      $prevP = $p;
    }

    if ($this->isClosed()) {
      $path .= 'Z';
    } 

    $id = self::$id++;

    /* Add markers if necessary. */
    if ($startPoint->flags & Point::SMARKER) {
      $this->options["marker-start"] = "url(#Pointer)";
    } elseif ($startPoint->flags & Point::IMARKER) {
      $this->options["marker-start"] = "url(#iPointer)";
    }

    if ($endPoint->flags & Point::SMARKER) {
      $this->options["marker-end"] = "url(#Pointer)";
    } elseif ($endPoint->flags & Point::IMARKER) {
      $this->options["marker-end"] = "url(#iPointer)";
    }

    /*
     * SVG objects without a fill will be transparent, and this looks so
     * terrible with the drop-shadow effect. Any objects that aren't filled
     * automatically get a white fill.
     */
    if ($this->isClosed() && !isset($this->options['fill'])) {
      $this->options['fill'] = '#fff';
    }


    $out .= "\t<path id=\"path{$this->name}\" ";
    foreach ($this->options as $opt => $val) {
      if (strpos($opt, 'a2s:', 0) === 0) {
        continue;
      }
      $out .= "$opt=\"$val\" ";
    }
    $out .= "d=\"{$path}\" />\n";
    
    if (count($this->text) > 0) {
      foreach ($this->text as $text) {
        $out .= "\t" . $text->render() . "\n";
      }
    }

    $bound = count($this->ticks);
    for ($i = 0; $i < $bound; $i++) {
      $t = $this->ticks[$i];
      if ($t->flags & Point::DOT) {
        $out .= "<circle cx=\"{$t->x}\" cy=\"{$t->y}\" r=\"3\" fill=\"black\" />";
      } elseif ($t->flags & Point::TICK) {
        $x1 = $t->x - 4;
        $y1 = $t->y - 4;
        $x2 = $t->x + 4;
        $y2 = $t->y + 4;
        $out .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke-width=\"1\" />";

        $x1 = $t->x + 4;
        $y1 = $t->y - 4;
        $x2 = $t->x - 4;
        $y2 = $t->y + 4;
        $out .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke-width=\"1\" />";
      }
    }

    $out .= "</g>\n";
    return $out;
  }
}

/*
 * Nothing really special here. Container for representing text bits.
 */
class SVGText {
  private $options;
  private $string;
  private $point;
  private $name;

  private static $id = 0;

  private static function svgEntities($str) {
    /* <, >, and & replacements are valid without a custom DTD:
     * https://www.w3.org/TR/xml/#syntax
     *
     * We want to replace these in text without confusing SVG.
     */
    $s = array('<', '>', '&');
    $r = array('&lt;', '&gt;', '&amp;');
    return str_replace($s, $r, $str);
  }

  public function __construct($x, $y) {
    $this->point = new Point($x, $y);
    $this->name = self::$id++;
    $this->options = array();
  }

  public function setOption($opt, $val) {
    $this->options[$opt] = $val;
  }

  public function getID() {
    return $this->name;
  }

  public function getPoint() {
    return $this->point;
  }

  public function setString($string) {
    $this->string = $string;
  }

  public function render() {
    $out = "<text x=\"{$this->point->x}\" y=\"{$this->point->y}\" id=\"text{$this->name}\" ";
    foreach ($this->options as $opt => $val) {
      if (strpos($opt, 'a2s:', 0) === 0) {
        continue;
      }
      $out .= "$opt=\"$val\" ";
    }
    $out .= ">";
    $out .= SVGText::svgEntities($this->string);
    $out .= "</text>\n";
    return $out;
  }
}

/*
 * Main class for parsing ASCII and constructing the SVG output based on the
 * above classes.
 */
class ASCIIToSVG {
  public $blurDropShadow = true;
  public $fontFamily = "Consolas,Monaco,Anonymous Pro,Anonymous,Bitstream Sans Mono,monospace";

  private $rawData;
  private $grid;

  private $svgObjects;
  private $clearCorners;

  /* Directions for traversing lines in our grid */
  const DIR_UP    = 0x1;
  const DIR_DOWN  = 0x2;
  const DIR_LEFT  = 0x4;
  const DIR_RIGHT = 0x8;
  const DIR_NE    = 0x10;
  const DIR_SE    = 0x20;

  public function __construct($data) {
    /* For debugging purposes */
    $this->rawData = $data;

    CustomObjects::loadObjects();

    $this->clearCorners = array();

    /*
     * Parse out any command references. These need to be at the bottom of the
     * diagram due to the way they're removed. Format is:
     * [identifier] optional-colon optional-spaces ({json-blob})\n
     *
     * The JSON blob may not contain objects as values or the regex will break.
     */
    $this->commands = array();
    preg_match_all('/^\[([^\]]+)\]:?\s+({[^}]+?})/ims', $data, $matches);
    $bound = count($matches[1]);
    for ($i = 0; $i < $bound; $i++) {
      $this->commands[$matches[1][$i]] = json_decode($matches[2][$i], true);
    }

    $data = preg_replace('/^\[([^\]]+)\](:?)\s+.*/ims', '', $data);

    /*
     * Treat our UTF-8 field as a grid and store each character as a point in
     * that grid. The (0, 0) coordinate on this grid is top-left, just as it
     * is in images.
     */
    $this->grid = explode("\n", $data);

    foreach ($this->grid as $k => $line) {
      $this->grid[$k] = preg_split('//u', $line, null, PREG_SPLIT_NO_EMPTY);
    }

    $this->svgObjects = new SVGGroup();
  }

  /*
   * This is kind of a stupid and hacky way to do this, but this allows setting
   * the default scale of one grid space on the X and Y axes.
   */
  public function setDimensionScale($x, $y) {
    $o = Scale::getInstance();
    $o->setScale($x, $y);
  }

  public function dump() {
    var_export($this);
  }

  /* Render out what we've done!  */
  public function render() {
    $o = Scale::getInstance();

    /* Figure out how wide we need to make the canvas */
    $canvasWidth = 0;
    foreach($this->grid as $line) {
      if (count($line) > $canvasWidth) {
        $canvasWidth = count($line);
      }
    }

    $canvasWidth = $canvasWidth * $o->xScale + 10;
    $canvasHeight = count($this->grid) * $o->yScale;

    /*
     * Boilerplate header with definitions that we might be using for markers
     * and drop shadows.
     */
/*
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<!-- Created with ASCIIToSVG (https://github.com/dhobsd/asciitosvg/) -->
*/
    $out = <<<SVG
width="{$canvasWidth}px" height="{$canvasHeight}px" version="1.1"
  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <!-- Created with ASCIIToSVG (https://github.com/dhobsd/asciitosvg/) -->
  <defs>
    <filter id="dsFilterNoBlur" width="150%" height="150%">
      <feOffset result="offOut" in="SourceGraphic" dx="3" dy="3"/>
      <feColorMatrix result="matrixOut" in="offOut" type="matrix" values="0.2 0 0 0 0 0 0.2 0 0 0 0 0 0.2 0 0 0 0 0 1 0"/>
      <feBlend in="SourceGraphic" in2="matrixOut" mode="normal"/>
    </filter>
    <filter id="dsFilter" width="150%" height="150%">
      <feOffset result="offOut" in="SourceGraphic" dx="3" dy="3"/>
      <feColorMatrix result="matrixOut" in="offOut" type="matrix" values="0.2 0 0 0 0 0 0.2 0 0 0 0 0 0.2 0 0 0 0 0 1 0"/>
      <feGaussianBlur result="blurOut" in="matrixOut" stdDeviation="3"/>
      <feBlend in="SourceGraphic" in2="blurOut" mode="normal"/>
    </filter>
    <marker id="iPointer"
      viewBox="0 0 10 10" refX="5" refY="5" 
      markerUnits="strokeWidth"
      markerWidth="8" markerHeight="7"
      fill="black"
      orient="auto">
      <path d="M 10 0 L 10 10 L 0 5 z" />
    </marker>
    <marker id="Pointer"
      viewBox="0 0 10 10" refX="5" refY="5" 
      markerUnits="strokeWidth"
      markerWidth="8" markerHeight="7"
      fill="black"
      orient="auto">
      <path d="M 0 0 L 10 5 L 0 10 z" />
    </marker>
  </defs>
SVG;

    /* Render the group, everything lives in there */
    $out .= $this->svgObjects->render();

    $out .= "</svg>\n";

    return $out;
  }

  /*
   * Parsing the grid is a multi-step process. We parse out boxes first, as
   * this makes it easier to then parse lines. By parse out, I do mean we
   * parse them and then remove them. This does mean that a complete line
   * will not travel along the edge of a box, but you probably won't notice
   * unless the box is curved anyway. While edges are removed, points are
   * not. This means that you can cleanly allow lines to intersect boxes
   * (as long as they do not bisect!
   *
   * After parsing boxes and lines, we remove the corners from the grid. At
   * this point, all we have left should be text, which we can pick up and
   * place.
   */
  public function parseGrid() {
    $this->parseBoxes();
    $this->parseLines();

    foreach ($this->clearCorners as $corner) {
      $this->grid[$corner[0]][$corner[1]] = ' ';
    }

    $this->parseText();

    $this->injectCommands();
  }

  /*
   * Ahh, good ol' box parsing. We do this by scanning each row for points and
   * attempting to close the shape. Since the approach is first horizontal,
   * then vertical, we complete the shape in a clockwise order (which is
   * important for the Bezier curve generation.
   */
  private function parseBoxes() {
    /* Set up our box group  */
    $this->svgObjects->pushGroup('boxes');
    $this->svgObjects->setOption('stroke', 'black');
    $this->svgObjects->setOption('stroke-width', '2');
    $this->svgObjects->setOption('fill', 'none');

    /* Scan the grid for corners */
    foreach ($this->grid as $row => $line) {
      foreach ($line as $col => $char) {
        if ($this->isCorner($char)) {
          $path = new SVGPath();

          if ($char == '.' || $char == "'") {
            $path->addPoint($col, $row, Point::CONTROL);
          } else {
            $path->addPoint($col, $row);
          }

          /*
           * The wall follower is a left-turning, marking follower. See that
           * function for more information on how it works.
           */
          $this->wallFollow($path, $row, $col+1, self::DIR_RIGHT);
        
          /* We only care about closed polygons */
          if ($path->isClosed()) {
            $path->orderPoints();

            $skip = false;
            /*
             * The walking code can find the same box from a different edge:
             *
             * +---+   +---+
             * |   |   |   |
             * |   +---+   |
             * +-----------+
             *
             * so ignore adding a box that we've already added.
             */
            foreach($this->svgObjects->getGroup('boxes') as $box) {
              $bP = $box->getPoints();
              $pP = $path->getPoints();
              $pPoints = count($pP);
              $shared = 0;

              /*
               * If the boxes don't have the same number of edges, they 
               * obviously cannot be the same box.
               */
              if (count($bP) != $pPoints) {
                continue;
              }

              /* Traverse the vertices of this new box... */
              for ($i = 0; $i < $pPoints; $i++) {
                /* ...and find them in this existing box. */
                for ($j = 0; $j < $pPoints; $j++) {
                  if ($pP[$i]->x == $bP[$j]->x && $pP[$i]->y == $bP[$j]->y) {
                    $shared++;
                  }
                }
              }

              /* If all the edges are in common, it's the same shape. */
              if ($shared == count($bP)) {
                $skip = true;
                break;
              }
            }

            if ($skip == false) {
              /* Search for any references for styling this polygon; add it */
              if ($this->blurDropShadow) {
                $path->setOption('filter', 'url(#dsFilter)');
              } else {
                $path->setOption('filter', 'url(#dsFilterNoBlur)');
              }

              $name = $this->findCommands($path);

              $this->svgObjects->addObject($path);
            }
          }
        }
      }
    }

    /*
     * Once we've found all the boxes, we want to remove them from the grid so
     * that they don't confuse the line parser. However, we don't remove any
     * corner characters because these might be shared by lines.
     */
    foreach ($this->svgObjects->getGroup('boxes') as $box) {
      $this->clearObject($box);
    }

    /* Anything after this is not a subgroup */
    $this->svgObjects->popGroup();
  }

  /*
   * Our line parser operates differently than the polygon parser. This is 
   * because lines are not intrinsically marked with starting points (markers
   * are optional) -- they just sort of begin. Additionally, so that markers
   * will work, we can't just construct a line from some random point: we need
   * to start at the correct edge.
   *
   * Thus, the line parser traverses vertically first, then horizontally. Once
   * a line is found, it is cleared immediately (but leaving any control points
   * in case there were any intersections.
   */
  private function parseLines() {
    /* Set standard line options */
    $this->svgObjects->pushGroup('lines');
    $this->svgObjects->setOption('stroke', 'black');
    $this->svgObjects->setOption('stroke-width', '2');
    $this->svgObjects->setOption('fill', 'none');

    /* The grid is not uniform, so we need to determine the longest row. */
    $maxCols = 0;
    $bound = count($this->grid);
    for ($r = 0; $r < $bound; $r++) {
      $maxCols = max($maxCols, count($this->grid[$r]));
    }

    for ($c = 0; $c < $maxCols; $c++) {
      for ($r = 0; $r < $bound; $r++) {
        /* This gets set if we find a line-start here. */
        $dir = false;

        $line = new SVGPath();

        /*
         * Since the column count isn't uniform, don't attempt to handle any
         * rows that don't extend out this far.
         */
        if (!isset($this->grid[$r][$c])) {
          continue;
        }

        $char = $this->getChar($r, $c);
        switch ($char) {
        /*
         * Do marker characters first. These are the easiest because they are
         * basically guaranteed to represent the start of the line.
         */
        case '<':
          $e = $this->getChar($r, $c + 1);
          if ($this->isEdge($e, self::DIR_RIGHT) || $this->isCorner($e)) {
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_RIGHT;
          } else {
            $se = $this->getChar($r + 1, $c + 1);
            $ne = $this->getChar($r - 1, $c + 1);
            if ($se == "\\") {
              $line->addMarker($c, $r, Point::IMARKER);
              $dir = self::DIR_SE;
            } elseif ($ne == '/') {
              $line->addMarker($c, $r, Point::IMARKER);
              $dir = self::DIR_NE;
            }
          }
          break;
        case '^':
          $s = $this->getChar($r + 1, $c);
          if ($this->isEdge($s, self::DIR_DOWN) || $this->isCorner($s)) { 
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_DOWN;
          } elseif ($this->getChar($r + 1, $c + 1) == "\\") {
            /* Don't need to check west for diagonals. */
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_SE;
          }
          break;
        case '>':
          $w = $this->getChar($r, $c - 1);
          if ($this->isEdge($w, self::DIR_LEFT) || $this->isCorner($w)) {
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_LEFT;
          }
          /* All diagonals come from west, so we don't need to check */
          break;
        case 'v':
          $n = $this->getChar($r - 1, $c);
          if ($this->isEdge($n, self::DIR_UP) || $this->isCorner($n)) {
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_UP;
          } elseif ($this->getChar($r - 1, $c + 1) == '/') {
            $line->addMarker($c, $r, Point::IMARKER);
            $dir = self::DIR_NE;
          }
          break;

        /*
         * Edges are handled specially. We have to look at the context of the
         * edge to determine whether it's the start of a line. A vertical edge
         * can appear as the start of a line in the following circumstances:
         *
         * +-------------      +--------------     +----    | (s)
         * |                   |                   |        |
         * |      | (s)        +-------+           |(s)     |
         * +------+                    | (s)
         *
         * From this we can extrapolate that we are a starting edge if our
         * southern neighbor is a vertical edge or corner, but we have no line
         * material to our north (and vice versa). This logic does allow for
         * the southern / northern neighbor to be part of a separate
         * horizontal line.
         */
        case ':':
          $line->setOption('stroke-dasharray', '5 5');
          /* FALLTHROUGH */
        case '|':
          $n = $this->getChar($r-1, $c);
          $s = $this->getChar($r+1, $c);
          if (($s == '|' || $s == ':' || $this->isCorner($s)) &&
              $n != '|' && $n != ':' && !$this->isCorner($n) &&
              $n != '^') {
            $dir = self::DIR_DOWN;
          } elseif (($n == '|' || $n == ':' || $this->isCorner($n)) &&
                    $s != '|' && $s != ':' && !$this->isCorner($s) &&
                    $s != 'v') {
            $dir = self::DIR_UP;
          }
          break;

        /*
         * Horizontal edges have the same properties for search as vertical
         * edges, except we need to look east / west. The diagrams for the
         * vertical case are still accurate to visualize this case; just
         * mentally turn them 90 degrees clockwise.
         */
        case '=':
          $line->setOption('stroke-dasharray', '5 5');
          /* FALLTHROUGH */
        case '-':
          $w = $this->getChar($r, $c-1);
          $e = $this->getChar($r, $c+1);
          if (($w == '-' || $w == '=' || $this->isCorner($w)) &&
              $e != '=' && $e != '-' && !$this->isCorner($e) &&
              $e != '>') {
            $dir = self::DIR_LEFT;
          } elseif (($e == '-' || $e == '=' || $this->isCorner($e)) &&
                    $w != '=' && $w != '-' && !$this->isCorner($w) &&
                    $w != '<') {
            $dir = self::DIR_RIGHT;
          }
          break;

        /*
         * We can only find diagonals going north or south and east. This is
         * simplified due to the fact that they have no corners. We are
         * guaranteed to run into their westernmost point or their relevant
         * marker.
         */
        case '/':
          $ne = $this->getChar($r-1, $c+1);
          if ($ne == '/' || $ne == '^' || $ne == '>') {
            $dir = self::DIR_NE;
          }
          break;

        case "\\":
          $se =  $this->getChar($r+1, $c+1);
          if ($se == "\\" || $se == "v" || $se == '>') {
            $dir = self::DIR_SE;
          }
          break;

        /*
         * The corner case must consider all four directions. Though a
         * reasonable person wouldn't use slant corners for this, they are
         * considered corners, so it kind of makes sense to handle them the
         * same way. For this case, envision the starting point being a corner
         * character in both the horizontal and vertical case. And then
         * mentally overlay them and consider that :).
         */
        case '+':
        case '#':
          $ne = $this->getChar($r-1, $c+1);
          $se =  $this->getChar($r+1, $c+1);
          if ($ne == '/' || $ne == '^' || $ne == '>') {
            $dir = self::DIR_NE;
          } elseif ($se == "\\" || $se == "v" || $se == '>') {
            $dir = self::DIR_SE;
          }
          /* FALLTHROUGH */

        case '.':
        case "'":
          $n = $this->getChar($r-1, $c);
          $w = $this->getChar($r, $c-1);
          $s = $this->getChar($r+1, $c);
          $e = $this->getChar($r, $c+1);
          if (($w == '=' || $w == '-') && $n != '|' && $n != ':' && $w != '-' &&
              $e != '=' && $e != '|' && $s != ':') {
            $dir = self::DIR_LEFT;
          } elseif (($e == '=' || $e == '-') && $n != '|' && $n != ':' && 
              $w != '-' && $w != '=' && $s != '|' && $s != ':') {
            $dir = self::DIR_RIGHT;
          } elseif (($s == '|' || $s == ':') && $n != '|' && $n != ':' &&
                    $w != '-' && $w != '=' && $e != '-' && $e != '=' &&
                    (($char != '.' && $char != "'") || 
                     ($char == '.' && $s != '.') || 
                     ($char == "'" && $s != "'"))) {
            $dir = self::DIR_DOWN;
          } elseif (($n == '|' || $n == ':') && $s != '|' && $s != ':' &&
                    $w != '-' && $w != '=' && $e != '-' && $e != '=' &&
                    (($char != '.' && $char != "'") || 
                     ($char == '.' && $s != '.') || 
                     ($char == "'" && $s != "'"))) {
            $dir = self::DIR_UP;
          }
          break;
        }

        /* It does actually save lines! */
        if ($dir !== false) {
          $rInc = 0; $cInc = 0;
          if (!$this->isMarker($char)) {
            $line->addPoint($c, $r);
          }

          /*
           * The walk routine may attempt to add the point again, so skip it.
           * If we don't, we can miss the line or end up with just a point.
           */
          if ($dir == self::DIR_UP) {
            $rInc = -1; $cInc = 0;
          } elseif ($dir == self::DIR_DOWN) {
            $rInc = 1; $cInc = 0;
          } elseif ($dir == self::DIR_RIGHT) {
            $rInc = 0; $cInc = 1;
          } elseif ($dir == self::DIR_LEFT) {
            $rInc = 0; $cInc = -1;
          } elseif ($dir == self::DIR_NE) {
            $rInc = -1; $cInc = 1;
          } elseif ($dir == self::DIR_SE) {
            $rInc = 1; $cInc = 1;
          }

          /*
           * Walk the points of this line. Note we don't use wallFollow; we are
           * operating under the assumption that lines do not meander. (And, in
           * any event, that algorithm is intended to find a closed object.)
           */
          $this->walk($line, $r+$rInc, $c+$cInc, $dir);

          /*
           * Remove it so that we don't confuse any other lines. This leaves
           * corners in tact, still.
           */
          $this->clearObject($line);
          $this->svgObjects->addObject($line);

          /* We may be able to find more lines starting from this same point */
          if ($this->isCorner($char)) {
            $r--;
          }
        }
      }
    }

    $this->svgObjects->popGroup();
  }

  /*
   * Look for text in a file. If the text appears in a box that has a dark
   * fill, we want to give it a light fill (and vice versa). This means we
   * have to figure out what box it lives in (if any) and do all sorts of
   * color calculation magic.
   */
  private function parseText() {
    $o = Scale::getInstance();

    /*
     * The style options deserve some comments. The monospace and font-size
     * choices are not accidental. This gives the best sort of estimation
     * for font size to scale that I could come up with empirically.
     *
     * N.B. This might change with different scales. I kind of feel like this
     * is a bug waiting to be filed, but whatever.
     */
    $fSize = 0.95*$o->yScale;
    $this->svgObjects->pushGroup('text');
    $this->svgObjects->setOption('fill', 'black');
    $this->svgObjects->setOption('style',
        "font-family:{$this->fontFamily};font-size:{$fSize}px");

    /*
     * Text gets the same scanning treatment as boxes. We do left-to-right
     * scanning, which should probably be configurable in case someone wants
     * to use this with e.g. Arabic or some other right-to-left language.
     * Either way, this isn't UTF-8 safe (thanks, PHP!!!), so that'll require
     * thought regardless.
     */
    $boxes = $this->svgObjects->getGroup('boxes');
    $bound = count($boxes);

    foreach ($this->grid as $row => $line) {
      $cols = count($line);
      for ($i = 0; $i < $cols; $i++) {
        if ($this->getChar($row, $i) != ' ') {
          /* More magic numbers that probably need research. */
          $t = new SVGText($i - .6, $row + 0.3);

          /* Time to figure out which (if any) box we live inside */
          $tP = $t->getPoint();

          $maxPoint = new Point(-1, -1);
          $boxQueue = array();

          for ($j = 0; $j < $bound; $j++) {
            if ($boxes[$j]->hasPoint($tP->gridX, $tP->gridY)) {
              $boxPoints = $boxes[$j]->getPoints();
              $boxTL = $boxPoints[0];

              /*
               * This text is in this box, but it may still be in a more
               * specific nested box. Find the box with the highest top
               * left X,Y coordinate. Keep a queue of boxes in case the top
               * most box doesn't have a fill.
               */
              if ($boxTL->y > $maxPoint->y && $boxTL->x > $maxPoint->x) {
                $maxPoint->x = $boxTL->x;
                $maxPoint->y = $boxTL->y;
                $boxQueue[] = $boxes[$j];
              }
            }
          }

          if (count($boxQueue) > 0) {
            /*
             * Work backwards through the boxes to find the box with the most
             * specific fill.
             */
            for ($j = count($boxQueue) - 1; $j >= 0; $j--) {
              $fill = $boxQueue[$j]->getOption('fill');

              if ($fill == 'none' || $fill == null) {
                continue;
              }

              if (substr($fill, 0, 1) != '#') {
                if (!isset($GLOBALS['A2S_colors'][strtolower($fill)])) {
                  continue;
                } else {
                  $fill = $GLOBALS['A2S_colors'][strtolower($fill)];
                }
              } else {
                if (strlen($fill) != 4 && strlen($fill) != 7) {
                  continue;
                }
              }
                

              if ($fill) {
                /* Attempt to parse the fill color */
                if (strlen($fill) == 4) {
                  $cR = hexdec(str_repeat($fill[1], 2));
                  $cG = hexdec(str_repeat($fill[2], 2));
                  $cB = hexdec(str_repeat($fill[3], 2));
                } elseif (strlen($fill) == 7) {
                  $cR = hexdec(substr($fill, 1, 2));
                  $cG = hexdec(substr($fill, 3, 2));
                  $cB = hexdec(substr($fill, 5, 2));
                }

                /*
                 * This magic is gleaned from the working group paper on
                 * accessibility at http://www.w3.org/TR/AERT. The recommended
                 * contrast is a brightness difference of at least 125 and a
                 * color difference of at least 500. Since our default color
                 * is black, that makes the color difference easier.
                 */
                $bFill = (($cR * 299) + ($cG * 587) + ($cB * 114)) / 1000;
                $bDiff = $cR + $cG + $cB;
                $bText = 0;

                if ($bFill - $bText < 125 || $bDiff < 500) {
                  /* If black is too dark, white will work */
                  $t->setOption('fill', '#fff');
                } else {
                  $t->setOption('fill', '#000');
                }

                break;
              }
            }

            if ($j < 0) {
              $t->setOption('fill', '#000');
            }
          } else {
            /* This text isn't inside a box; make it black */
            $t->setOption('fill', '#000');
          }

          /* We found a stringy character, eat it and the rest. */
          $str = $this->getChar($row, $i++);
          while ($i < count($line) && $this->getChar($row, $i) != ' ') {
            $str .= $this->getChar($row, $i++);
            /* Eat up to 1 space */
            if ($this->getChar($row, $i) == ' ') {
              $str .= ' ';
              $i++;
            }
          }

          if ($str == '') {
            continue;
          }

          $t->setString($str);

          /*
           * If we were in a box, group with the box. Otherwise it gets its
           * own group.
           */
          if (count($boxQueue) > 0) {
            $t->setOption('stroke', 'none');
            $t->setOption('style',
              "font-family:{$this->fontFamily};font-size:{$fSize}px");
            $boxQueue[count($boxQueue) - 1]->addText($t);
          } else {
            $this->svgObjects->addObject($t);
          }
        }
      }
    }
  }

  /*
   * Allow specifying references that target an object starting at grid point
   * (ROW,COL). This allows styling of lines, boxes, or any text object.
   */
  private function injectCommands() {
    $boxes = $this->svgObjects->getGroup('boxes');
    $lines = $this->svgObjects->getGroup('lines');
    $text = $this->svgObjects->getGroup('text');

    foreach ($boxes as $obj) {
      $objPoints = $obj->getPoints();
      $pointCmd = "{$objPoints[0]->gridY},{$objPoints[0]->gridX}";

      if (isset($this->commands[$pointCmd])) {
        $obj->setOptions($this->commands[$pointCmd]);
      }

      foreach ($obj->getText() as $text) {
        $textPoint = $text->getPoint();
        $pointCmd = "{$textPoint->gridY},{$textPoint->gridX}";

        if (isset($this->commands[$pointCmd])) {
          $text->setOptions($this->commands[$pointCmd]);
        }
      }
    }

    foreach ($lines as $obj) {
      $objPoints = $obj->getPoints();
      $pointCmd = "{$objPoints[0]->gridY},{$objPoints[0]->gridX}";

      if (isset($this->commands[$pointCmd])) {
        $obj->setOptions($this->commands[$pointCmd]);
      }
    }

    foreach ($text as $obj) {
      $objPoint = $obj->getPoint();
      $pointCmd = "{$objPoint->gridY},{$objPoint->gridX}";

      if (isset($this->commands[$pointCmd])) {
        $obj->setOptions($this->commands[$pointCmd]);
      }
    }
  }

  /*
   * A generic, recursive line walker. This walker makes the assumption that
   * lines want to go in the direction that they are already heading. I'm
   * sure that there are ways to formulate lines to screw this walker up,
   * but it does a good enough job right now.
   */
  private function walk($path, $row, $col, $dir, $d = 0) {
    $d++;
    $r = $row;
    $c = $col;

    if ($dir == self::DIR_RIGHT || $dir == self::DIR_LEFT) {
      $cInc = ($dir == self::DIR_RIGHT) ? 1 : -1;
      $rInc = 0;
    } elseif ($dir == self::DIR_DOWN || $dir == self::DIR_UP) {
      $cInc = 0;
      $rInc = ($dir == self::DIR_DOWN) ? 1 : -1;
    } elseif ($dir == self::DIR_SE || $dir == self::DIR_NE) {
      $cInc = 1;
      $rInc = ($dir == self::DIR_SE) ? 1 : -1;
    }

    /* Follow the edge for as long as we can */
    $cur = $this->getChar($r, $c);
    while ($this->isEdge($cur, $dir)) {
      if ($cur == ':' || $cur == '=') {
        $path->setOption('stroke-dasharray', '5 5');
      }

      if ($this->isTick($cur)) {
        $path->addTick($c, $r, ($cur == 'o') ? Point::DOT : Point::TICK);
        $path->addPoint($c, $r);
      }

      $c += $cInc;
      $r += $rInc;
      $cur = $this->getChar($r, $c);
    }

    if ($this->isCorner($cur)) {
      if ($cur == '.' || $cur == "'") {
        $path->addPoint($c, $r, Point::CONTROL);
      } else {
        $path->addPoint($c, $r);
      }

      if ($path->isClosed()) {
        $path->popPoint();
        return;
      }

      /*
       * Attempt first to continue in the current direction. If we can't,
       * try to go in any direction other than the one opposite of where
       * we just came from -- no backtracking.
       */
      $n = $this->getChar($r - 1, $c);
      $s = $this->getChar($r + 1, $c);
      $e = $this->getChar($r, $c + 1);
      $w = $this->getChar($r, $c - 1);
      $next = $this->getChar($r + $rInc, $c + $cInc);

      $se = $this->getChar($r + 1, $c + 1);
      $ne = $this->getChar($r - 1, $c + 1);

      if ($this->isCorner($next) || $this->isEdge($next, $dir)) {
        return $this->walk($path, $r + $rInc, $c + $cInc, $dir, $d);
      } elseif ($dir != self::DIR_DOWN &&
                ($this->isCorner($n) || $this->isEdge($n, self::DIR_UP))) {
        /* Can't turn up into bottom corner */
        if (($cur != '.' && $cur != "'") || ($cur == '.' && $n != '.') ||
              ($cur == "'" && $n != "'")) {
          return $this->walk($path, $r - 1, $c, self::DIR_UP, $d);
        }
      } elseif ($dir != self::DIR_UP && 
                ($this->isCorner($s) || $this->isEdge($s, self::DIR_DOWN))) {
        /* Can't turn down into top corner */
        if (($cur != '.' && $cur != "'") || ($cur == '.' && $s != '.') ||
              ($cur == "'" && $s != "'")) {
          return $this->walk($path, $r + 1, $c, self::DIR_DOWN, $d);
        }
      } elseif ($dir != self::DIR_LEFT &&
                ($this->isCorner($e) || $this->isEdge($e, self::DIR_RIGHT))) {
        return $this->walk($path, $r, $c + 1, self::DIR_RIGHT, $d);
      } elseif ($dir != self::DIR_RIGHT &&
                ($this->isCorner($w) || $this->isEdge($w, self::DIR_LEFT))) {
        return $this->walk($path, $r, $c - 1, self::DIR_LEFT, $d);
      } elseif ($dir == self::DIR_SE &&
                ($this->isCorner($ne) || $this->isEdge($ne, self::DIR_NE))) {
        return $this->walk($path, $r - 1, $c + 1, self::DIR_NE, $d);
      } elseif ($dir == self::DIR_NE &&
                ($this->isCorner($se) || $this->isEdge($se, self::DIR_SE))) {
        return $this->walk($path, $r + 1, $c + 1, self::DIR_SE, $d);
      }
    } elseif ($this->isMarker($cur)) {
      /* We found a marker! Add it. */
      $path->addMarker($c, $r, Point::SMARKER);
      return;
    } else {
      /*
       * Not a corner, not a marker, and we already ate edges. Whatever this
       * is, it is not part of the line.
       */
      $path->addPoint($c - $cInc, $r - $rInc);
      return;
    }
  }

  /*
   * This function attempts to follow a line and complete it into a closed
   * polygon. It assumes that we have been called from a top point, and in any
   * case that the polygon can be found by moving clockwise along its edges.
   * Any time this algorithm finds a corner, it attempts to turn right. If it
   * cannot turn right, it goes in any direction other than the one it came
   * from. If it cannot complete the polygon by continuing in any direction
   * from a point, that point is removed from the path, and we continue on
   * from the previous point (since this is a recursive function).
   *
   * Because the function assumes that it is starting from the top left,
   * if its first turn cannot be a right turn to moving down, the object
   * cannot be a valid polygon. It also maintains an internal list of points
   * it has already visited, and refuses to visit any point twice.
   */
  private function wallFollow($path, $r, $c, $dir, $bucket = array(), $d = 0) {
    $d++;

    if ($dir == self::DIR_RIGHT || $dir == self::DIR_LEFT) {
      $cInc = ($dir == self::DIR_RIGHT) ? 1 : -1;
      $rInc = 0;
    } elseif ($dir == self::DIR_DOWN || $dir == self::DIR_UP) {
      $cInc = 0;
      $rInc = ($dir == self::DIR_DOWN) ? 1 : -1;
    }

    /* Traverse the edge in whatever direction we are going. */
    $cur = $this->getChar($r, $c);
    while ($this->isBoxEdge($cur, $dir)) {
      $r += $rInc;
      $c += $cInc;
      $cur = $this->getChar($r, $c);
    }

    /* We 'key' our location by catting r and c together */
    $key = "{$r}{$c}";
    if (isset($bucket[$key])) {
      return;
    }

    /*
     * When we run into a corner, we have to make a somewhat complicated
     * decision about which direction to turn.
     */
    if ($this->isBoxCorner($cur)) {
      if (!isset($bucket[$key])) {
        $bucket[$key] = 0;
      }

      switch ($cur) {
      case '.':
      case "'":
        $pointExists = $path->addPoint($c, $r, Point::CONTROL);
        break;

      case '#':
        $pointExists = $path->addPoint($c, $r);
        break;
      }

      if ($path->isClosed() || $pointExists) {
        return;
      }

      /*
      * Special case: if we're looking for our first turn and we can't make it
      * due to incompatible corners, keep looking, but don't adjust our call
      * depth so that we can continue to make progress.
      */
      if ($d == 1 && $cur == '.' && $this->getChar($r + 1, $c) == '.') {
        return $this->wallFollow($path, $r, $c + 1, $dir, $bucket, 0);
      }

      /*
       * We need to make a decision here on where to turn. We may have multiple
       * directions we can choose, and all of them might generate a closed
       * object. Always try turning right first.
       */
      $newDir = false;
      $n = $this->getChar($r - 1, $c);
      $s = $this->getChar($r + 1, $c);
      $e = $this->getChar($r, $c + 1);
      $w = $this->getChar($r, $c - 1);

      if ($dir == self::DIR_RIGHT) {
        if (!($bucket[$key] & self::DIR_DOWN) &&
            ($this->isBoxEdge($s, self::DIR_DOWN) || $this->isBoxCorner($s))) {
          /* We can't turn into another top edge. */
          if (($cur != '.' && $cur != "'") || ($cur == '.' && $s != '.') ||
              ($cur == "'" && $s != "'")) {
            $newDir = self::DIR_DOWN;
          }
        } else {
          /* There is no right hand turn for us; this isn't a valid start */
          if ($d == 1) {
            return;
          }
        }
      } elseif ($dir == self::DIR_DOWN) {
        if (!($bucket[$key] & self::DIR_LEFT) &&
            ($this->isBoxEdge($w, self::DIR_LEFT) || $this->isBoxCorner($w))) {
          $newDir == self::DIR_LEFT;
        } 
      } elseif ($dir == self::DIR_LEFT) {
        if (!($bucket[$key] & self::DIR_UP) &&
            ($this->isBoxEdge($n, self::DIR_UP) || $this->isBoxCorner($n))) {
          /* We can't turn into another bottom edge. */
          if (($cur != '.' && $cur != "'") || ($cur == '.' && $n != '.') ||
              ($cur == "'" && $n != "'")) {
            $newDir = self::DIR_UP;
          }
        } 
      } elseif ($dir == self::DIR_UP) {
        if (!($bucket[$key] & self::DIR_RIGHT) &&
            ($this->isBoxEdge($e, self::DIR_RIGHT) || $this->isBoxCorner($e))) {
          $newDir = self::DIR_RIGHT;
        } 
      }

      if ($newDir != false) {
        if ($newDir == self::DIR_RIGHT || $newDir == self::DIR_LEFT) {
          $cMod = ($newDir == self::DIR_RIGHT) ? 1 : -1;
          $rMod = 0;
        } elseif ($newDir == self::DIR_DOWN || $newDir == self::DIR_UP) {
          $cMod = 0;
          $rMod = ($newDir == self::DIR_DOWN) ? 1 : -1;
        }

        $bucket[$key] |= $newDir;
        $this->wallFollow($path, $r+$rMod, $c+$cMod, $newDir, $bucket, $d);
        if ($path->isClosed()) {
          return;
        }
      }

      /*
       * Unfortunately, we couldn't complete the search by turning right,
       * so we need to pick a different direction. Note that this will also
       * eventually cause us to continue in the direction we were already
       * going. We make sure that we don't go in the direction opposite of
       * the one in which we're already headed, or an any direction we've
       * already travelled for this point (we may have hit it from an
       * earlier branch). We accept the first closing polygon as the
       * "correct" one for this object.
       */
      if ($dir != self::DIR_RIGHT && !($bucket[$key] & self::DIR_LEFT) &&
          ($this->isBoxEdge($w, self::DIR_LEFT) || $this->isBoxCorner($w))) {
        $bucket[$key] |= self::DIR_LEFT;
        $this->wallFollow($path, $r, $c - 1, self::DIR_LEFT, $bucket, $d);
        if ($path->isClosed()) {
          return;
        }
      } 
      if ($dir != self::DIR_LEFT && !($bucket[$key] & self::DIR_RIGHT) &&
          ($this->isBoxEdge($e, self::DIR_RIGHT) || $this->isBoxCorner($e))) {
        $bucket[$key] |= self::DIR_RIGHT;
        $this->wallFollow($path, $r, $c + 1, self::DIR_RIGHT, $bucket, $d);
        if ($path->isClosed()) {
          return;
        }
      } 
      if ($dir != self::DIR_DOWN && !($bucket[$key] & self::DIR_UP) &&
          ($this->isBoxEdge($n, self::DIR_UP) || $this->isBoxCorner($n))) {
          if (($cur != '.' && $cur != "'") || ($cur == '.' && $n != '.') ||
              ($cur == "'" && $n != "'")) {
          /* We can't turn into another bottom edge. */
          $bucket[$key] |= self::DIR_UP;
          $this->wallFollow($path, $r - 1, $c, self::DIR_UP, $bucket, $d);
          if ($path->isClosed()) {
            return;
          }
        }
      } 
      if ($dir != self::DIR_UP && !($bucket[$key] & self::DIR_DOWN) &&
          ($this->isBoxEdge($s, self::DIR_DOWN) || $this->isBoxCorner($s))) {
          if (($cur != '.' && $cur != "'") || ($cur == '.' && $s != '.') ||
              ($cur == "'" && $s != "'")) {
          /* We can't turn into another top edge. */
          $bucket[$key] |= self::DIR_DOWN;
          $this->wallFollow($path, $r + 1, $c, self::DIR_DOWN, $bucket, $d);
          if ($path->isClosed()) {
            return;
          }
        }
      }

      /*
       * If we get here, the path doesn't close in any direction from this
       * point (it's probably a line extension). Get rid of the point from our
       * path and go back to the last one.
       */
      $path->popPoint();
      return;
    } elseif ($this->isMarker($this->getChar($r, $c))) {
      /* Marker is part of a line, not a wall to close. */
      return;
    } else {
      /* We landed on some whitespace or something; this isn't a closed path */
      return;
    }
  }

  /*
   * Clears an object from the grid, erasing all edge and marker points. This
   * function retains corners in "clearCorners" to be cleaned up before we do
   * text parsing.
   */
  private function clearObject($obj) {
    $points = $obj->getPoints();
    $closed = $obj->isClosed();

    $bound = count($points);
    for ($i = 0; $i < $bound; $i++) {
      $p = $points[$i];

      if ($i == count($points) - 1) {
        /* This keeps us from handling end of line to start of line */
        if ($closed) {
          $nP = $points[0];
        } else {
          $nP = null;
        }
      } else {
        $nP = $points[$i+1];
      }

      /* If we're on the same vertical axis as our next point... */
      if ($nP != null && $p->gridX == $nP->gridX) {
        /* ...traverse the vertical line from the minimum to maximum points */
        $maxY = max($p->gridY, $nP->gridY);
        for ($j = min($p->gridY, $nP->gridY); $j <= $maxY; $j++) {
          $char = $this->getChar($j, $p->gridX);

          if (!$this->isTick($char) && $this->isEdge($char) || $this->isMarker($char)) {
            $this->grid[$j][$p->gridX] = ' ';
          } elseif ($this->isCorner($char)) {
            $this->clearCorners[] = array($j, $p->gridX);
          } elseif ($this->isTick($char)) {
            $this->grid[$j][$p->gridX] = '+';
          }
        }
      } elseif ($nP != null && $p->gridY == $nP->gridY) {
        /* Same horizontal plane; traverse from min to max point */
        $maxX = max($p->gridX, $nP->gridX);
        for ($j = min($p->gridX, $nP->gridX); $j <= $maxX; $j++) {
          $char = $this->getChar($p->gridY, $j);

          if (!$this->isTick($char) && $this->isEdge($char) || $this->isMarker($char)) {
            $this->grid[$p->gridY][$j] = ' ';
          } elseif ($this->isCorner($char)) {
            $this->clearCorners[] = array($p->gridY, $j);
          } elseif ($this->isTick($char)) {
            $this->grid[$p->gridY][$j] = '+';
          }
        }
      } elseif ($nP != null && $closed == false && $p->gridX != $nP->gridX &&
                $p->gridY != $nP->gridY) {
        /*
         * This is a diagonal line starting from the westernmost point. It
         * must contain max(p->gridY, nP->gridY) - min(p->gridY, nP->gridY)
         * segments, and we can tell whether to go north or south depending
         * on which side of zero p->gridY - nP->gridY lies. There are no
         * corners in diagonals, so we don't have to keep those around.
         */
        $c = $p->gridX;
        $r = $p->gridY;
        $rInc = ($p->gridY > $nP->gridY) ? -1 : 1;
        $bound = max($p->gridY, $nP->gridY) - min($p->gridY, $nP->gridY);

        /*
         * This looks like an off-by-one, but it is not. This clears the
         * corner, if one exists.
         */
        for ($j = 0; $j <= $bound; $j++) {
          $char = $this->getChar($r, $c);
          if ($char == '/' || $char == "\\" || $this->isMarker($char)) {
            $this->grid[$r][$c++] = ' ';
          } elseif ($this->isCorner($char)) {
            $this->clearCorners[] = array($r, $c++);
          } elseif ($this->isTick($char)) {
            $this->grid[$r][$c] = '+';
          }
          $r += $rInc;
        }

        $this->grid[$p->gridY][$p->gridX] = ' ';
        break;
      }
    }
  }

  /*
   * Find style information for this polygon. This information is required to
   * exist on the first line after the top, touching the left wall. It's kind
   * of a pain requirement, but there's not a much better way to do it:
   * ditaa's handling requires too much text flung everywhere and this way
   * gives you a good method for specifying *tons* of information about the
   * object.
   */
  private function findCommands($box) {
    $points = $box->getPoints();
    $sX = $points[0]->gridX + 1;
    $sY = $points[0]->gridY + 1;
    $ref = '';
    if ($this->getChar($sY, $sX++) == '[') {
      $char = $this->getChar($sY, $sX++);
      while ($char != ']') {
        $ref .= $char;
        $char = $this->getChar($sY, $sX++);
      }

      if ($char == ']') {
        $sX = $points[0]->gridX + 1;
        $sY = $points[0]->gridY + 1;

        if (!isset($this->commands[$ref]['a2s:delref']) &&
            !isset($this->commands[$ref]['a2s:label'])) {
          $this->grid[$sY][$sX] = ' ';
          $this->grid[$sY][$sX + strlen($ref) + 1] = ' ';
        } else {
          if (isset($this->commands[$ref]['a2s:label'])) {
            $label = $this->commands[$ref]['a2s:label'];
          } else {
            $label = null;
          }

          $len = strlen($ref) + 2;
          for ($i = 0; $i < $len; $i++) {
            if (strlen($label) > $i) {
              $this->grid[$sY][$sX + $i] = substr($label, $i, 1);
            } else {
              $this->grid[$sY][$sX + $i] = ' ';
            }
          }
        }

        if (isset($this->commands[$ref])) {
          $box->setOptions($this->commands[$ref]);
        }
      }
    }

    return $ref;
  }
  
  /*
   * Extremely useful debugging information to figure out what has been
   * parsed, especially when used in conjunction with clearObject.
   */
  private function dumpGrid() {
    foreach($this->grid as $lines) {
      echo implode('', $lines) . "\n";
    }
  }

  private function getChar($row, $col) {
    if (isset($this->grid[$row][$col])) {
      return $this->grid[$row][$col];
    }

    return null;
  }

  private function isBoxEdge($char, $dir = null) {
    if ($dir == null) {
      return $char == '-' || $char == '|' || char == ':' || $char == '=' || $char == '*' || $char == '+';
    } elseif ($dir == self::DIR_UP || $dir == self::DIR_DOWN) {
      return $char == '|' || $char == ':' || $char == '*' || $char == '+';
    } elseif ($dir == self::DIR_LEFT || $dir == self::DIR_RIGHT) {
      return $char == '-' || $char == '=' || $char == '*' || $char == '+';
    }
  }

  private function isEdge($char, $dir = null) {
    if ($char == 'o' || $char == 'x') {
      return true;
    }

    if ($dir == null) {
      return $char == '-' || $char == '|' || $char == ':' || $char == '=' || $char == '*' || $char == '/' || $char == "\\";
    } elseif ($dir == self::DIR_UP || $dir == self::DIR_DOWN) {
      return $char == '|' || $char == ':' || $char == '*';
    } elseif ($dir == self::DIR_LEFT || $dir == self::DIR_RIGHT) {
      return $char == '-' || $char == '=' || $char == '*';
    } elseif ($dir == self::DIR_NE) {
      return $char == '/';
    } elseif ($dir == self::DIR_SE) {
      return $char == "\\";
    }
  }

  private function isBoxCorner($char) {
    return $char == '.' || $char == "'" || $char == '#';
  }

  private function isCorner($char) {
    return $char == '.' || $char == "'" || $char == '#' || $char == '+';
  }

  private function isMarker($char) {
    return $char == 'v' || $char == '^' || $char == '<' || $char == '>';
  }

  private function isTick($char) {
    return $char == 'o' || $char == 'x';
  }
}

/* vim:ts=2:sw=2:et:
 *  * */
