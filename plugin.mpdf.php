<?php
/**
 * @mainpage Libreria per la generazione dei file pdf
 * 
 * Plugin per la creazione di file PDF con la libreria mPDF (http://www.mpdf1.com/mpdf/).
 * mPDF è una classe PHP che genera file PDF da codice HTML con Unicode/UTF-8 e supporto CJK.
 * gino è stato testato con la versione 5.6.
 * 
 * INSTALLAZIONE
 * ---------------
 * 1. scaricare la libreria
 * 2. scompattare il file nella directory lib e rinominare la directory senza il numero di versione, ad esempio
 * @code
 * # mv MPDF56 MPDF
 * @endcode
 * 3. copiare il file mpdf.css nella directory css.
 * 4. copiare il file func.mpdf.php nella directory lib.
 * 
 * UTILIZZO
 * ---------------
 * Per attivare la classe occorre includerla all'inizio del file che genera il PDF:
 * @code
 * require_once(PLUGIN_DIR.OS.'plugin.mpdf.php');
 * @endcode
 * 
 * La classe fornisce gli strumenti per generare file pdf. In particolare prevede come output di:
 * - inviare il file inline al browser
 * - salvare localmente il file
 * - creare il file e inviarlo come allegato
 * Nel costruttore è inoltre possibile impostare come opzione la modalità debug.
 * 
 * NOTE
 * ---------------
 * La libreria mPDF può richiedere una quantità di memoria maggiore del previsto.
 * Per ovviare all'inconveniente occorre inserire la direttiva @a memory_limit nell'apposito file di configurazione di apache:
 * @code
 * php_admin_value memory_limit "32M"
 * @endcode
 * 
 * Se istanziare la classe genera degli errori PHP occorre inibire la stampa degli errori richiamando la funzione:
 * @code
 * error_reporting(0);
 * @endcode
 * 
 * ESEMPI
 * ---------------
 * Esempio di utilizzo del plugin
 * @code
 * $pdf = new pdf(array('output'=>$output, 'debug'=>$this->_debug_doc));
 * 
 * $header = $this->headerPDF($img_dir);
 * $footer = $this->footerPDF($img_dir);
 * $buffer = $pdf->htmlStart(array('header'=>$header, 'footer'=>$footer));
 * $buffer .= $this->htmlDoc($obj_pdf, $id);
 * $buffer .= $pdf->htmlEnd();
 * $html = $pdf->htmlCreate($buffer);
 * 
 * $filename = $this->fileName($id, array('type'=>'p'));
 * if($output == 'file')
 * {
 *   $filename = $dir.'/'.$filename;
 * }
 * $pdf->createPDF($html, $filename, array('title'=>_("Progetto"), 'author'=>_("Otto Srl"));
 * @endcode
 * 
 * Per non stampare il footer occorre impostare il parametro @a footer a  @a false in htmlStart().
 * 
 * Esempi di celle di tabella
 * @code
 * <td colspan="2" valign="top" align="center">text_label:<br />text_value</td>
 * <td width="50%" valign="top" rowspan="2">text_label:<br />text_value</td>
 * @endcode
 * 
 * IN SVILUPPO
 * ---------------
 * ###Gestione di una tabella per personalizzare lo stile di tabella
 * @code
 * CREATE TABLE IF NOT EXISTS `style_print` (
 *   `id` smallint(2) NOT NULL AUTO_INCREMENT,
 *   `reference` int(11) NOT NULL,
 *   `tablename` varchar(50) NOT NULL,
 *   `break` enum('no','yes') NOT NULL DEFAULT 'no',
 *   `onetable` enum('no','yes') CHARACTER SET utf8 NOT NULL DEFAULT 'no',
 *   PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 * @endcode
 */

/**
 * @file plugin.mpdf.php
 * @brief Classe per la generazione di file pdf
 * 
 * @copyright 2005 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author marco guidotti guidottim@gmail.com
 * @author abidibo abidibo@gmail.com
 */
class plugin_mpdf {
	
	/**
	 * Tipo di output
	 * 
	 * @var string
	 */
	private $_output;
	
	/**
	 * Modalità debug
	 * 
	 * @var boolean
	 */
	private $_debug;
	
	/**
	 * Stile della tabella
	 * 
	 * @var string
	 */
	private $_tbl_style;
	
	/**
	 * Costruttore
	 * 
	 * @param array	options
	 *   array associativo di opzioni
	 *   - @b output (string): tipo di output (default send)
	 *     - @a send: invia il file inline al browser
	 *     - @a file: salva localmente il file (indicare il percorso assoluto)
	 *     - @a email: crea un file PDF e lo invia come allegato
	 *   - @b debug (boolean): stampa a video il buffer (default false)
	 *   - @b table (string): stile della tabella (default style_print) [IN SVILUPPO]
	 */
	function __construct($options=array()){
		
		require_once(LIB_DIR.OS."MPDF".OS."mpdf.php");
		require_once(LIB_DIR.OS."func.mpdf.php");
		
		$this->_output = array_key_exists('output', $options) ? $options['output'] : 'send';
		$this->_debug = array_key_exists('debug', $options) ? $options['debug'] : false;
		$this->_tbl_style = array_key_exists('table', $options) ? $options['table'] : 'style_print';
		
		if($this->_output == 'file')
			$this->_output = 'F';
		elseif($this->_output == 'email')
			$this->_output = 'S';
		else
			$this->_output = 'I';
		
		$this->_tbl_style = 'style_print';
	}
	
	/**
	* Imposta l'header e il footer
	* 
	* @param array options
	*   - @b css_file (string): percorso a un file css (default css/mpdf.css)
	*   - @b css_style (string): stili css personalizzati (in un tag style)
	*   - @b header (string): header personalizzato
	*   - @b footer (mixed):
	*     - boolean, col valore @a false il footer non viene mostrato
	*     - string, footer personalizzato, sono implementate le stringhe sostitutive:
	*       - @a _NUMPAGE_, numero di pagina
	*       - @a _TOTPAGE_, numero totale di pagine
	*     - in tutti gli altri casi viene mostrato il footer standard
	*  @return string
	*
	* @example
	* @code
	* //FILE CSS
	* body {font-family: sans-serif; font-size: 10pt;}
	* p {margin: 0pt;}
	* td {vertical-align: top;}
	* .items td {border-left: 0.1mm solid #000000; border-right: 0.1mm solid #000000;}
	* table thead td { background-color: #EEEEEE; text-align: center; border: 0.1mm solid #000000;}
	* 
	* //HEADER
	* $logo = "<img style=\"width:1.56cm;\" src=\"".$this->_doc_img_dir."/logo.jpg\" />";
	* <table width=\"100%\" style=\"font-family:Arial,sans-serif; color:#999999;\"><tr>
	* <td width=\"20%\" style=\"font-size:10pt;\">$logo</td>
	* <td width=\"70%\" style=\"font-size:10pt; text-align:center;\">$header</td>
	* <td width=\"10%\" style=\"text-align:right; font-size:8pt;\">Doc. n: $number<br />Rev. N. $revision</td>
	* </tr></table>
	* 
	* //FOOTER
	* <div style=\"border-top:1px solid #BDDAF1; padding-top:3mm;\">
	* <table width=\"100%\" style=\"font-family:Arial,sans-serif; color:#999999;\"><tr>
	* <td width=\"80%\" style=\"text-align:left; font-size:6pt;\">$footer</td>
	* <td width=\"20%\" style=\"text-align:right; font-size:6pt;\">"._("Pagina")." _NUMPAGE_ "._("di")." _TOTPAGE_</td>
	* </tr></table>
	* </div>
	* @endcode
	*/
	public function htmlStart($options=array()){
		
		$css_file = array_key_exists('css_file', $options) ? $options['css_file'] : "css/mpdf.css";
		$css_style = array_key_exists('css_style', $options) ? $options['css_style'] : '';
		$header = array_key_exists('header', $options) ? $options['header'] : '';
		$footer = array_key_exists('footer', $options) ? $options['footer'] : '';
		
		$html = "<html>";
		$html .= "<head>";
		$html .= "<link href=\"$css_file\" type=\"text/css\" rel=\"stylesheet\" />";
		if($css_style)
			$html .= "<style>".$css_style."</style>";
		
		$html .= "</head>";
		$html .= "<body>\n";
		
		if(is_bool($footer) && $footer===false)
		{
			$footer = '';
		}
		elseif(is_string($footer) && $footer)
		{
			if(preg_match('#_NUMPAGE_#', $footer))
				$footer = preg_replace('#_NUMPAGE_#', '{PAGENO}', $footer);
			if(preg_match('#_TOTPAGE_#', $footer))
				$footer = preg_replace('#_TOTPAGE_#', '{nb}', $footer);
		}
		else
		{
			$footer = $this->defaultFooter();
		}
		$html .= "
<!--mpdf
<htmlpageheader name=\"myheader\">
$header
</htmlpageheader>

<htmlpagefooter name=\"myfooter\">
$footer
</htmlpagefooter>

<sethtmlpageheader name=\"myheader\" value=\"on\" show-this-page=\"1\" />
<sethtmlpagefooter name=\"myfooter\" value=\"on\" />
mpdf-->";
			
		return $html;
	}
	
	/**
	 * Footer standard
	 * 
	 * @return string
	 */
	public function defaultFooter() {
		
		$footer = "
<div style=\"border-top: 1px solid #000000; font-size: 6pt; text-align: center; padding-top: 3mm; \">
"._("Pagina")." {PAGENO} "._("di")." {nb}
</div>";
		return $footer;
	}
	
	/**
	 * Chiusura del testo html
	 * 
	 * @return string
	 */
	public function htmlEnd(){
		
		$html = "</body>\n";
		$html .= "</html>\n";
		return $html;
	}
	
	/**
	 * Crea un testo HTML compatibile con la generazione del PDF
	 * 
	 * @param string $html
	 * @return string or print (debug)
	 */
	public function htmlCreate($html){
		
		$html = pdfHtmlToEntities($html);
		$html = utf8_encode($html);
		
		if($this->_debug)
		{
			echo $html;
			exit();
		}
		else
			return $html;
	}
	
	/**
	 * Crea il PDF
	 * 
	 * @param mixed $html
	 *   - @a string, documento con pagine aventi la stessa struttura
	 *   - @a array, documento con pagine che possono cambiare struttura, come l'orientamento; struttura dell'array:
	 *     array([, string html], array(orientation=>[, string [L|P]], html=>[, string]), ...)
	 * @param string $filename
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b title (string): titolo del PDF
	 *   - @b author (string): autore del PDF
	 *   - @b creator (string): chi ha generato il PDF
	 *   - @b watermark (boolean): scritta in sovraimpressione (default false)
	 *   - @b watermark_text (string): testo della scritta in sovraimpressione (default 'esempio')
	 *   - @b landscape (boolean): orientamento orizzontale della pagina (default false)
	 *   - @b mode (string): codifica del testo (default utf-8)
	 *   - @b font_size (integer)
	 *   - @b font (string)
	 *   - @b top-margin (integer): distance in mm from top of page to start of text (ignoring any headers)
	 *   - @b header-margin (integer): distance in mm from top of page to start of header
	 *   - @b bottom-margin (integer): distance in mm from bottom of page to bottom of text (ignoring any footers)
	 *   - @b footer-margin (integer): distance in mm from bottom of page to bottom of footer
	 *   - @b orientation (string): specifica l'orientamento di una nuova pagina; i valori accettati sono:
	 *     - L, landscape
	 *     - P, portrait (default)
	 * @return output
	 * 
	 * Esempio:
	 * @code
	 * $sequence = array($html1, array('orientation'=>'L', 'html'=>$html2));
	 * $pdf->createPDF($sequence, $filename, array('title'=>_("Progetto"), 'author'=>_("Otto Srl"), 'creator'=>_("Marco Guidotti")));
	 * @endcode
	 */
	public function createPDF($html, $filename, $options=array()){
		
		$title = array_key_exists('title', $options) ? $options['title'] : '';
		$author = array_key_exists('author', $options) ? $options['author'] : '';
		$creator = array_key_exists('creator', $options) ? $options['creator'] : '';
		$watermark = array_key_exists('watermark', $options) ? $options['watermark'] : false;
		$watermark_text = array_key_exists('watermark_text', $options) ? $options['watermark_text'] : _("esempio");
		$landscape = array_key_exists('landscape', $options) ? $options['landscape'] : false;
		$mode = array_key_exists('mode', $options) ? $options['mode'] : 'utf-8';
		$default_font_size = array_key_exists('font_size', $options) ? $options['font_size'] : 0;
		$default_font = array_key_exists('font', $options) ? $options['font'] : '';
		$orientation = array_key_exists('orientation', $options) ? $options['orientation'] : 'P';
		
		$format = $landscape ? 'A4-L' : 'A4';
		
		if($format == 'A4')
		{
			$left_margin = 20;
			$right_margin = 15;
			$top_margin = 48;
			$bottom_margin = 25;
			$header_margin = 10;
			$footer_margin = 10;
		}
		else	// Valori di default come nel costruttore della classe mpdf
		{
			$left_margin = 15;
			$right_margin = 15;
			$top_margin = 16;
			$bottom_margin = 16;
			$header_margin = 9;
			$footer_margin = 9;
		}
		
		// Personalizzazione dei parametri
		if(array_key_exists('top-margin', $options) && !is_null($options['top-margin'])) $top_margin = $options['top-margin'];
		if(array_key_exists('header-margin', $options) && !is_null($options['header-margin'])) $header_margin = $options['header-margin'];
		if(array_key_exists('bottom-margin', $options) && !is_null($options['bottom-margin'])) $bottom_margin = $options['bottom-margin'];
		if(array_key_exists('footer-margin', $options) && !is_null($options['footer-margin'])) $footer_margin = $options['footer-margin'];
		
		$mpdf=new mPDF($mode, $format, $default_font_size, $default_font, $left_margin, $right_margin, $top_margin, $bottom_margin, $header_margin, $footer_margin, $footer_margin, $orientation);
		
		$mpdf->useOnlyCoreFonts = true;    // default false
		$mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor($author);
		$mpdf->SetCreator($creator);
		$mpdf->SetWatermarkText($watermark_text);
		$mpdf->showWatermarkText = $watermark;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');
		
		//$mpdf->StartProgressBarOutput(2);
		//$mpdf->shrink_tables_to_fit = 0;	// prevent all tables from resizing
		
		if(is_string($html))
		{
			$mpdf->WriteHTML($html);
		}
		elseif(is_array($html) AND sizeof($html) > 0)
		{
			$pages = $html;
			for($i=0, $end=sizeof($pages); $i<$end; $i++)
			{
				if($i==0)
				{
					$mpdf->WriteHTML($pages[$i]);
				}
				else
				{
					if(is_array($pages[$i]))
					{
						$orientation_page = array_key_exists('orientation', $pages[$i]) ? $pages[$i]['orientation'] : 'P';
						$html = array_key_exists('html', $pages[$i]) ? $pages[$i]['html'] : '';
					}
					else
					{
						$orientation_page = $landscape ? 'L' : 'P';
						$html = $pages[$i];
					}
					$mpdf->AddPageByArray(array('orientation'=>$orientation_page));
					$mpdf->WriteHTML($html);
				}
			}
		}
		
		if($this->_output == 'save')
		{
			$dirname = dirname($filename);
			if(!is_dir($dirname))
			{
				$this->_output = 'send';
				$filename = basename($filename);
			}
		}
		$mpdf->Output($filename, $this->_output);
		
		if($this->_output == 'send')
			exit();
	}
	
	/**
	 * Crea il PDF e lo invia via email
	 * 
	 * @param string $html contenuto del file
	 * @param string $filename nome del file allegato alla email
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b send (boolean): invia il file anche al browser (default false)
	 *   - @b mailto (string)
	 *   - @b from_name (string)
	 *   - @b from_mail (string)
	 *   - @b replyto (string)
	 *   - @b subject (string)
	 *   - @b message (string)
	 * @return void
	 * 
	 * @todo Verificare se occorre utilizzare \n al posto di \r\n
	 */
	public function emailPDF($html, $filename, $options=array()){
		
		$send = array_key_exists('send', $options) ? $options['send'] : false;
		
		$mailto = array_key_exists('mailto', $options) ? $options['mailto'] : '';
		$from_name = array_key_exists('from_name', $options) ? $options['from_name'] : '';
		$from_mail = array_key_exists('from_mail', $options) ? $options['from_mail'] : '';
		$replyto = array_key_exists('replyto', $options) ? $options['replyto'] : '';
		$subject = array_key_exists('subject', $options) ? $options['subject'] : '';
		$message = array_key_exists('message', $options) ? $options['message'] : '';
		
		$mpdf=new mPDF();
		$mpdf->WriteHTML($html);

		$content = $mpdf->Output('', 'S');
		$content = chunk_split(base64_encode($content));
		
		$uid = md5(uniqid(time()));
		
		$header = "From: ".$from_name." <".$from_mail.">\r\n";
		$header .= "Reply-To: ".$replyto."\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
		$header .= "This is a multi-part message in MIME format.\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
		$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$header .= $message."\r\n\r\n";
		$header .= "--".$uid."\r\n";
		$header .= "Content-Type: application/pdf; name=\"".$filename."\"\r\n";
		$header .= "Content-Transfer-Encoding: base64\r\n";
		$header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
		$header .= $content."\r\n\r\n";
		$header .= "--".$uid."--";
		$is_sent = @mail($mailto, $subject, "", $header);

		if($send)
			$mpdf->Output();
		exit();
	}
	
	/**
	 * Break di pagina
	 * 
	 * @return string
	 */
	public function breakPage(){
		
		return "<pagebreak />";
	}
	
	/**
	 * Contenitore di testo
	 * 
	 * @param string $text
	 * @return string
	 */
	public function longText($text){
		
		if(!empty($text))
			$text = "<div class=\"longtext\">$text</div>";
		
		return $text;
	}
	
	/**
	 * Gestione del testo
	 * 
	 * @param string $text
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b class (string): classe del tag span, es. 'label'
	 *   - @b style (string): stile del tag span, es. 'color:#000000; font-size:10px';
	 *   - @b other (string): altro nel tag span
	 *   - @b type (string): tipo di dato (default text)
	 *     - @a text, richiama il metodo pdfChars()
	 *     - @a textarea, richiama il metodo pdfChars_Textarea()
	 *     - @a editor, richiama il metodo pdfTextChars()
	 * @return string
	 */
	public function el($text, $options=array()){
		
		$class = array_key_exists('class', $options) ? $options['class'] : '';
		$style = array_key_exists('style', $options) ? $options['style'] : '';
		$other = array_key_exists('other', $options) ? $options['other'] : '';
		$type = array_key_exists('type', $options) ? $options['type'] : 'text';
		
		if($class)
			$class = "class=\"$class\"";
		if($style)
			$style = "style=\"$style\"";
		
		if($type == 'textarea')
			$method = 'pdfChars_Textarea';
		elseif($type == 'editor')
			$method = 'pdfTextChars';
		else
			$method = 'pdfChars';
		
		$text = $method($text);
		
		if($class OR $style OR $other)
		{
			$text = "<span $class$style$other>$text</span>";
		}
		
		return $text;
	}
	
	/**
	 * Ogni elemento è una tabella
	 * 
	 * @param array $data sequenza di tag TD, ad esempio: array("<td width=\"10%\">"._("ID").": $countid</td>", "<td width=\"15%\">"._("Quantità").": $quantity</td>")
	 * @param array $options
	 *   array associativo di opzioni
	 * @return string
	 */
	public function multiTable($data=array(), $options=array()){
		
		$class = array_key_exists('class', $options) ? $options['class'] : '';
		$style = array_key_exists('style', $options) ? $options['style'] : '';
		$autosize = array_key_exists('autosize', $options) ? $options['autosize'] : 1;
		$border = array_key_exists('border', $options) ? $options['border'] : 0;
		
		if($class) $class = " class=\"$class\"";
		if($style) $style = " style=\"$style\"";
		if($autosize) $autosize = " autosize=\"$autosize\"";
		if($border) $border = " border=\"$border\"";
		
		$GINO = "<table".$autosize.$border.$style.$class.">";
		$GINO .= "<thead>";
		$GINO .= "<tr>";
		if(sizeof($data) > 0)
		{
			foreach($data AS $td)
			{
				$GINO .= $td;
			}
		}
		$GINO .= "</thead>";
		$GINO .= "<tbody>";
		$GINO .= "</tbody>";
		$GINO .= "</table>";
		
		return $GINO;
	}
	
	/**
	 * 
	 * @param array $data elementi della tabella, ad esempio: array(array($record1_field1, $record1_field2), array($record2_field1, $record2_field2))
	 * @param array $header intestazioni della tabella, ad esempio: array("<td width=\"5%\">"._("ID")."</td>", "<td width=\"10%\">"._("Quantità")."</td>")
	 * @param array $options
	 */
	public function singleTable($data=array(), $header=array(), $options=array()){
		
		$class = array_key_exists('class', $options) ? $options['class'] : '';
		$style = array_key_exists('style', $options) ? $options['style'] : '';
		$autosize = array_key_exists('autosize', $options) ? $options['autosize'] : 1;
		$border = array_key_exists('border', $options) ? $options['border'] : 0;
		
		if($class) $class = " class=\"$class\"";
		if($style) $style = " style=\"$style\"";
		if($autosize) $autosize = " autosize=\"$autosize\"";
		if($border) $border = " border=\"$border\"";
		
		$GINO = "<table".$autosize.$border.$style.$class.">";
		$GINO .= "<thead>";
		$GINO .= "<tr>";
		if(sizeof($header) > 0)
		{
			foreach($header AS $value)
			{
				$GINO .= $value;
			}
		}
		$GINO .= "</tr>";
		$GINO .= "</thead>";
		
		$GINO .= "<tbody>";
		if(sizeof($data) > 0)
		{
			foreach($data AS $record)
			{
				if(sizeof($record) > 0)
				{
					foreach($record AS $field)
					{
						$GINO .= "<td valign=\"top\">".$field."</td>";
					}
				}
			}
		}
		$GINO .= "</tbody>";
		$GINO .= "</table>";
		
		return $GINO;
	}
	
	/*
	 * 	Opzioni di stampa (TODO)
	 */
	
	private function jsLib() {
	
		$GINO = '';
		$GINO .= "<script type=\"text/javascript\">\n";
		$GINO .= "function stylePrint(ref, table, result, method, params) {
			
			var call = 'formStylePrint';
			showDiv(result);
			
			var url = '".$this->_home."?pt[".$this->_className."-'+call+']';
			var data = 'ref='+ref+'&tbl='+table+'&m='+method+'&p='+params;
			sendPost(url, data, result, '', true);
		};\n";
		
		$GINO .= "</script>\n";
		
		return $GINO;
	}
	
	/**
	 * Valori di stampa
	 * 
	 * Es. di utilizzo:
	 * @code
	 * if($this->stylePrint($ref, 'break', $table) == 'yes')
	 *   $GINO .= "<pagebreak />";
	 * if($this->stylePrint($ref, 'onetable', $table) == 'yes')
	 *   $GINO .= $this->singleTable($data, $header, $options);
	 * else
	 *   $GINO .= $this->multiTable($data, $options);
	 * @endcode
	 */
	private function stylePrint($reference, $field, $table){
		
		if($field == 'break')
			$default = 'no';
		elseif($field == 'onetable')
			$default = 'no';
		else
			$default = 'no';
		
		$query = "SELECT $field FROM ".$this->_tbl_style." WHERE reference='$reference' AND tablename='$table'";
		$a = $this->_db->selectquery($query);
		if(sizeof($a) > 0)
		{
			foreach($a AS $b)
			{
				return $b[$field];
			}
		}
		return $default;
	}
	
	public function formStylePrint($reference=0, $key='', $table='', $div='', $method='', $params=''){
		
		if(empty($reference) AND empty($key))
		{
			$reference = cleanVar($_POST, 'ref', 'int', '');
			$key = cleanVar($_POST, $this->_param_step, 'string', '');
			$table = cleanVar($_POST, 'tbl', 'string', '');
			$div = cleanVar($_POST, 'div', 'string', '');
			$method = cleanVar($_POST, 'm', 'string', '');
			$params = cleanVar($_POST, 'p', 'string', '');
			$ajax = true;
		}
		else $ajax = false;
		
		$GINO = '';
		
		$GINO .= "<div class=\"boxform\">\n";
		$this->_gform = new GinoForm('gform_st', 'post', false);
		$this->_gform->load('dataform_st');
		
		$query = "SELECT id, break_prod, table_prod FROM ".$this->_tbl_style_print." WHERE reference='$reference' AND tablename='$table'";
		$a = $this->_db->selectquery($query);
		if(sizeof($a) > 0)
		{
			foreach($a AS $b)
			{
				$id = $b['id'];
				$break_prod = $b['break_prod'];
				$table_prod = $b['table_prod'];
				$action = $this->_act_modify;
				$submit = _("modifica");
			}
		}
		else
		{
			$id = 0;
			$break_prod = '';
			$table_prod = '';
			$action = $this->_act_insert;
			$submit = _("crea il record");
		}
		
		$link_close = $ajax ? $this->linkClose($div) : '';
		$ref_number = $this->_db->getFieldFromId($table, 'number', 'id', $reference);
		
		$GINO .= "<div class=\"area_title_a\">\n";
		$GINO .= "<div class=\"area_title_sx_a\">"._("Impostazioni stampa")." '$ref_number'</div>\n";
		$GINO .= "<div class=\"area_title_dx_a\">$link_close</div>\n";
		$GINO .= "<div class=\"null\"></div>\n";
		$GINO .= "</div>\n";
		
		$GINO .= "<div class=\"form\">\n";
		$GINO .= $this->_gform->form($this->_home."?pt[".$this->_className."-actionStylePrint]", '', '');
		$GINO .= $this->_gform->input('id', $id, 'hidden', '', '', '');
		$GINO .= $this->_gform->input('ref', $reference, 'hidden', '', '', '');
		$GINO .= $this->_gform->input('tbl', $table, 'hidden', '', '', '');
		$GINO .= $this->_gform->input('action', $action, 'hidden', '', '', '');
		$GINO .= $this->_gform->input('method', $method, 'hidden', '', '', '');
		$GINO .= $this->_gform->input('params', $params, 'hidden', '', '', '');
		
		$array =  array('yes'=>_("si"), 'no'=>_("no"));
		$GINO .= $this->_gform->radio('break', $break_prod, _("Break page prima della tabella prodotti"), 'req', '', '', 'array', $array, 'no', 'h', '');
		$GINO .= $this->_gform->radio('onetable', $table_prod, _("Prodotti in un'unica tabella"), 'req', '', '', 'array', $array, 'no', 'h', '');
		$GINO .= $this->_gform->cinput('submit', $submit, '', '', '', '', 'submit', 0, 0, '');
		$GINO .= $this->_gform->cform();
		$GINO .= "</div>\n";
		
		return $GINO;
	}
	
	public function  actionStylePrint(){
		
		$reference = cleanVar($_POST, 'ref', 'int', '');
		$id = cleanVar($_POST, 'id', 'int', '');
		$table = cleanVar($_POST, 'tbl', 'string', '');
		$action = cleanVar($_POST, 'action', 'string', '');
		$method = cleanVar($_POST, 'method', 'string', '');
		$params = cleanVar($_POST, 'params', 'string', '');
		
		$break = cleanVar($_POST, 'break', 'string', '');
		$onetable = cleanVar($_POST, 'onetable', 'string', '');
		
		// Return
		$link = $this->setParams($params);
		if(!empty($link)) $link_error = $link.'&'; else $link_error = $link;
		
		$redirect = $this->setRedirect($method);
		// End
		
		$this->_gform = new GinoForm('gform_st','post', true);
		$this->_gform->save('dataform_st');
		$req_error = $this->_gform->arequired();
		
		if($req_error > 0)
		EvtHandler::HttpCall($this->_home, $redirect, $link_error."error=01");
		
		if($action == $this->_act_insert AND empty($id))
		{
			$query_control = "SELECT id FROM ".$this->_tbl_style_print." WHERE reference='$reference' AND tablename='$table'";
			$a = $this->_db->selectquery($query_control);
			if(sizeof($a) > 0)
				EvtHandler::HttpCall($this->_home, $redirect, $link_error.'error=09');
			
			$query = "INSERT INTO ".$this->_tbl_style_print." (id, reference, tablename, break, onetable)
			VALUES ($id, $reference, '$table', '$break', '$onetable')";
			$result = $this->_db->actionquery($query);
		}
		elseif($action == $this->_act_modify AND !empty($id))
		{
			$query = "UPDATE ".$this->_tbl_style_print." SET break='$break', onetable='$onetable' WHERE id='$id'";
			$result = $this->_db->actionquery($query);
		}
		else
		{
			$result = false;
		}
		
		if($result)
		{
			EvtHandler::HttpCall($this->_home, $redirect, $link);
		}
		else
		{
			EvtHandler::HttpCall($this->_home, $redirect, $link_error.'error=09');
		}
	}
}
?>