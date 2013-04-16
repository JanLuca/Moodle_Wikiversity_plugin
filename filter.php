<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class for including pages from Wikiversity
 *
 * @author Jan Luca Naumann <jan@jans-seite.de>
 * @license CC-BY-SA 3.0 or later
 */

class filter_wikiversity extends moodle_text_filter {
	public function filter($text, array $options = array()) {
		global $PAGE;

		$regex = '@\[Include\-WV\].*\/\/(.*)\.wikiversity\.org\/wiki\/(.*)\??\[\/Include\-WV]@i';

		$already_replaced = array();

		$styles = '<link rel="stylesheet" href="https://bits.wikimedia.org/de.wikiversity.org/load.php?debug=false&amp;lang=de&amp;modules=ext.wikihiero%7Cmediawiki.legacy.commonPrint%2Cshared%7Cmw.PopUpMediaTransform%7Cskins.vector&amp;only=styles&amp;skin=vector&amp;*" />';
		$styles .= '<style type="text/css">.center{width: auto; text-align: left;} body {font-family: Arial,Verdana,Helvetica,sans-serif; font-size:13px;}</style>';
		
		if ( preg_match_all( $regex, $text, $matches, PREG_SET_ORDER ) ) {
			$text = $styles.$text;

			foreach( $matches as $match ) {
				$wv_lang = $match[1];
				$title = $match[2];

				if( !empty( $already_replaced[$wv_lang.$title] ) ) continue;

				$url = 'https://' . $wv_lang . '.wikiversity.org/w/api.php?action=parse&format=php&prop=text&page=' . $title;

				$curl = new curl( array( 'cache' => true, 'module_cache' => 'filter_wikiversity' ) );

				$page = $curl->get( $url );
				$page = unserialize( $page );

				$page = $page['parse']['text']['*'];

				$add_link = '<a href="https://' . $wv_lang . '.wikiversity.org/wiki/' . $title . '">https://' . $wv_lang . '.wikiversity.org/wiki/' . $title . '</a>';
				$add = get_string( 'isfrom', 'filter_wikiversity', $add_link );
				$add .= get_string( 'license', 'filter_wikiversity' );

				$add_link = '<a href="https://' . $wv_lang . '.wikiversity.org/w/index.php?title=' . $title . '&action=history">';
				$add .= get_string( 'authors', 'filter_wikiversity', $add_link );

				$page .= '<hr />' . $add;

				$page = str_replace( 'href="/wiki', 'href="https://' . $wv_lang . '.wikiversity.org/wiki', $page );
				$page = str_replace( 'href="/w/', 'href="https://' . $wv_lang . '.wikiversity.org/w/', $page );

				if ( !$PAGE->user_is_editing() ) {
					$regex_edit = '@\<span class=\"editsection\"\>\[.*?\]\<\/span\>@i';
					$page = preg_replace( $regex_edit, '', $page );
				}

				$text = str_replace( $match[0], $page, $text );

				$already_replaced[$wv_lang.$title] = true;
			}
		}

		return $text;
	}
}
?>