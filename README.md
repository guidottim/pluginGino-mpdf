pluginGino-mpdf
================

Plugin per [gino CMS](https://github.com/otto-torino/gino) per la creazione di file PDF con la libreria mPDF (http://www.mpdf1.com/mpdf/).   
mPDF è una classe PHP che genera file PDF da codice HTML con Unicode/UTF-8 e supporto CJK. gino è stato testato con la versione 5.4 della libreria.

Installazione
-------------

* scaricare la libreria
* scompattare il file nella directory lib e rinominare la directory senza il numero di versione, ad esempio: # mv MPDF54 MPDF
* copiare il file mpdf.css nella directory css.
* copiare il file func.mpdf.php nella directory lib.

Utilizzo
--------

Per attivare la classe occorre includerla all'inizio del file che genera il PDF:

	require_once(PLUGIN_DIR.OS.'plugin.mpdf.php');

Note
-------------

La libreria mPDF può richiedere una quantità di memoria maggiore del previsto.
Per ovviare all'inconveniente occorre inserire la direttiva **memory_limit** nell'apposito file di configurazione di apache:

	php_admin_value memory_limit "32M"


Parametri di personalizzazione della classe mPDF
------------------------------------------------

class mPDF (   
[ string $mode   
[, mixed $format   
[, float $default_font_size   
[, string $default_font   
[, float $margin_left ,   
float $margin_right ,   
float $margin_top ,   
float $margin_bottom ,   
float $margin_header ,   
float $margin_footer   
[, string $orientation ]]]]]]   
)

plugin.mpdf.php
---------------

La **classe plugin_mpdf** contiene i metodi che permettono di interfacciarsi con la libreria mPDF e che gestiscono la generazione dei file PDF.   
La classe include i file della libreria mPDF (lib/MPDF/mpdf.php) e il file che contiene le funzioni per gestire la conversione corretta dei dati dal database al file PDF (func.mpdf.php). Inoltre il file PDF può essere personalizzato utilizzando i CSS (css/mpdf.css).

**ATTENZIONE**: la classe è in fase di sviluppo. per cui alcune funzionalità sono ancora da perfezionare.

**Metodi pubblici**

void **__construct**(array $**options**=array())

@param options

* output [string]
   * send: invia il file inline al browser
   * file: salva localmente il file (indicare il percorso assoluto)
   * email: crea un file PDF e lo invia come allegato
* debug [boolean]: stampa a video il buffer

string **htmlStart**(array $**options**=array())

@param options

* css [string]: stili css personalizzati (diversi da quelli del file css/pdf.css, incluso di default)
* header [string]: header personalizzato
* footer [string]: footer personalizzato, stringhe sostitutive:
   * _NUMPAGE_: numero di pagina
   * _TOTPAGE_: numero totale di pagine
* number_page [boolean]: stampa il numero di pagina (viene attivato se non è impostato 'footer')

@description: imposta l'header e il footer del codice html
@example

FILE CSS

	body {font-family: sans-serif; font-size: 10pt;}   
	p {margin: 0pt;}   
	td {vertical-align: top;}   
	.items td {border-left: 0.1mm solid #000000; border-right: 0.1mm solid #000000;}   
	table thead td { background-color: #EEEEEE; text-align: center; border: 0.1mm solid #000000;}

HEADER

	$logo = "<img style=\"width:1.56cm;\" src=\"".$this->_doc_img_dir."/logo.jpg\" />";
	<table width=\"100%\" style=\"font-family:Arial,sans-serif; color:#999999;\"><tr>
	<td width=\"20%\" style=\"font-size:10pt;\">$logo</td>
	<td width=\"70%\" style=\"font-size:10pt; text-align:center;\">$header</td>
	<td width=\"10%\" style=\"text-align:right; font-size:8pt;\">Doc. n: $number<br />Rev. N. $revision</td>
	</tr></table>

FOOTER

	<div style=\"border-top:1px solid #BDDAF1; padding-top:3mm;\">
	<table width=\"100%\" style=\"font-family:Arial,sans-serif; color:#999999;\"><tr>
	<td width=\"80%\" style=\"text-align:left; font-size:6pt;\">$footer</td>
	<td width=\"20%\" style=\"text-align:right; font-size:6pt;\">"._("Pagina")." _NUMPAGE_ "._("di")." _TOTPAGE_</td>
	</tr></table>
	</div>

string **htmlEnd**()

@description: imposta la chiusura del codice html

string **htmlCreate**(string $**html**)

@param html: codice html   
@description: adatta il codice html alle corrette specifiche per la generazione del PDF

file **createPDF**(string|array $**html**, string $**filename**, array $**options**=array())

@param html

* string -> documento con pagine aventi la stessa struttura
* array -> documento con pagine che possono cambiare struttura (ad es. orientamento).   
struttura array: array([, string html], array(orientation=>[, string [L|P]], html=>[, string]), ...)

@param filename: nome del file PDF
@param options

* landscape [boolean]: imposta l'orientamento di default (false: portrait)
* title [string]: titolo del PDF
* author [string]: autore del PDF
* creator [string]: chi ha generato il PDF
* watermark [boolean]: scritta in sovraimpressione
* watermark_text [string]: testo della scritta in sovraimpressione

@description: genera il file PDF. Per integrare altre opzioni fare riferimento alle opzioni previste nella class mPDF.

email **emailPDF**(string|array $**html**, string $**filename**, array $**options**=array())

@param html

* string -> documento con pagine aventi la stessa struttura
* array -> documento con pagine che possono cambiare struttura (ad es. orientamento). Struttura array: array([, string html], array(orientation=>[, string [L|P]], html=>[, string]), ...)

@param filename: nome del file PDF   
@param options

* send [boolean]: se vero invia il file anche al browser
* mailto [string]: indirizzo al quale inviare l'email   
* from_name [string]: nome di chi invia l'email
* from_mail [string]: indirizzo che invia l'email   
* replyto [string]:  indirizzo ri reply   
* subject [string]: oggetto dell'email
* message [string]: messaggio dell'email

@description: genera il file PDF e lo invia come allegato email

string **breakPage**()

@description: inserisce un break page

string **longText**(string $**text**)

@param text   
@description: racchiude il testo in un DIV

string **el**(string $**text**, array $**options**=array())

@param text   
@param options

* class [string]: classe del tag SPAN (es. 'label')
* style [string]: stile del tag SPAN (es. 'color:#000000; font-size:10px')
* other [string]: altro nel tag SPAN
* type [string]: text|textarea|editor

@description: a seconda del valore di type, passa il testo a una funzione del file [[func.pdf.php]] ed eventualmente lo racchiude in un tag SPAN.

Esempio
-------

	$pdf = new pdf(array('output'=>[output-type], 'debug'=>[true|false]));
	
	// HTML1
	$html1 .= $pdf->htmlStart(array('header'=>[header], 'footer'=>[footer]));
	[...]
	$html1 .= $pdf->breakPage();
	[...]
	// END1
	
	// oppure creare un metodo che raggruppi i blocchi, tipo
	$html2 = $this->htmlDoc2($pdf, ...);
	
	$html1 = $pdf->htmlCreate($html1);
	$html2 = $pdf->htmlCreate($html2);
	
	$sequence = array($html1, array('orientation'=>'L', 'html'=>$html2));
	$pdf->createPDF($sequence, $filename, array('title'=>_("Titolo"), 'author'=>_("Otto Srl"), 'creator'=>_("Marco Guidotti"), 'watermark'=>[watermark]));

Licenza
-------

MIT License

Link
-----------------

Si prega di segnalare bug, errori e consigli alla pagina del progetto su github: http://github.com/guidottim/pluginGino-mpdf