<?php
/**
 * DokuWiki Plugin a2s (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Schplurtz le Déboulonné <Schplurtz@laposte.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_a2s extends DokuWiki_Syntax_Plugin {
    /**
     * return some info.
     * Why did I copy this function here ? old DW compat ???
     * 
     * @return array hash of plugin technical informations.
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'protected';
    }
    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'block';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 610;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('< *a2s *>(?=.*?</a2s>)',$mode,'plugin_a2s');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</a2s>','plugin_a2s');
    }

    /**
     * Handle matches of the a2s syntax
     *
     * @param string          $match   The match of the syntax
     * @param int             $state   The state of the handler
     * @param int             $pos     The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){
        require_once(dirname(__FILE__).'/a2s.php');
        switch ($state) {
          case DOKU_LEXER_ENTER :
            return array($state, $match);
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
          case DOKU_LEXER_EXIT :
            return array($state, '');
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array();
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        if($mode != 'xhtml') return false;
        $state='';
        list($state, $match) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :
            $a=2;
            // TODO: check $match left and right spaces, in order to align.
        break;
        case DOKU_LEXER_UNMATCHED :
            $scale = array(9, 16);
            $o = new \org\dh0\a2s\ASCIIToSVG($match);
            $o->setDimensionScale(9, 16);
            $o->parseGrid();
            $renderer->doc .= $o->render();
        break;
        case DOKU_LEXER_EXIT :
            $a=2;
        break;
        }
        return true;
    }
}

// vim:ts=4:sw=4:et:
