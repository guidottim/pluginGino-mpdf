pluginGino-mpdf
================

Plugin per [gino CMS](https://github.com/otto-torino/gino) per la creazione di file PDF con la libreria mPDF (http://www.mpdf1.com/mpdf/).   
mPDF è una classe PHP che genera file PDF da codice HTML con Unicode/UTF-8 e supporto CJK. gino è stato testato con la versione 6.0.0 della libreria.   
È richiesta una versione di **gino >= 2.0.0**.

# Installazione

* scaricare la libreria
* scompattare il file nella directory lib e rinominare la directory senza il numero di versione, ad esempio:

	mv mpdf60 mpdf

* copiare il file plugin.mpdf.php nella directory lib/plugin.
* copiare il file func.mpdf.php nella directory lib.
* copiare il file mpdf.css nella directory css.

# Utilizzo

Per attivare la classe occorre includere il file della libreria:

	require_once(PLUGIN_DIR.OS.'plugin.mpdf.php');

# Note

La libreria mPDF può richiedere una quantità di memoria maggiore del previsto.
Per ovviare all'inconveniente occorre inserire la direttiva **memory_limit** nell'apposito file di configurazione di apache:

	php_admin_value memory_limit "128M"

# plugin.mpdf.php

La **classe plugin_mpdf** contiene i metodi che si interfacciano alla libreria mPDF e che gestiscono la generazione dei file PDF.   
La classe include i file della libreria mPDF (lib/MPDF/mpdf.php) e il file che contiene le funzioni per gestire la conversione corretta dei dati dal database al file PDF (func.mpdf.php). Inoltre il file PDF può essere personalizzato utilizzando i CSS (css/mpdf.css).

# Link

Si prega di segnalare bug, errori e consigli alla pagina del progetto su github: http://github.com/guidottim/pluginGino-mpdf.

La documentazione relativa alla libreria si può trovare all'indirizzo <a href="http://gino.otto.to.it/page/view/plugin" target="_blank">http://gino.otto.to.it/page/view/plugin</a>.

# Copyright
Copyright © 2005-2015 [Otto srl](http://www.otto.to.it), [MIT License](http://opensource.org/licenses/MIT)
