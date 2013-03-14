<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

/**
 * REDCap View is a class of static functions that build HTML elements.
 */
class RCView {

	/** The amount to indent each level of HTML. */
	const INDENT = "\t";
	
	/** HTML for non-breaking space. */
	const SP = '&nbsp;';
	
	/** Used to generate unique IDs for HTML elements. */
	private static $jsId = 0;
	
	/** Returns a unique HTML element ID. */
	static function getId() {
		self::$jsId++;
		return 'redcapJSAutoId_' . self::$jsId;
	}
	
	/**#@+ Convenience functions for various HTML elements. */
	static function i($html) { return self::toHtml('i', array(), $html, true); }
	static function b($html) { return self::toHtml('b', array(), $html, true); }
	static function br() { return self::toHtml('br', array(), false, true); }
	static function input($attrs) { return self::toHtml('input', $attrs, false); }
	static function hidden($attrs) {
		$attrs['type'] = 'hidden';
		return self::toHtml('input', $attrs, false);
	}
	static function submit($attrs) {
		$attrs['type'] = 'submit';
		return self::toHtml('input', $attrs, false);
	}
	static function file($attrs) {
		$attrs['type'] = 'file';
		return self::toHtml('input', $attrs, false);
	}
	static function checkbox($attrs) {
		$attrs['type'] = 'checkbox';
		return self::toHtml('input', $attrs, false);
	}
	static function text($attrs) {
		$attrs['type'] = 'text';
		return self::toHtml('input', $attrs, false);
	}
	static function radio($attrs) {
		$attrs['type'] = 'radio';
		return self::toHtml('input', $attrs, false);
	}
	static function font($attrs, $html, $suppressNewlines=true) {
		return self::toHtml('font', $attrs, $html, $suppressNewlines);
	}
	static function label($attrs, $html, $suppressNewlines=true) {
		return self::toHtml('label', $attrs, $html, $suppressNewlines);
	}
	static function button($attrs, $html) {
		return self::toHtml('button', $attrs, $html);
	}
	static function div($attrs, $html) {
		return self::toHtml('div', $attrs, $html);
	}
	static function span($attrs, $html, $suppressNewlines=true) {
		return self::toHtml('span', $attrs, $html, $suppressNewlines);
	}
	static function form($attrs, $html) {
		return self::toHtml('form', $attrs, $html);
	}
	static function a($attrs, $html) {
		return self::toHtml('a', $attrs, $html, true, true);
	}
	static function h1($attrs, $html) {
		return self::toHtml('h1', $attrs, $html);
	}
	static function h2($attrs, $html) {
		return self::toHtml('h2', $attrs, $html);
	}
	static function h3($attrs, $html) {
		return self::toHtml('h3', $attrs, $html);
	}
	static function table($attrs, $html) {
		return self::toHtml('table', $attrs, $html);
	}
	static function tr($attrs, $html) {
		return self::toHtml('tr', $attrs, $html);
	}
	static function td($attrs, $html) {
		return self::toHtml('td', $attrs, $html);
	}
	static function th($attrs, $html) {
		return self::toHtml('th', $attrs, $html);
	}
	static function fieldset($attrs, $html) {
		return self::toHtml('fieldset', $attrs, $html);
	}
	static function legend($attrs, $html) {
		return self::toHtml('legend', $attrs, $html);
	}
	static function img($attrs) {
		// Does not require that APP_PATH_IMAGES be used in SRC attr, but if added, then don't add a second time.
		if (isset($attrs['src']) && substr($attrs['src'], 0, strlen(APP_PATH_IMAGES)) != APP_PATH_IMAGES) {
			$attrs['src'] = APP_PATH_IMAGES . $attrs['src'];
		}
		return self::toHtml('img', $attrs, false);
	}
	static function p($attrs, $html) {
		return self::toHtml('p', $attrs, $html);
	}
	static function li($attrs, $html) {
		return self::toHtml('li', $attrs, $html);
	}
	static function ul($attrs, $html) {
		return self::toHtml('ul', $attrs, $html);
	}
	static function ol($attrs, $html) {
		return self::toHtml('ol', $attrs, $html);
	}
	static function textarea($attrs, $html) {
		return self::toHtml('textarea', $attrs, $html, false, true);
	}
	static function pre($attrs, $html) {
		return self::toHtml('pre', $attrs, $html, false, true);
	}
	/**#@-*/
	
	/** Makes a link using an icon.
	 * @param string $id the ID attribute of the link.
	 * @param string $icon the image src.
	 * @param string $title the title and alt for the image.
	 * @param string $url the href for the link (minus query string).
	 * @param array $qvars the variables used to make the query string. The values will be automatically
	 * encoded for URL.
	 */
	static function iconLink($id, $icon, $title, $url, $qvars) {
		$imgAttrs = array('src' => $icon, 'title' => $title, 'alt' => $title, 'class' => 'imgfix2');
		$imgHtml = self::toHtml('img', $imgAttrs, false, true);
		if (is_array($qvars) && count($qvars) > 0) {
			$pairs = array();
			foreach ($qvars as $key => $val) $pairs[] = "$key=" . urlencode($val);
			$url .= '?' . implode('&', $pairs);
		}
		$aAttrs = array('id' => $id, 'href' => $url);
		return self::toHtml('a', $aAttrs, $imgHtml);
	}
	
	/** Makes a very simple unordered list. */
	static function simpleList($items, $escape=false, $listAttrs=array()) {
		foreach ($items as $i) $html .= self::toHtml('li', array(), $escape ? self::escape($i) : $i);
		return self::toHtml('ul', $listAttrs, $html);
	}
	
	/**
	 * Makes a select box.
	 * @param array $attrs see self::toHTML() $attrs param.
	 * @param array $opts the select box options. Values will be automatically escaped for HTML and
	 * trimmed to a reasonable size.
	 * @param string $selKey the option to select by default.
	 */
	static function select($attrs, $opts, $selKey=null, $maxOptChars=55) {
		$o = '';
		$maxNormal = $maxOptChars;
		$maxShout = round($maxOptChars/2); // show less characters if the option is SHOUTING
		foreach ($opts as $key => $val) {
			$val = self::escape($val);
			$max = preg_match('/[a-z]/', $val) ? $maxNormal : $maxShout;
			$oAttrs = array('value' => $key);
			if ($selKey."" === $key."") $oAttrs['selected'] = 'selected';
			if (strlen($val) > $max) $val = substr($val, 0, $max-3) . '...';
			$o .= self::toHtml('option', $oAttrs, $val);
		}
		return self::toHtml('select', $attrs, $o);
	}
	
	/**
	 * Creates an Enabled/Disabled select box.
	 * @param array $attrs see self::toHTML() $attrs param.
	 * @param boolean $enabled true if the select box should default to Enabled;
	 * false to default to Disabled.
	 */
	static function selectEnabledDisabled($attrs, $enabled) {
		$opts = array(0 => RCL::disabled(), 1 => RCL::enabled());
		return self::select($attrs, $opts, ($enabled ? 1 : 0));
	}
	
	/** Displays a box with an error message inside, prepended with error icon/text. */
	static function errorBox($html, $id='') {
		global $lang;
		$h = '';
		$h .= self::toHtml('img', array('src' => APP_PATH_IMAGES . 'exclamation.png', 'class' => 'imgfix'));
		$h .= $lang['global_01'] . $lang['colon'] . ' ';
		$attrs = array('class' => 'red', 'style' => 'margin-bottom: 20px;', 'id' => $id, 'title' => $lang['global_01']);
		return self::toHtml('div', $attrs, $h . $html);
	}
	
	/** Displays a box with a success message inside, prepended with check icon. */
	static function successBox($html, $id='') {
		global $lang;
		$h = '';
		$h .= self::toHtml('img', array('src' => APP_PATH_IMAGES . 'tick.png', 'class' => 'imgfix'));
		$h .= $lang['setup_08'] . ' ';
		$attrs = array('class' => 'darkgreen', 'style' => 'margin-bottom: 20px;', 'id' => $id, 'title' => $lang['setup_08']);
		return self::toHtml('div', $attrs, $h . $html);
	}
	
	/** Displays a box with a warning message inside, prepended with warning icon. */
	static function warnBox($html, $id='') {
		global $lang;
		$h = '';
		$h .= self::toHtml('img', array('src' => APP_PATH_IMAGES . 'error.png', 'class' => 'imgfix'));
		$h .= $lang['global_03'] . $lang['colon'] . ' ';
		$attrs = array('class' => 'yellow', 'style' => 'margin-bottom: 20px;', 'id' => $id, 'title' => $lang['global_03']);
		return self::toHtml('div', $attrs, $h . $html);
	}
	
	/** Displays a box with a confirmation message inside, prepended with confirmation icon. */
	static function confBox($html, $id='') {
		global $lang;
		$h = '';
		$h .= self::toHtml('img', array('src' => APP_PATH_IMAGES . 'exclamation_orange.png', 'class' => 'imgfix'));
		$attrs = array('class' => 'yellow', 'style' => 'margin-bottom: 20px;', 'id' => $id, 'title' => $lang['global_02']);
		return self::toHtml('div', $attrs, $h . $html);
	}
	
	/**
	 * Uses flexigrid to build a very simple table.
	 * @param array $rows the first element is the table title (string), the
	 * second element is an array of column headers, and the subsequent elements
	 * are arrays of column data.
	 * @param type $widths the width in pixels of each column.
	 */
	static function simpleGrid($rows, $widths) {
		$r = '';
		// build the title row
		$title = array_shift($rows);
		if (!empty($title))
			$r .= self::div(array('class' => 'mDiv'), self::div(array('class' => 'ftitle'), $title));
		// build the header row
		$hdr = array_shift($rows);
		if ($hdr !== null) {
			$h = '';
			for ($i = 0; $i < count($widths); $i++) {
				$h .= self::th(array(), self::div(array('style' => 'width: ' . $widths[$i] . 'px;'), $hdr[$i]));
			}
			$r .= self::div(array('class' => 'hDiv'), self::div(array('class' => 'hDivBox'),
							self::table(array('cellspacing' => '0'), self::tr(array(), $h))));
		}
		// build the data rows
		$h = ''; $rowCnt = 1;
		foreach ($rows as $row) {
			$cells = '';
			for ($i = 0; $i < count($widths); $i++) {
				$cells .= self::td(array(), self::div(array('style' => 'width: ' . $widths[$i] . 'px;'), $row[$i]));
			}
			$rowAttrs = $rowCnt % 2 == 0 ? array('class' => 'erow') : array();
			$h .= self::tr($rowAttrs, $cells);
			$rowCnt++;
		}
		$r .= self::div(array('class' => 'bDiv'), self::table(array('cellspacing' => '0'), $h));
		// take into account the padding (10px per cell) when calulating total width
		$totalWidth = array_sum($widths) + 10 * count($widths);
		return self::div(array('class' => 'flexigrid', 'style' => 'width: ' . $totalWidth . 'px;'), $r);
	}
	
	/**
	 * Builds a simple 2-column table intended for user input.
	 * @param string $title a title displayed at the top of the table.
	 * @param array $rowArr each element is an array representing a row:
	 * $arr = current($rowArr);
	 * $arr['label'] = HTML explanation of the required input
	 * $arr['input'] = HTML representing the input field(s)
	 * $arr['info'] = HTML additional instructions to appear under the input.
	 */
	static function simpleInputTable($title, $rowArr) {
		$h = '';
		if (!empty($title)) {
			$h .= self::tr(array(),
							self::td(array('colspan' => '2', 'style' => 'padding: 10px;'),
											self::font(array('class' => 'redcapBlockTitle'), $title)));
		}
		foreach ($rowArr as $arr) {
			$label = empty($arr['label']) ? '' : $arr['label'];
			$input = empty($arr['input']) ? '' : $arr['input'];
			$info = empty($arr['info']) ? '' : $arr['info'];
			$h .= self::tr(array(),
							self::td(array('class' => 'cc_label'), $label) .
							self::td(array('class' => 'cc_data'), $input .
											(empty($info) ? '' : self::div(array('class' => 'cc_info'), $info))));
		}
		return self::table(array('style' => 'border: 1px solid #ccc; background-color: #f0f0f0; margin: 20px 0;'), $h);
	}
	
	/**
	 * Escapes a string for use in HTML.
	 * @param boolean/integer $removeAllTags true to blindly escape all HTML tags;
	 * false to first perform some user-friendly sanitation in cases where the
	 * HTML is displayed to the user (e.g., in a title).
	 */
	static function escape($s, $removeAllTags=true) {
		$s = html_entity_decode($s, ENT_QUOTES);
		if ($removeAllTags) $s = strip_tags($s);
		return htmlspecialchars($s, ENT_QUOTES);
	}
	
	/**
	 * Quotes a PHP string for inclusion in JavaScript. Example:
	 * PHP:
	 * $foo = 'I love "quotes"';
	 * JS:
	 * alert(<?php RCView::strToJS($foo); ?>);
	 * Also replaces newlines with spaces because INI strings can have newlines
	 * purely for code-formatting reasons.
	 */
	static function strToJS($s) {
		return '"' . str_replace(array('"', "\r\n", "\n"), array('\\"', " ", " "), $s) . '"';
	}
	
	/**
	 * Builds an HTML element as requested by the public functions of this class.
	 * @param string $elemType e.g. input, form, hidden, etc.
	 * @param array $attrs keys are attribute names and values are the attribute values. All values will
	 * be encoded for HTML, therefore JavaScript should *NOT* be used here; instead, use jQuery to
	 * bind to events of this element within a $(function(){}) block.
	 * @param string $html any HTML to be included within the open/close tags of this element. If this
	 * is === FALSE, then the tag of this element will self-close.
	 * @param boolean $suppressNewline true if you do not want to follow this element with a newline.
	 * @param boolean $forceOneLiner if true, no indenting will be done and no newlines
	 * will be added that preceed or follow the $html.
	 */
	private static function toHtml($elemType, $attrs, $html=false, $suppressNewline=false, $forceOneLiner=false) {
		$h = "<$elemType";
		foreach ($attrs as $key => $val) {
			if ($key == "") continue;
			$h .= " $key=\"" . self::escape($val, true) . '"';
		}
		if ($html === false) $h .= "/>";
		else {
			$h .= ">";
			if (strlen($html) > 0) {
				// if there are no newlines in the HTML then assume we want a one-liner
				if (strpos($html, "\n") === false || $forceOneLiner) $h .= $html;
				// newlines in the HTML imply that we should add a level of indentation
				else {
					$h .= "\n";
					// ugly hack to deal with elements that contain newline-sensitive text
					$hackMap = array();
					foreach (array('textarea', 'pre') as $elem) {
						$elem . ' ' . preg_match_all("|<$elem.*?>.*?</$elem>|is", $html, $matches);
						foreach ($matches[0] as $match) {
							$hackKey = '{REPLACEME_HACK_' . count($hackMap) . '}';
							$hackMap[$hackKey] = $match;
							$html = str_replace($match, $hackKey, $html);
						}
					}
					$lines = explode("\n", $html);
					foreach ($lines as $line)
						if (!empty($line)) $h .= self::INDENT . "$line\n";
					// clean up after our ugly hack
					foreach ($hackMap as $hackKey => $hackStr)
						$h = str_replace($hackKey, $hackStr, $h);
				}
			}
			$h .= "</$elemType>";
		}
		if (!$suppressNewline) $h .= "\n";
		return $h;
	}
	
	/**
	* Exports file data, causing the user's browser to download/open the file.
	* @param string $filename the name that the file will be given.
	* @param string $content the contents of the file.
	* @param string $type the MIME type.
	*/
	static function exportFile($filename, $content, $type) {
		header('Cache-Control: max-age=0, must-revalidate');
		header('Content-Description: File Transfer');
		header('Content-type: ' . $type);
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-length: ' . strlen($content));
		echo $content;
		exit();
	}
	
	/** Returns button to return to previous page (with default text) **/
	public static function btnGoBack($text=null) {
		global $lang;
		if ($text == null) $text = $lang['global_77'];
		$img = self::img(array('style'=>'vertical-align:middle;','src'=>'arrow_left.png'));
		$txt = self::span(array('style'=>'vertical-align:middle;'), $text);
		return self::button(array('class'=>'jqbuttonmed','onclick'=>'history.go(-1)'), $img . $txt);
	}
	
	/** Returns a note to display to the user regarding disabled API status. */
	static function disabledAPINote() {
		global $lang;
		global $super_user;
		$note = '';
		$note .= $lang['api_01'] . ' ';
		if ($super_user) {
			$note .= $lang['api_07'] . ' ';
			$note .= RCView::a(array('target' => '_blank',
					'style' => 'text-decoration:underline;',
					'href' => APP_PATH_WEBROOT . 'ControlCenter/modules_settings.php'),
						$lang['graphical_view_07']);
		}
		else $note .= $lang['api_06'];
		return $note;
	}
	
	/** Returns hidden div with simpleDialog class to be displayed via jQueryUI dialog() function **/
	public static function simpleDialog($content="",$title="",$id="") {
		$titleAttr = ($title == "") ? "" : "title";
		$idAttr    = ($id == "") ? "" : "id";
		return self::div(array('class'=>'simpleDialog',$titleAttr=>$title,$idAttr=>$id), $content);
	}
}