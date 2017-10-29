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
    protected static $cssAlign=array(
        '' => 'media', 'left' => 'medialeft',
        'right' => 'mediaright', 'center' => 'mediacenter'
    );
    protected static $opening=<<<SVG
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<!-- Created with ASCIIToSVG (https://github.com/dhobsd/asciitosvg/) -->
<svg 
SVG;
    protected static $renderer=null;
    protected static $align='';
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
        switch ($state) {
        case DOKU_LEXER_ENTER :
            $spaces=array();
            preg_match( '/<( *)a2s( *)>/', $match, $spaces );
            $left=strlen($spaces[1]);
            $right=strlen($spaces[2]);
            $align='';
            if( ($right + $left) > 0 ) {
                if( $right > $left )
                    $align='left';
                elseif( $left > $right)
                    $align='right';
                else
                    $align='center';
            }
            self::$align=$align; // needed to pass $align to ODT LEXER_MATCHED render
            return array($state, $align, null); // odt renderer expects array size 3
        case DOKU_LEXER_UNMATCHED :
            $o = new dokuwiki\plugin\a2s\ASCIIToSVG($this->_prepare($match));
            $o->setDimensionScale(9, 16);
            $o->parseGrid();
            // save alignment for later use by ODT renderer
            return array($state, $o->render(), self::$align);
        case DOKU_LEXER_EXIT :
            return array($state, null, null); // odt renderer expects array size 3
        }
        return array();
    }

    /**
     * Render output, generic method. Call specialized renderer depending
     * on the mode.
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml, odt)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        switch($mode) {
        case 'xhtml':
            return $this->_render_xhtml($renderer, $data);
        break;
        case 'odt':
            return $this->_render_odt($renderer, $data);
        break;
        }
        return false;
    }

    /**
     * Render xhtml output. add data to the renderer doc.
     *
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    protected function _render_xhtml(Doku_Renderer $renderer, $data) {
        list($state, $value) = $data;

        switch ($state) {
        case DOKU_LEXER_ENTER :
            $align=self::$cssAlign[$value];
            $renderer->doc .= "<svg class=\"a2s {$align}\" ";
        break;
        case DOKU_LEXER_UNMATCHED :
            $renderer->doc .= $value;
        break;
        }
        return true;
    }

    /**
     * Render odt output.
     *
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    protected function _render_odt(Doku_Renderer $renderer, $data) {
        list($state, $svg_data, $align) = $data;

        if($state === DOKU_LEXER_UNMATCHED) {
            $dim=$this->_extract_XY_4svg( $svg_data );
            $renderer->_addStringAsSVGImage(self::$opening.$svg_data, $dim[0], $dim[1], $align);
        }
        return true;
    }

    /**
     * Find the SVG X and Y dimensions in the svg string of the image.
     * it searches for 'width="nnnpx" height="mmmpx"' in the first
     * given string and returns the dimension in inch.
     *
     * @param String  $svgtxt  The svg string to inspect
     * @return array the X and Y dimensions suitable as SVG dimensions
     */
    protected function _extract_XY_4svg( $svgtxt ) {
        $sizes=array();
        preg_match( '/width="(.*?)px" height="(.*?)px"/', $svgtxt, $sizes );
        array_shift($sizes);
        // assume a 96 dpi screen
        return array_map( function($v) { return ($v/96.0)."in"; }, $sizes );
    }
    /**
     * Prepare matched text for beeing parsed. Removes unnecessary blank lines
     * expand wikilinks to http absolute links. (absolute links because of
     * ODT export)
     *
     * @param String  $text  The matched a2s input string
     * @return String        the prepared string
     */
    protected function _prepare( $text ) {
        return preg_replace_callback(
                     '/"a2s:link":"
                     \\[\\[
                         ([^]|]*)    # The page_id
                         (\\|[^]]*)? # |description optional
                     ]]"
                     /x',
                     function( $match ) {
                         return '"a2s:link":"' . wl( cleanID($match[1]), '', true ) . '"';
                     },
                     preg_replace_callback(
                                  '/"a2s:link":"
                                  \\[\\[
                                      ([a-z0-9][-_.a-z0-9]*[a-z0-9])>([^]]*)
                                  ]]"
                                  /x',
                                  function( $match ) {
                                      return '"a2s:link":"' . $this->interwiki($match[1], $match[2]) . '"';
                                  },
                                  trim($text, "\r\n")
                     )
               );
    }
    function interwiki( $wiki, $id ) {
        if( null == self::$renderer ) {
            self::$renderer = p_get_renderer('xhtml');  // get renderer
            self::$renderer->interwiki = getInterwiki();  // populate the interwiki hash with the interwiki schemes
        }
        return self::$renderer->_resolveInterWiki($wiki,$id);
    }
}

// vim:ts=4:sw=4:et:
