pluginGino-mpdf
================

Plugin per [gino CMS](https://github.com/otto-torino/gino) per la creazione di file PDF con la libreria mPDF (http://www.mpdf1.com/mpdf/).   
mPDF è una classe PHP che genera file PDF da codice HTML con Unicode/UTF-8 e supporto CJK. gino è stato testato con la versione 5.6 della libreria.

Installazione
-------------

* scaricare la libreria
* scompattare il file nella directory lib e rinominare la directory senza il numero di versione, ad esempio: # mv MPDF56 MPDF
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

Licenza
-------

MIT License

Link
-----------------

Si prega di segnalare bug, errori e consigli alla pagina del progetto su github: http://github.com/guidottim/pluginGino-mpdf