<?php
/**
 * @mainpage Libreria per la generazione dei file pdf
 * 
 * Plugin per la creazione di file PDF con la libreria mPDF (http://www.mpdf1.com/mpdf/).
 * mPDF è una classe PHP che genera file PDF da codice HTML con Unicode/UTF-8 e supporto CJK.
 * gino è stato testato con la versione 6.0.0.
 * 
 * INSTALLAZIONE
 * ---------------
 * 1. Scaricare la libreria
 * 2. Scompattare il file nella directory lib e rinominare la directory senza il numero di versione, ad esempio
 * @code
 * # mv mpdf57 mpdf
 * @endcode
 * 3. Copiare il file mpdf.css nella directory css.
 * 4. Copiare il file func.mpdf.php nella directory lib.
 * 
 * ###Operazioni aggiuntive
 * Se si attiva la visualizzazione della barra di progresso occorre assegnare i permessi read/write alla directory mpdf/tmp/
 * @code
 * # chmod 777 tmp/
 * @endcode
 * 
 * Nel caso in cui si riscontra un errore di questo tipo:
 * @code
 * file_put_contents(/.../ttfontdata/dejavusanscondensed.GSUBGPOStables.dat): failed to open stream: Permission denied ...
 * @endcode
 * assegnare i permessi read/write alla directory mpdf/ttfontdata/
 * 
 * Quando si aggiorna la versione della libreria mPDF occorre verificare se sono stati aggiornati nella classe mPDF i metodi che eventualmente la classe custom_mpdf sovrascrive. \n
 * Nel caso di di modifiche sostanziali modificare i metodi della classe custom_mpdf.
 * 
 * FUNZIONAMENTO
 * ---------------
 * Il file plugin.mpdf.php comprende tre classi:
 *   - @a gino_mpdf
 *   - @a plugin_mpdf
 *   - @a custom_mpdf
 * 
 * La classe gino_mpdf raggruppa i metodi di definizione dei contenuti; questi metodi possono essere sovrascritti da una classe costruita appositamente per la generazione di uno specifico file pdf. \n
 * La classe plugin_mpdf contiene i metodi di impostazione del pdf. \n
 * La classe custom_mpdf estende mPDF per la personalizzazione degli output html.
 * 
 * I metodi che devono essere richiamati dalle applicazioni per generare i pdf sono:
 *   - gino_mpdf::pdfFromPage()
 *   - gino_mpdf::create()
 * 
 * Il metodo @a gino_mpdf::pdfFromPage() viene utilizzato per generare il pdf della visualizzazione di una pagina web, mentre @a gino_mpdf::create() per generare un file con un html personalizzato.
 * 
 * 
 * gino_mpdf::create() istanzia plugin_mpdf e richiama plugin_mpdf::makeFile() \n
 * plugin_mpdf::makeFile() istanzia custom_mpdf che estende mPDF
 * 
 * La classe custom_mpdf contiene i metodi che sovrascrivono i metodi di mPDF. \n
 * La classe plugin_mpdf costruisce il file pdf; attraverso il metodo plugin_mpdf::setPhpParams() è possibile impostare alcuni parametri php. \n
 * La classe gino_mpdf funge da interfaccia alla classe plugin_mpdf e definisce le impostazioni base del file pdf (gino_mpdf::defineBasicOptions()), l'header e il footer di default, il nome standard del file.
 * 
 * MODO DI UTILIZZO
 * ---------------
 * Per attivare la classe occorre includere il file della libreria:
 * @code
 * require_once(PLUGIN_DIR.OS.'plugin.mpdf.php');
 * @endcode
 * 
 * ###GENERAZONE DEL PDF DI UNA PAGINA WEB
 * L'esempio ipotizza la generazione del pdf del post di un blog (file class_blog.php, metodo detail())
 * @code
 * $pdf = \Gino\cleanVar($request->GET, 'pdf', 'int', '');
 * if($pdf)
 * {
 *   require_once(PLUGIN_DIR.OS.'plugin.mpdf.php');
 *   require_once(LIB_DIR.OS.'func.mpdf.php');
 *   
 *   \Gino\Plugin\plugin_mpdf::setPhpParams(array('disable_error'=>false));
 *   
 *   $obj_pdf = new \Gino\Plugin\gino_mpdf();
 *   return $obj_pdf->pdfFromPage($view->render($dict), array(
 *     'css_file' => array('app/blog/pdf.css', 'css/mpdf.css'), 
 *     'filename' => 'blog.pdf'
 *   ));
 * }
 * @endcode
 * 
 * ###GENERAZONE DI UN FILE PDF COSTRUITO AD HOC
 * La procedura da seguire è indicativamente la seguente:
 * 1. creare una classe per la definizione dei contenuti che estenda la classe gino_mpdf (es child_1)
 * 2. in questa classe (child_1) o in una sua ulteriore classe figlia (child_2) definire header, footer e contenuti sovrascrivendo i metodi gino_mpdf::header(), gino_mpdf::footer() e gino_mpdf::content()
 * 3. istanziare child_1/child_2 passandogli l'opzione @a html ed eventuali altre opzioni specifiche
 * 
 * @code
 * $child_1 = new child_1(array(['opt1'=>val1, ...] 'html'=>[true|false]));
 * return $child_1->generate($options);
 * @endcode
 * 
 * L'header e il footer del pdf devono essere passati come opzioni al metodo plugin_mpdf::htmlStart(); 
 * per non stampare il footer occorre impostare il parametro @a footer a @a false. \n
 * Per visualizzare un esempio di header e footer vedere i metodi gino_mpdf::defaultHeader() e gino_mpdf::defaultFooter().
 * 
 * ###OUTPUT
 * La libreria gestisce i seguenti output:
 *   - stampare a video l'html (string)
 *   - inviare il file inline al browser (inline)
 *   - salvare localmente il file (file)
 *   - far scaricare il file (download)
 *   - creare il file e inviarlo come allegato email
 * 
 * @code
 * gino_mpdf::create(array('output'=>[value]))
 * @endcode
 * 
 * ###DEBUG
 * Per attivare la modalità debug occorre passare l'opzione @a debug a gino_mpdf::create() o a gino_mpdf::pdfFromPage() che lo richiama.
 * @code
 * gino_mpdf::create(array('debug'=>true))
 * @endcode
 * 
 * Il debug viene poi gestito nella classe plugin_mpdf().
 * 
 * VARIE
 * ---------------
 * ###Memoria
 * La libreria mPDF utilizza una quantità notevole di memoria; nel caso in cui venga visualizzato un messaggio di errore di superamento del limite di memoria come ad esempio
 * @code
 * Fatal error: Allowed memory size of 134.217.728 bytes exhausted (tried to allocate 261904 bytes) in C:\inetpub\wwwroot\lib\MPDF\mpdf.php on line 22048
 * @endcode
 * 
 * occorre approntare alcuni accorgimenti elencanti nella seguente pagina di <a href="http://mpdf1.com/manual/index.php?tid=408&searchstring=memory%20size">documentazione mpdf</a>.
 * 
 * L'aumento di memoria allo script php può essere gestito a livello di: \n
 *   - file php.ini
 *   @code
 *   memory_limit = 128M
 *   @endcode
 *   - file php
 *   @code
 *   ini_set("memory_limit","128M")
 *   @endcode
 *   - virtualhost
 *   @code
 *   php_admin_value memory_limit "128M"
 *   @endcode
 * 
 * Limpostazione massima del limite di memoria per lo script php è
 * @code
 * ini_set("memory_limit","-1")
 * @endcode
 * 
 * ####Windows
 * La memoria può esaurirsi rapidamente durante l'esecuzione di PHP 5.3.x su Windows, e questo potrebbe essere dovuto da un bug nella versione di php per Windows. 
 * Uno script che esaurisce 256 MB di memoria su Windows può invece utilizzare solo 18MB quando viene eseguito su Linux. E sembra che non accada esclusivamente quando si utilizzano tabelle. \n
 * Quindi, se si utilizza solo Windows in un ambiente di prova e Linux per la produzione, si dovrebbe considerare di impostare il limite di memoria massimo su Windows.
 * 
 * ###Errori PHP
 * Un qualsiasi errore generato dallo script php (anche se soltanto un warning o un notice), blocca la generazione del pdf. 
 * In questo caso occorre inibire la stampa degli errori richiamando direttamente la funzione php:
 * @code
 * error_reporting(0);
 * @endcode
 * 
 * oppure
 * @code
 * plugin_mpdf::setPhpParams(array('disable_error'=>false));
 * @endcode
 * 
 * ###PROGRESS BAR
 * La progress bar non è raccomandata per un utilizzo generale ma può essere di aiuto in fase di sviluppo o nella generazione di documenti lenti. \n
 * Per impostare il valore a livello globale occorre editare il valore per @a progressBar nel file di configurazione config.php.
 * 
 * ####Personalizzazione
 * La pagina della progress bar può essere personalizzata attraverso la definizione dell'opzione @a progbar_altHTML nel metodo plugin_mpdf::makeFile(). 
 * Ad esempio
 * @code
 * $mpdf->progbar_altHTML = '<html><body>
 * <div style="margin-top: 5em; text-align: center; font-family: Verdana; font-size: 12px;">
 * <img style="vertical-align: middle" src="img/loading.gif" /> Creating PDF file. Please wait...</div>'
 * @endcode
 * 
 * Inoltre è possibile sovrascrivere direttamente il metodo che genera la pagina della progress bar, ad esempio per personalizzarne la lingua o le diciture. \n
 * In questo caso occorre modificare il metodo custom_mpdf::StartProgressBarOutput().
 * 
 * ####Attivazione
 * Per attivare la barra di progresso nella generazione inline di un PDF occorre assegnare i permessi 777 alla directory MPDF/tmp/, 
 * in quanto la libreria salva un file temporaneo in questa directory e poi lo mostra a video attraverso il file MPDF/includes/out.php.
 * 
 * GESTIONE DEI CONTENUTI
 * ---------------
 * 
 * ###GESTIONE DELLE STRINGHE
 * Le stringhe di testo sono gestite dal metodo text() che richiama le funzioni presenti nel file func.mpdf.php. 
 * Le tipologie trattate sono: \n
 *   - @a text, richiama la funzione @a pdfChars() (default)
 *   - @a textarea, richiama la funzione @a pdfChars_Textarea()
 *   - @a editor, richiama la funzione @a pdfTextChars()
 * 
 * Nel caso in cui i dati in arrivo dal database non vengano gestiti attraverso l'interfaccia di gestione delle stringhe text(), 
 * la funzione php htmlentities() presente nelle funzioni del file func.mpdf.php (anche pdfHtmlToEntities()) 
 * potrebbe determinare la creazione di un file pdf costituito unicamente da una pagina bianca.
 * 
 * La classe gino_mpdf mette a disposizione il metodo mText() per interfacciarsi a plugin_mpdf::text().
 * 
 * ###BREAKPAGE
 * Occorre fare attenzione al posizionamento dei breakpage, in quanto un breakpage a fine html genera una pagina vuota.
 * 
 * La definizione dei contenuti di un pdf a partire da un array di singoli contenuti html avviene unendo questi singoli contenuti che saranno tra loro separati. 
 * In questo caso non posizionare i breakpage a fine html in quanto i singoli contenuti html vengono sempre mostrati a partire da una nuova pagina.
 * 
 * PERMESSI DEL FILE PDF
 * ---------------
 * L'opzione @a protection (array) permette di crittografare e impostare i permessi sul file pdf. Di default il documento non è crittografato e garantisce tutte le autorizzazioni all'utente (valore null di @a protection). Al contrario un array vuoto nega ogni autorizzazioni all'utente. \n
 * L'array può includere alcuni, tutti o nessuno dei seguenti valori che indicano i permessi concessi (@see http://mpdf1.com/manual/index.php?tid=129&searchstring=setprotection):
 *     - @a copy
 *     - @a print
 *     - @a modify
 *     - @a annot-forms
 *     - @a fill-forms
 *     - @a extract
 *     - @a assemble
 *     - @a print-highres
 * 
 * Le password dell'utente e del proprietario vengono passate attraverso le opzioni @a user_password e @a owner_password.
 * 
 * GESTIONE FILE CSS
 * ---------------
 * I file css possono essere caricati come opzione del metodo definePage() in due modi, come stringa o come array. 
 * Sarà poi il metodo htlmStart() a prendersi carico della corretta inclusione dei file css nel codice html dal quale verrà generato il file pdf. \n
 * 
 * 1) stringa: in questo caso viene incluso nel codice html soltanto il file css indicato, ad esempio
 * @code
 * array([...,] 'css_file'=>'app/test/css/report.css'[, ...])
 * @endcode
 * 
 * Eventuali altri file dovranno essere inclusi utilizzando la chiave \@import nel file css
 * @code
 * @import url(test.css);
 * .void {}
 * .title { color: red; }
 * @endcode
 * In questo caso ho riscontrato che la prima classe css dopo la direttiva \@import non viene presa in considerazione, per cui è necessario inserire una classe "finta", 
 * come 'void' nell'esempio appena sopra.
 * 
 * 2) array: in questo caso vengono inclusi nel codice html tutti i file css indicati, nell'ordine degli elementi nell'array, ad esempio
 * @code
 * array([...,] 'css_file'=>array('app/dvr/css/report.css', 'app/dvr/css/test.css')[, ...])
 * @endcode
 * 
 * CSS/STILI
 * ---------------
 * ###Posizionamento
 * Gli elementi DIV possono essere posizionati staticamente nella pagina, a condizione che abbiamo come parent direttamente il BODY, 
 * ovvero che non siano all'interno di una SECTION o di un altro DIV.
 * 
 * Nel caso di <pre>position:absolute;</pre> il blocco prende come riferimento la pagina senza tenere in considerazione i margini, 
 * mentre nel caso <pre>position:relative;</pre> il blocco prende come riferimento i margini della pagina. \n
 * Seguono due esempi: nel primo caso il blocco viene posizionato al vivo in basso nella pagina, 
 * mentre nel secondo caso il blocco viene posizionato a una distanza di 30mm dal basso e dentro i margini della pagina.
 * 
 * @code
 * .myfixed1 {
 *   position: absolute;
 *   overflow: visible;
 *   left: 0;
 *   bottom: 0;
 *   border: 1px solid #880000;
 *   background-color: #FFEEDD;
 *   background-gradient: linear #dec7cd #fff0f2 0 1 0 0.5;
 *   padding: 1.5em;
 *   margin: 0;
 *   font-family:sans;
 * }
 * 
 * .myfixed2 {
 *   position: fixed;
 *   overflow: auto;
 *   left: 120mm;
 *   right: 0;
 *   bottom: 0mm;
 *   height: 30mm;
 *   border: 1px solid #880000;
 *   background-color: #FFEEDD;
 *   background-gradient: linear #dec7cd #fff0f2 0 1 0 0.5;
 *   padding: 0.5em;
 *   margin: 0;
 *   font-family:sans;
 * }
 * @endcode
 * 
 * ###Rotazione del testo
 * Sull'intera riga di una tabella (tag tr) oppure su singole celle (tag td).
 * @code
 * <tr text-rotate="45">
 * oppure
 * <tr style="text-rotate: 45">
 * @endcode
 * 
 * ###Tabelle
 * Block-level tags (DIV, P etc) are ignored inside tables, including any CSS styles - inline CSS or stylesheet classes, id etc. \n
 * To set text characteristics within a table/cell, either define the CSS for the table/cell, or use in-line tags e.g. <SPAN style="...">.
 * 
 * ####Ripetizione di header e/o footer di una tabella al cambio di pagina
 * Se una tabella è splittata su più pagine, la prima riga di una tabella sarà ripetuta in testa alla nuova pagina se:
 * @code
 * <table repeat_header="1"> o
 * <thead> o <tfoot> sono definiti
 * @endcode
 * 
 * ####Inserimento di un bordo all'inizio e alla fine di una tabella
 * @code
 * .table_style {
 *   topntail: 0.02cm solid #666;
 * }
 * @endcode
 * 
 * ####Esempi di celle di tabella
 * @code
 * <td colspan="2" valign="top" align="center">text_label:<br />text_value</td>
 * <td width="50%" valign="top" rowspan="2">text_label:<br />text_value</td>
 * @endcode
 * 
 * ###Cambiare le dimensioni della pagina nel documento
 * L'esempio seguente stampa una pagina A4 (landscape).
 * 
 * Come css
 * @code
 * .headerPagedStart { page: smallsquare; }
 * .headerPagedEnd { page: standard; }
 * @page smallsquare {
 *   sheet-size: A4-L; // width height <length>{2} | Letter | Legal | Executive | A4 | A4-L | A3 | A3-L etc. Any of the standard sheet sizes can be used with the suffix '-L' for landscape
 *   size: 15cm 20cm; // width height <length>{1,2} | auto | portrait | landscape NB 'em' and 'ex' % are not allowed
 *   margin: 5%;
 *   margin-header: 5mm;
 *   margin-footer: 5mm;
 * }
 * @page standard {
 *   sheet-size: A4; margin: 15mm; margin-header: 5mm; margin-footer: 5mm;
 * }
 * @endcode
 * 
 * Nel codice html
 * @code
 * <h2 class="headerPagedStart">Paged Media using CSS</h2>
 * <h4>Changing page (sheet) sizes within the document</h4> <p>This should print on an A4 (landscape) sheet</p> <p>Nulla felis erat, imperdiet eu, ..........</p>
 * <div class="headerPagedEnd"></div>
 * @endcode
 */

/**
 * @file plugin.mpdf.php
 * @brief Contiene le classi gino_mpdf, custom_mpdf, plugin_mpdf
 * 
 * @copyright 2013-2015 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author marco guidotti guidottim@gmail.com
 * @author abidibo abidibo@gmail.com
 */

/**
 * @namespace Gino.Plugin
 * @description Namespace che comprende classi di tipo plugin
 */
namespace Gino\Plugin;

// Percorso relativo alla directory principale della libreria mPDF
define('_MPDF_URI', 'lib/mpdf/');

require_once(LIB_DIR.OS."mpdf".OS."mpdf.php");
require_once(LIB_DIR.OS."func.mpdf.php");

/**
 * @brief Classe che funge da interfaccia alla classe plugin_mpdf
 * 
 * @copyright 2014-2015 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author marco guidotti guidottim@gmail.com
 * @author abidibo abidibo@gmail.com
 * 
 * I metodi header(), footer() e content() contengono i dati del pdf e vengono sovrascritti dalla child class. \n
 * I defaultHeader() e defaultFooter() contengono l'header e il footer di default.
 * 
 * I dati in arrivo dal database devono essere gestiti attraverso l'interfaccia di gestione delle stringhe gino_mpdf::mText().
 */
class gino_mpdf {
	
	protected $_registry;
	
	/**
	 * Indica se mostrare l'html
	 * 
	 * @var boolean
	 */
	protected $_html;
	
	/**
	 * Oggetto pdf
	 * 
	 * @var object
	 */
	protected $_pdf;
	
	/**
	 * Costruttore
	 * 
	 * @param integer $ute
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b html (boolean): indica se mostrare l'html o creare il file pdf
	 * @return void
	 * 
	 * Se si mostra l'html (html true) la pagina carica lo stesso il file di stile specificato nell'opzione @a css_file del metodo create().
	 */
	function __construct($options=array()) {
		
		$this->_html = \Gino\gOpt('html', $options, false);
		
		$this->_registry = \Gino\registry::instance();
		$this->_pdf = null;
	}
	
	/**
	 * Definisce le impostazioni base per la libreria mPDF
	 * 
	 * @return array
	 */
	public static function defineBasicOptions() {
		
		$options = array(
			'debug'=>false, 
			'css_file' => array('css/mpdf.css'), 
			'title' => 'Pdf document', 
			'author' => 'Otto Srl', 
			'creator' => 'Marco Guidotti', 
			'landscape'=>false, 
			'top-margin'=>20, 
			'bottom-margin'=>30, 
			'progressBar'=>false, 
			'progbar_heading'=>"Generazione pdf - Stato di avanzamento", 
		);
		
		return $options;
	}
	
	/**
	 * Header del file pdf
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - img_dir (string): percorso alle immagini
	 * @return string
	 */
	protected function header($options=array()) {
		
		return null;
	}
	
	/**
	 * Footer del file pdf
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - img_dir (string): percorso alle immagini
	 * @return string
	 */
	protected function footer($options=array()) {
		
		return null;
	}
	
	/**
	 * Definizione dei contenuti di un pdf
	 * 
	 * @param array $options array associativo di opzioni per la generazione del pdf (@see create())
	 * @return string or array
	 */
	protected function content($options=array()) {
		
		return null;
	}
	
	/**
	 * Header base
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b text_left (string): testo da mostrare nella parte sinistra dell'intestazione
	 *   - @b text_right (string): testo da mostrare nella parte destra dell'intestazione
	 *   - @b title (string): titolo da mostrare sotto l'intestazione
	 * @return string
	 */
	protected function defaultHeader($options=array()) {
		
		$text_left = \Gino\gOpt('text_left', $options, null);
		$text_right = \Gino\gOpt('text_right', $options, null);
		$title = \Gino\gOpt('title', $options, null);
		
		$header = "<table width=\"100%\"><tr>
		<td style=\"font-size: 8pt; text-align:left;\">".$this->mText($text_left)."</td>
		<td style=\"font-size: 8pt; text-align:right;\">".$this->mText($text_right)."</td>
		</tr></table>";
		
		if($title) $header .= "<div class=\"title_header\">$title</div>";
		
		return $header;
	}
	
	/**
	 * Footer base
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b text1 (string): testo da mostrare nella parte sinistra del piè di pagina
	 *   - @b text2 (string): testo da mostrare al centro del piè di pagina
	 * @return string
	 */
	protected function defaultFooter($options=array()) {
		
		$text1 = \Gino\gOpt('text1', $options, null);
		$text2 = \Gino\gOpt('text2', $options, null);
		
		$footer = "<div style=\"border-top:1px solid #666; padding-top:3mm;\">";
		$footer .= "<table width=\"100%\"><tr>";
		
		$width_sx = $text2 ? 20 : 80;
		
		$footer .= "<td width=\"".$width_sx."%\" style=\"text-align:left; font-size:6pt;\">$text1</td>";
		if($text2) $footer .= "<td width=\"60%\" style=\"text-align:center; font-size:6pt;\">$text2</td>";
		
		$footer .= "<td width=\"20%\" style=\"text-align:right; font-size:6pt;\">"._("Pagina")." _NUMPAGE_ "._("di")." _TOTPAGE_</td>";
		$footer .= "</tr></table>";
		$footer .= "</div>";
		
		return $footer;
	}
	
	/**
	 * Imposta il nome del file pdf
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b name (string): nome base del file
	 *   - @b date (boolean): indica se mostrare la data di creazione del file (default false)
	 * @return string
	 */
	protected function setFileName($options=array()) {
		
		$name = \Gino\gOpt('name', $options, 'doc');
		$date = \Gino\gOpt('date', $options, false);
		
		if($date)
		{
			$date = date("Ymd");
			$name .= '-'.$date;
		}
		$name .= '.pdf';
		
		return $name;
	}
	
	/**
	 * Pagina di copertina
	 * 
	 * @param string $title titolo della copertina
	 * @return string
	 */
	protected function frontpage($title) {
		
		$buffer = "<section>";
		$buffer .= "<div class=\"cover\">".$this->mText($title)."</div>";
		$buffer .= "</section>";
		
		return $buffer;
	}
	
	/**
	 * Gestisce la ripetizione di una stringa (uno o più caratteri)
	 * 
	 * @param string $string carattere/i da ripetere
	 * @param integer $num numero di ripetizioni
	 * @param integer $break numero di caratteri dopo i quali inserire un tag br
	 * @return string
	 */
	protected function repeatChar($string, $num, $break=null) {
		
		$buffer = '';
		if($num)
		{
			$count = 1;
			for($i=1; $i<=$num; $i++)
			{
				$buffer .= $string;
				
				if($count == $break)
				{
					$buffer .= "<br />";
					$count = 1;
				}
				else $count++;
			}
		}
		else $buffer = $string;
		
		return $buffer;
	}
	
	/**
	 * Genera il pdf di una pagina html
	 * 
	 * @see Gino.Plugin.gino_mpdf::defineBasicOptions()
	 * @see Gino.Plugin.gino_mpdf::create()
	 * @param string $content contenuto della risposta
	 * @param array $opts
	 *   array associativo di opzioni
	 *   - @b link_return (string): indirizzo di reindirizzamento dopo la creazione del file pdf
	 *   opzioni di gino_mpdf::create()
	 *   - @b output (string): tipo di output (default inline)
	 *   - @b filename (string): nome del file
	 *   - @b img_dir (string): percorso della directory delle immagini per header e footer (es. app/blog/img)
	 *   - @b save_dir (string): percorso della directory di salvataggio dei file (es. $this->getBaseAbsPath().'/pdf')
	 *   - @b css_html (string): file css per l'html (es. app/blog/blog_blog.css)
	 *   - @b content (string): contenuto del file
	 *   opzione del costruttore della classe plugin_mpdf
	 *   - @b output (string): tipo di output (default inline)
	 *   - @b debug (boolean)
	 *   opzioni di plugin_mpdf::makeFile()
	 *   opzioni di plugin_mpdf::definePage()
	 *   - @b css_file (mixed): file css per per il pdf (es. array('app/blog/pdf.css', 'css/mpdf.css'))
	 *   - @b header
	 *   - @b footer
	 *   - @b debug_exit
	 * @return mixed (void or string)
	 */
	public function pdfFromPage($content, $opts=array()) {
		
		$link_return = \Gino\gOpt('link_return', $opts, null);
		$output = \Gino\gOpt('output', $opts, 'inline');
		$css_file = \Gino\gOpt('css_file', $opts, null);
		$css_html = \Gino\gOpt('css_html', $opts, null);
		$img_dir = \Gino\gOpt('img_dir', $opts, null);
		$save_dir = \Gino\gOpt('save_dir', $opts, null);
		$filename = \Gino\gOpt('filename', $opts, null);
		
		$options = gino_mpdf::defineBasicOptions();
        
        $options['output'] = $output;
        if($css_file) $options['css_file'] = $css_file;
        if($css_html) $options['css_html'] = $css_html;
        if($img_dir) $options['img_dir'] = $img_dir;
        if($save_dir) $options['save_dir'] = $save_dir;
        if($filename) $options['filename'] = $filename;
        
		$options['content'] = \Gino\htmlToPdf($content);
		
		$pdf = $this->create($options);
		
		if($this->_html)
			return $pdf;
		
		if($link_return)
		{
			$this->redirect($link_return);
		}
		return null;
	}
	
	/**
	 * Costruisce il file
	 * 
	 * @see Gino.Plugin.plugin_mpdf::definePage()
	 * @see Gino.Plugin.plugin_mpdf::makeFile()
	 * @see header()
	 * @see footer()
	 * @see content()
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b output (string): tipologia di output del pdf (@see plugin_mpdf::outputs())
	 *     - @a file
	 *     - @a inline
	 *     - @a download
	 *     - @a string
	 *   - @b debug (boolean): attiva il debug (default false)
	 *   - @b filename (string): nome del file (default doc.pdf)
	 *   - @b img_dir (string): percorso alle immagini nel pdf
	 *   - @b save_dir (string): percorso alla directory di salvataggio del file
	 *   - @b css_file (mixed): percorso ai file css inclusi nel pdf (caricati in @see plugin_mpdf::definePage())
	 *   - @b css_html (string): percorso al file css incluso nel formato html (ad esempio 'app/news/css/web.css')
	 *   - @b content (string): contenuto del file
	 *   opzioni specifiche del metodo plugin_mpdf::makeFile():
	 *   - @b title (string)
	 *   - @b author (string)
	 *   - @b creator (string)
	 *   - @b format (string)
	 *   - @b landscape (boolean)
	 *   - @b protection (array)
	 *   - @b user_password (string)
	 *   - @b owner_password (string)
	 *   - @b title (string)
	 *   - @b watermark (boolean)
	 *   - @b watermark_text (string)
	 *   - @b top-margin (integer)
	 *   - @b bottom-margin (integer)
	 *   - @b header-margin (integer)
	 *   - @b footer-margin (integer)
	 *   - @b simpleTables (boolean)
	 *   - @b showStats (boolean)
	 *   - @b progressBar (mixed)
	 *   - @b progbar_heading (string)
	 *   - @b progbar_altHTML (string)
	 *   opzioni specifiche del metodo plugin_mpdf::definePage():
	 *   - @b header (string)
	 *   - @b footer (string)
	 *   - @b debug_exit (boolean)
	 * @return mixed
	 *   - string, html and output string
	 *   - boolean true, output File
	 *   - exit, output inline and download
	 */
	public function create($options=array()) {
		
		$output = array_key_exists('output', $options) ? $options['output'] : null;
		$debug = array_key_exists('debug', $options) ? $options['debug'] : false;
		
		$filename = \Gino\gOpt('filename', $options, 'doc.pdf');
		$img_dir = \Gino\gOpt('img_dir', $options, null);
		$save_dir = \Gino\gOpt('save_dir', $options, '');
		$css_html = \Gino\gOpt('css_html', $options, null);
		
		$save_dir = (substr($save_dir, -1) != '/' && $save_dir != '') ? $save_dir.'/' : $save_dir;
		
		$pdf = new plugin_mpdf(
			array(
				'output'=>$output, 
				'debug'=>$debug
			)
		);
		
		$this->_pdf = $pdf;
		
		$content = \Gino\gOpt('content', $options, null);
		if(!$content) $content = $this->content($options);
		
		// HTML
		if($this->_html)
		{
			if(is_array($content))
				$content = implode("<br />", $content);
			
			if($css_html)
				$this->_registry->addCss($css_html);
			
			return $content;
		}
		// End
		
		if(is_array($content))
		{
			$html = $content;
		}
		else
		{
			$options['header'] = $this->header(array('img_dir'=>$img_dir));
			$options['footer'] = $this->footer(array('img_dir'=>$img_dir));
			
			$html = $pdf->definePage($content, $options);
		}
		
		if($output == 'file')
		{
			if(!is_dir($save_dir)) mkdir($save_dir, 0777, true);
			$file = $save_dir.$filename;
		}
		else $file = $filename;
		
		$res = $pdf->makeFile($html, $file, $options);
		
		return $res;
	}
	
	/**
	 * Redirige il processo di creazione del file all'indirizzo specificato
	 * 
	 * Si utilizza il javascript perché la funzione header() ritorna l'errore: \n
	 * Warning: Cannot modify header information - headers already sent in ...
	 * 
	 * @param string $link
	 */
	protected function redirect($link) {
		
		echo "<script type=\"text/javascript\">window.location.href='".$link."';</script>";
		exit();
	}
	
	/**
	 * Interfaccia di gestione delle stringhe
	 * 
	 * Se esiste l'oggetto pdf, le stringhe vengono passate al metodo plugin_mpdf::text().
	 * 
	 * @see plugin_mpdf::text()
	 * @param string $string testo da gestire
	 * @param array $options array associativo di opzioni del metodo plugin_mpdf::text()
	 * @return string
	 */
	protected function mText($string, $options=array()) {
		
		if($this->_html)
		{
			$type = \Gino\gOpt('type', $options, 'text');
			
			if($type == 'textarea')
				return \Gino\htmlCharsText($string);
			elseif($type == 'editor')
				return \Gino\htmlChars($string);
			else
				return \Gino\htmlChars($string);
		}
		else return $this->_pdf->text($string, $options);
	}
	
	/**
	 * Interfaccia al metodo di generazione di un testo html compatibile con il pdf
	 * 
	 * @see plugin_mpdf::htmlCreate()
	 * @param string $html
	 * @param boolean $exit
	 * @return string
	 * 
	 * Da utilizzare per la gestione di pagine con impostazioni personalizzate.
	 */
	protected function convertHtmlToPdf($html, $exit=true) {
		
		if($this->_html)
		{	
			return $html;
		}
		else
		{
			return $this->_pdf->htmlCreate($html, $exit);
		}
	}
	
	/**
	 * Interfaccia al metodo di break page
	 * 
	 * @see plugin_mpdf::breakPage()
	 * @return string
	 */
	protected function breakpage() {
		
		if(is_object($this->_pdf) && !$this->_html)
		{
			return $this->_pdf->breakpage();
		}
		else return '';
	}
	
	/**
	 * Stampa una tabella composta da n elementi
	 * 
	 * @param array $data elementi della tabella, ogni elemento è una riga; ad esempio array(array($record1_field1, $record1_field2), array($record2_field1, $record2_field2))
	 * @param array $header intestazioni della tabella, ad esempio:
	 * @code
	 * array("<td width=\"5%\">"._("ID")."</td>", "<td width=\"10%\">"._("Quantità")."</td>")
	 * @endcode
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b class (string): nome della classe css
	 *   - @b style (string): definizione degli stili css (proprietà style)
	 *   - @b autosize (integer): fattore massimo di restringimento consentito per una singola tabella (con [autosize=1] la tabella non viene ridimensionata)
	 *   - @b border (integer): valore della proprietà border
	 * @return string
	 */
	protected function printTable($data=array(), $header=array(), $options=array()){
		
		$class = array_key_exists('class', $options) ? $options['class'] : '';
		$style = array_key_exists('style', $options) ? $options['style'] : '';
		$autosize = array_key_exists('autosize', $options) ? $options['autosize'] : 1;
		$border = array_key_exists('border', $options) ? $options['border'] : 0;
		
		if($class) $class = " class=\"$class\"";
		if($style) $style = " style=\"$style\"";
		if($autosize) $autosize = " autosize=\"$autosize\"";
		if($border) $border = " border=\"$border\"";
		
		$buffer = "<table".$autosize.$border.$style.$class.">";
		$buffer .= "<thead>";
		$buffer .= "<tr>";
		if(sizeof($header) > 0)
		{
			foreach($header AS $value)
			{
				$buffer .= $value;
			}
		}
		$buffer .= "</tr>";
		$buffer .= "</thead>";
		
		$buffer .= "<tbody>";
		if(sizeof($data) > 0)
		{
			foreach($data AS $record)
			{
				$buffer .= "<tr>";
				if(sizeof($record) > 0)
				{
					foreach($record AS $field)
					{
						$buffer .= "<td valign=\"top\">".$field."</td>";
					}
				}
				$buffer .= "</tr>";
			}
		}
		$buffer .= "</tbody>";
		$buffer .= "</table>";
		
		return $buffer;
	}
	
	/**
	 * Tabella che dispone gli elementi su 2/3 colonne a partire da sinistra e dall'alto verso il basso 
	 * 
	 * @see parseFieldForArrangeTable()
	 * @param array $items array contente gli oggetti degli elementi della tabella
	 * @param array $selected array contenente i valori id degli elementi selezionati
	 * @param array $options
	 *   - @b cols (integer): numero di colonne (default 2, massimo 3)
	 *   - @b field (mixed):
	 *     - @a string, nome del campo degli oggetti $items da mostrare
	 *     - @a array, elenco dei campi degli oggetti $items da mostrare
	 *   - @b separator (string): separatore dei valori dei campi esplicitati nell'opzione @a field (nel caso di array)
	 *   - @b table_class (string): nome della classe del tag table
	 *   - @b td_class (string): nome della classe del tag td
	 *   - @b add_rows (string): righe da aggiungere in fondo alla tabella
	 * @return string
	 */
	protected function arrangeTable($items, $selected, $options=array()) {
		
		$cols = \Gino\gOpt('cols', $options, 2);
		$field = \Gino\gOpt('field', $options, null);
		$separator = \Gino\gOpt('separator', $options, null);
		$table_class = \Gino\gOpt('table_class', $options, null);
		$td_class = \Gino\gOpt('td_class', $options, null);
		$add_rows = \Gino\gOpt('add_rows', $options, null);
		
		$table_class = $table_class ? " class=\"$table_class\"" : '';
		$td_class = $td_class ? " class=\"$td_class\"" : '';
		
		$buffer = '';
		
		if(count($items))
		{
			$items_for_col = ceil(count($items)/$cols);
			
			$i = 1;
			$col1 = $col2 = $col3 = array();
			foreach($items AS $item)
			{
				if($cols == 3)
				{
					if($i <= $items_for_col)
						$col1[] = $item;
					elseif($i <= $items_for_col*2 && $i > $items_for_col)
						$col2[] = $item;
					elseif($i > $items_for_col*2)
						$col3[] = $item;
				}
				elseif($cols == 2)
				{
					if($i <= $items_for_col)
						$col1[] = $item;
					elseif($i > $items_for_col)
						$col2[] = $item;
				}
				
				$i++;
			}
			
			$buffer .= "<table".$table_class.">";
			
			for($i=0, $end=$items_for_col; $i<=$end; $i++)
			{
				$buffer .= "<tr>";
				
				if(isset($col1[$i]))
				{
					$i_col1 = $col1[$i];
					$checked = in_array($i_col1->id, $selected) ? "checked=\"checked\"" : '';
					
					$text = $this->parseFieldForArrangeTable($i_col1, $field, array('separator'=>$separator));
					$buffer .= "<td".$td_class."><input type=\"checkbox\" $checked /> ".$this->mText($text)."</td>";
				}
				else
				{
					$buffer .= "<td".$td_class."></td>";
				}
				
				if(isset($col2[$i]))
				{
					$i_col2 = $col2[$i];
					$checked = in_array($i_col2->id, $selected) ? "checked=\"checked\"" : '';
					
					$text = $this->parseFieldForArrangeTable($i_col2, $field, array('separator'=>$separator));
					$buffer .= "<td".$td_class."><input type=\"checkbox\" $checked /> ".$this->mText($text)."</td>";
				}
				else
				{
					$buffer .= "<td".$td_class."></td>";
				}
				
				if(isset($col3[$i]))
				{
					$i_col3 = $col3[$i];
					$checked = in_array($i_col3->id, $selected) ? "checked=\"checked\"" : '';
					
					$text = $this->parseFieldForArrangeTable($i_col3, $field, array('separator'=>$separator));
					$buffer .= "<td".$td_class."><input type=\"checkbox\" $checked /> ".$this->mText($text)."</td>";
				}
				else
				{
					$buffer .= "<td".$td_class."></td>";
				}
				$buffer .= "</tr>";
			}
			if($add_rows) $buffer .= $add_rows;
			
			$buffer .= "</table>";
		}
		
		return $buffer;
	}
	
	/**
	 * Gestisce i valori delle celle del metodo arrangeTable()
	 * 
	 * @param object $obj oggetto dal quale recuperare i valori dei campi indicati nel parametro @a field
	 * @param mixed $field
	 *   - @a string, nome del campo
	 *   - @a array, elenco dei nomi dei campi
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b separator (string): separatore dei valori dei campi esplicitati nel parametro @a field (nel caso di array)
	 * @return string
	 */
	private function parseFieldForArrangeTable($obj, $field, $options=array()) {
		
		$separator = \Gino\gOpt('separator', $options, ' ');
		
		if(is_string($field))
		{
			return $obj->$field;
		}
		elseif(is_array($field))
		{
			$text = '';
			$i = 1;
			$end = count($field);
			
			foreach($field AS $f)
			{
				$text .= $obj->$f;
				
				if($i < $end) $text .= $separator;
				
				$i++;
			}
			return $text;
		}
		else return null;
	}
}

/**
 * @brief Classe che estende mPDF per la personalizzazione degli output html
 * 
 * @copyright 2013-2015 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author marco guidotti guidottim@gmail.com
 * @author abidibo abidibo@gmail.com
 */
class custom_mpdf extends \mPDF {
	
	/**
	 * @see mPDF::StartProgressBarOutput()
	 */
	function StartProgressBarOutput($mode=1) {
		// must be relative path, or URI (not a file system path)
		if (!defined('_MPDF_URI')) { 
			$this->progressBar = false;
			if ($this->debug) { $this->Error("You need to define _MPDF_URI to use the progress bar!"); }
			else return false; 
		}
		$this->progressBar = $mode;
		if ($this->progbar_altHTML) {
			echo $this->progbar_altHTML;
		}
		else {
			echo '<html>
			<head>
				<title>mPDF File Progress</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<link rel="stylesheet" type="text/css" href="'._MPDF_URI.'progbar.css" />
			</head>
			<body>
				<div class="main">
				<div class="heading">'.$this->progbar_heading.'</div>
				<div class="demo">';
			if ($this->progressBar==2) {
				echo '<table width="100%"><tr><td style="width: 50%;"> 
				<span class="barheading">Writing HTML code</span> <br/>

			<div class="progressBar">
			<div id="element1"  class="innerBar">&nbsp;</div>
			</div>
			<span class="code" id="box1"></span>
			</td><td style="width: 50%;">
			<span class="barheading">Autosizing elements</span> <br/>
			<div class="progressBar">
			<div id="element4"  class="innerBar">&nbsp;</div>
			</div>
			<span class="code" id="box4"></span>
			<br/><br/>
			<span class="barheading">Writing Tables</span> <br/>
			<div class="progressBar">
			<div id="element7"  class="innerBar">&nbsp;</div>
			</div>
			<span class="code" id="box7"></span>
			</td></tr>
			<tr><td><br /><br /></td><td></td></tr>
			<tr><td style="width: 50%;"> 
			';
			}
			echo '<span class="barheading">Writing PDF file</span> <br/>
			<div class="progressBar">
			<div id="element2"  class="innerBar">&nbsp;</div>
			</div>
			<span class="code" id="box2"></span>';
			
			if ($this->progressBar==2) {
				echo '
			</td><td style="width: 50%;">
			<span class="barheading">Memory usage</span> <br/>
			<div class="progressBar">
			<div id="element5"  class="innerBar">&nbsp;</div>
			</div>
			<span id="box5">0</span> '.ini_get("memory_limit").'<br />
			<br/><br/>
			<span class="barheading">Memory usage (peak)</span> <br/>
			<div class="progressBar">
			<div id="element6"  class="innerBar">&nbsp;</div>
			</div>
			<span id="box6">0</span> '.ini_get("memory_limit").'<br />
			</td></tr>
			</table>
			'; }
			echo '<br/><br/>
			<span id="box3"></span>
			</div>';
		}
		ob_flush();
		flush();
	}
}

/**
 * @brief Classe per la generazione di file pdf
 * 
 * @copyright 2013-2015 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
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
	 * Costruttore
	 * 
	 * @param array	options
	 *   array associativo di opzioni
	 *   - @b output (string): tipo di output del file pdf; deve essere conforme a quelli presenti nel metodo mpdf::outputs()
	 *     - @a inline: send to standard output; invia il file inline al browser (default)
	 *     - @a download: download file
	 *     - @a file: salva localmente il file; indicare il percorso assoluto
	 *     - @a string: ritorna una stringa
	 *   - @b debug (boolean): stampa a video il buffer (default false)
	 * @return void
	 */
	function __construct($options=array()){
		
		if(array_key_exists('output', $options) && $options['output'])
		{
			$res = array_keys(self::outputs(), $options['output']);
			
			if($res && count($res))
				$this->_output = $res[0];
			else
				$this->_output = 'I';
		}
		else $this->_output = 'I';
		
		$this->_debug = array_key_exists('debug', $options) && $options['debug'] ? $options['debug'] : false;
	}
	
	/**
	 * Tipologie di output del pdf
	 * 
	 * @return array
	 */
	public static function outputs() {
		
		return array('F'=>'file', 'I'=>'inline', 'D'=>'download', 'S'=>'string');
	}
	
	/**
	 * Imposta dei parametri di configurazione in uno script php
	 * 
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b disable_error (boolean): spegne tutte le segnalazioni d'errore
	 *   - @b memory_usage (boolean): ritorna l'ammontare di memoria allocata da php (in byte); 
	 *   si tratta della quantità di memoria utilizzata non appena viene eseguito lo script o delle singole istruzioni
	 *   - @b memory_peak_usage (boolean): ritorna il picco di memoria allocata da php (in byte)
	 * @return null
	 * 
	 * @a memory_get_usage e @a memory_get_peak_usage prevedono l'opzione @a real_usage: \n
	 *   - true, ritorna la reale dimensione della memorai allocata dal sistema
	 *   - false (o non impostata), soltanto la memoria usata da emalloc()
	 * 
	 * Richiamando memory_get_peak_usage alla fine dello script si riuscirà a ricavare la più alta allocazione registrata durante l'esecuzione. \n
	 * Probabilmente è molto più utile questo valore che ottenere i valori di inizio e fine dello script 
	 * in quanto in questo modo non si tiene conto della memoria allocata e poi deallocata durante il runtime.
	 */
	public static function setPhpParams($options=array()) {
		
		$disable_error = \Gino\gOpt('disable_error', $options, false);
		$memory_usage = \Gino\gOpt('memory_usage', $options, false);
		$memory_peak_usage = \Gino\gOpt('memory_peak_usage', $options, false);
		
		if($disable_error)
		{
			error_reporting(0);
		}
		
		if($memory_usage)
		{
			echo convertSize(memory_get_usage(true))."<br />";
		}
		
		if($memory_peak_usage)
		{
			echo convertSize(memory_get_peak_usage(true))."<br />";
		}
		
		return null;
	}
	
	/**
	 * Estrapola il nome del file pdf
	 * 
	 * @param string $filename
	 * @return string
	 */
	private function conformFile($filename='') {
		
		if($filename)
		{
			$dirname = dirname($filename);
			if(!is_dir($dirname))
			{
				$filename = basename($filename);
			}
		}
		else $filename = '';
		
		return $filename;
	}
	
	/**
	 * Imposta l'header e il footer
	 * 
	 * @param array options
	 *   - @b css_file (mixed):
	 *     - string, percorso al file css (default css/mpdf.css)
	 *     - array, elenco dei file css da caricare
	 *   - @b css_style (string): stili css personalizzati (in un tag style)
	 *   - @b header (string): header personalizzato
	 *   - @b footer (mixed):
	 *     - boolean, col valore @a false il footer non viene mostrato
	 *     - string, footer personalizzato, sono implementate le stringhe sostitutive:
	 *       - @a _NUMPAGE_, numero di pagina
	 *       - @a _TOTPAGE_, numero totale di pagine
	 *     - in tutti gli altri casi viene mostrato il footer standard
	 *  @return string
	 */
	public function htmlStart($options=array()){
		
		$css_file = array_key_exists('css_file', $options) ? $options['css_file'] : "css/mpdf.css";
		$css_style = array_key_exists('css_style', $options) ? $options['css_style'] : '';
		$header = array_key_exists('header', $options) ? $options['header'] : '';
		$footer = array_key_exists('footer', $options) ? $options['footer'] : '';
		
		$html = "<html>";
		$html .= "<head>";
		
		if(is_array($css_file) && count($css_file))
		{
			foreach($css_file AS $item)
			{
				$html .= "<link href=\"$item\" type=\"text/css\" rel=\"stylesheet\" />";
			}
		}
		else
		{
			$html .= "<link href=\"$css_file\" type=\"text/css\" rel=\"stylesheet\" />";
		}
		
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
	 * Processa il testo HTML per renderlo compatibile con la generazione del pdf
	 * 
	 * @see func.mpdf.php, pdfHtmlToEntities()
	 * @param string $html testo html
	 * @return string or print (debug)
	 */
	public function htmlCreate($html){
		
		$html = \Gino\pdfHtmlToEntities($html);
		$html = utf8_encode($html);
		
		if($this->_debug)
			echo $html;
		else
			return $html;
	}
	
	/**
	 * Definizione del contenuto html
	 * 
	 * @see htmlStart()
	 * @see htmlEnd()
	 * @see htmlCreate()
	 * @param string $text
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b css_file (mixed): percorso ai file css inclusi nel pdf
	 *     - @a array, elenco dei file (ad esempio array('app/news/css/pdf.css', 'app/news/css/local.css'))
	 *     - @a string
	 *   - @b header (string)
	 *   - @b footer (string)
	 *   - @b debug_exit (boolean): interrompe il flusso dell'html nel caso di debug attivo
	 * @return string
	 */
	public function definePage($text, $options=array()) {
		
		$css_file = \Gino\gOpt('css_file', $options, null);
		$header = \Gino\gOpt('header', $options, null);
		$footer = \Gino\gOpt('footer', $options, null);
		$debug_exit = \Gino\gOpt('debug_exit', $options, true);
		
		$buffer = $this->htmlStart(array('header'=>$header, 'footer'=>$footer, 'css_file'=>$css_file));
		$buffer .= $text;
		$buffer .= $this->htmlEnd();
		$buffer = $this->htmlCreate($buffer);
		
		if($this->_debug && $debug_exit) exit();
		
		return $buffer;
	}
	
	/**
	 * Crea il file pdf
	 * 
	 * @see mPDF::WriteHTML()
	 * @see mPDF::Output()
	 * @param mixed $html
	 *   - @a string, documento con pagine aventi la stessa struttura
	 *   - @a array, documento con pagine che possono cambiare struttura, come l'orientamento; struttura dell'array:
	 *     array([, string html], array(orientation=>[, string [L|P]], html=>[, string]), ...)
	 * @param string $filename nome del file pdf
	 * @param array $options
	 *   array associativo di opzioni
	 *   - @b title (string): titolo del pdf
	 *   - @b author (string): autore del pdf
	 *   - @b creator (string): chi ha generato il pdf
	 *   - @b watermark (boolean): scritta in sovraimpressione (default false)
	 *   - @b watermark_text (string): testo della scritta in sovraimpressione (default 'esempio')
	 *   - @b format (string): formato della pagina (default A4)
	 *   - @b landscape (boolean): orientamento orizzontale della pagina (default false)
	 *   - @b mode (string): codifica del testo (default utf-8)
	 *   - @b protection (array): crittografa e imposta i permessi per il file pdf; il valore di default è null, ovvero il documento non è crittografato e garantisce tutte le autorizzazioni all'utente. \n
	 *    L'array può includere alcuni, tutti o nessuno dei seguenti valori che indicano i permessi concessi:
	 *     - @a copy
	 *     - @a print
	 *     - @a modify
	 *     - @a annot-forms
	 *     - @a fill-forms
	 *     - @a extract
	 *     - @a assemble
	 *     - @a print-highres
	 *   - @b user_password (string): password utente del pdf
	 *   - @b owner_password (string): password del proprietario del pdf
	 *   - @b font_size (integer)
	 *   - @b font (string)
	 *   - @b top-margin (integer): distance in mm from top of page to start of text (ignoring any headers)
	 *   - @b header-margin (integer): distance in mm from top of page to start of header
	 *   - @b bottom-margin (integer): distance in mm from bottom of page to bottom of text (ignoring any footers)
	 *   - @b footer-margin (integer): distance in mm from bottom of page to bottom of footer
	 *   - @b orientation (string): specifica l'orientamento di una nuova pagina; i valori accettati sono:
	 *     - L, landscape
	 *     - P, portrait (default)
	 *   - @b simpleTables (boolean): disabilita gli stili css complessi delle tabelle (bordi, padding, ecc.) per incrementare le performance (default false)
	 *   - @b showStats (boolean): visualizza i valori di performance relativi al file pdf (default false); 
	 *   l'opzione sopprime l'output del file pdf e visualizza i dati sul browser, tipo:
	 *   @code
	 *   Generated in 0.45 seconds
	 *   Compiled in 0.46 seconds (total)
	 *   Peak Memory usage 10.25MB
	 *   PDF file size 37kB
	 *   Number of fonts 6
	 *   @endcode
	 *   - @b progressBar (mixed): abilita la visualizzazione di una barra di progresso durante la generazione del file; 
	 *   non è raccomandata come utilizzo generale ma può essere utile in ambiente di sviluppo e nella generazione lenta di documenti
	 *     - 1, visualizza la progress bar
	 *     - 2, visualizza più di una progress bar per un esame dettagliato del progresso
	 *     - false, disabilita la progress bar (default)
	 *   - @b progbar_heading (string): heading personalizzato della progressBar
	 *   - @b progbar_altHTML (string): progressBar personalizzata (html)
	 * @return mixed
	 *   - string (output string)
	 *   - exit (output inline e download)
	 *   - boolean true (output file)
	 * 
	 * Esempio:
	 * @code
	 * $sequence = array($html1, array('orientation'=>'L', 'html'=>$html2));
	 * $pdf->makeFile($sequence, $filename, array('title'=>_("Progetto"), 'author'=>_("Otto Srl"), 'creator'=>_("Marco Guidotti")));
	 * @endcode
	 * 
	 * Il costruttore della classe mPDF viene costruito con i seguenti valori di default
	 * @code
	 * function mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') { ... }
	 * @endcode
	 */
	public function makeFile($html, $filename, $options=array()){
		
		$title = \Gino\gOpt('title', $options, '');
		$author = \Gino\gOpt('author', $options, '');
		$creator = \Gino\gOpt('creator', $options, '');
		$watermark = \Gino\gOpt('watermark', $options, false);
		$watermark_text = \Gino\gOpt('watermark_text', $options, _("esempio"));
		
		$format = array_key_exists('format', $options) && $options['format'] ? $options['format'] : 'A4';
		$landscape = \Gino\gOpt('landscape', $options, false);
		$mode = array_key_exists('mode', $options) && $options['mode'] ? $options['mode'] : 'utf-8';
		
		$protection = \Gino\gOpt('protection', $options, null);
		$user_password = \Gino\gOpt('user_password', $options, '');
		$owner_password = \Gino\gOpt('owner_password', $options, '');
		
		$default_font_size = \Gino\gOpt('font_size', $options, 0);
		$default_font = \Gino\gOpt('font', $options, '');
		$orientation = \Gino\gOpt('orientation', $options, 'P');
		$simple_tables = \Gino\gOpt('simpleTables', $options, false);
		$show_stats = \Gino\gOpt('showStats', $options, false);
		$progress_bar = \Gino\gOpt('progressBar', $options, false);
		$progress_bar_heading = \Gino\gOpt('progbar_heading', $options, null);
		$progress_bar_alt = \Gino\gOpt('progbar_altHTML', $options, null);
		
		if($landscape) $format .= '-L';
		
		if($format == 'A4' || $format == 'A3')
		{
			$left_margin = 20;
			$right_margin = 15;
			$top_margin = 48;
			$bottom_margin = 25;
			$header_margin = 10;
			$footer_margin = 10;
		}
		else	// Valori di default come nel costruttore della classe mPDF (MPDF/mpdf.php)
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
		
		$mpdf = new custom_mpdf(
			$mode, 
			$format, 
			$default_font_size, 
			$default_font, 
			$left_margin, 
			$right_margin, 
			$top_margin, 
			$bottom_margin, 
			$header_margin, 
			$footer_margin, 
			$footer_margin, 
			$orientation
		);
		
		$mpdf->simpleTables = $simple_tables;
		$mpdf->showStats = $show_stats;
		$mpdf->useOnlyCoreFonts = true;
		if(is_array($protection)) {
			$mpdf->SetProtection($protection, $user_password, $owner_password);
		}
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor($author);
		$mpdf->SetCreator($creator);
		$mpdf->SetWatermarkText($watermark_text);
		$mpdf->showWatermarkText = $watermark;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');
		
		if($progress_bar)
		{
			if($progress_bar_heading) $mpdf->progbar_heading = $progress_bar_heading;
			if($progress_bar_alt) $mpdf->progbar_altHTML = $progress_bar_alt;
			
			$mpdf->StartProgressBarOutput($progress_bar);
		}
		
		//$mpdf->allow_charset_conversion = true;
		//$mpdf->charset_in = 'iso-8859-1';	// default 'utf-8'
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
		
		$filename = $this->conformFile($filename);
		
		if($this->_output == 'S')
		{
			return $mpdf->Output($filename, $this->_output);
		}
		elseif($this->_output == 'I' || $this->_output == 'D')
		{
			$mpdf->Output($filename, $this->_output);
			exit();
		}
		else	// F
		{
			$mpdf->Output($filename, $this->_output);
			return true;
		}
	}
	
	/**
	 * Invia il file pdf come allegato email
	 * 
	 * @param string $mpdf_output output con opzione 'string'
	 * @param string $filename nome del file allegato alla email
	 * @param array $options
	 *   array associativo di opzioni
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
	public function sendToEmail($mpdf_output, $filename, $options=array()){
		
		$mailto = array_key_exists('mailto', $options) ? $options['mailto'] : '';
		$from_name = array_key_exists('from_name', $options) ? $options['from_name'] : '';
		$from_mail = array_key_exists('from_mail', $options) ? $options['from_mail'] : '';
		$replyto = array_key_exists('replyto', $options) ? $options['replyto'] : '';
		$subject = array_key_exists('subject', $options) ? $options['subject'] : '';
		$message = array_key_exists('message', $options) ? $options['message'] : '';
		
		$content = chunk_split(base64_encode($mpdf_output));
		
		$filename = $this->conformFile($filename);
		
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

		exit();
	}
	
	/**
	 * Formatta il contenuto da salvare in un campo del database
	 * 
	 * @param string $mpdf_output output con opzione 'string'
	 * @return string
	 */
	public function dataToDB($mpdf_output) {
		
		$string = bin2hex($mpdf_output);
		$string = "0x".$string;
		
		return $string;
	}
	
	/**
	 * Recupera il file pdf salvato come stringa in un record del database
	 * 
	 * @param string $data
	 */
	public function getToDataDB($data) {
		
		$pdf = pack("H*", $data );
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen($pdf));
		header('Content-disposition: inline; filename="'.$name.'"');
		header('Cache-Control: public, must-revalidate, max-age=0');
		header('Pragma: public');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		echo $pdf;
		exit;
	}
	
	/**
	 * Break di pagina
	 * 
	 * @return string
	 */
	public function breakpage(){
		
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
	 *   - @b type (string): tipo di dato (default @a text)
	 *     - @a text, richiama la funzione Gino.pdfChars()
	 *     - @a textarea, richiama la funzione Gino.pdfChars_Textarea()
	 *     - @a editor, richiama la funzione Gino.pdfTextChars()
	 * @return string
	 */
	public function text($text, $options=array()){
		
		$class = \Gino\gOpt('class', $options, '');
		$style = \Gino\gOpt('style', $options, '');
		$other= \Gino\gOpt('other', $options, '');
		$type = \Gino\gOpt('type', $options, 'text');
		
		if($class)
			$class = "class=\"$class\"";
		if($style)
			$style = "style=\"$style\"";
		
		if($type == 'textarea')
			$method = '\Gino\pdfChars_Textarea';
		elseif($type == 'editor')
			$method = '\Gino\pdfTextChars';
		else
			$method = '\Gino\pdfChars';
		
		$text = $method($text);
		
		if($class OR $style OR $other)
		{
			$text = "<span $class$style$other>$text</span>";
		}
		
		return $text;
	}
}
?>