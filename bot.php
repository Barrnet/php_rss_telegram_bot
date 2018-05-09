<?php 
/* 
**********************************
* Bot Telegram - Invio Messaggi **
**********************************
*/
require_once('bot_config.php');
/* Variabili statiche. Attenzione: Modificare solo le variabili in bot_config.php */
$max_age_articoli = time() - 1200;
/* Definisco a FALSE la data di ultimo avvio. Al primo avvio, considera il parametro max_age_articoli */
$last_send = false;
$last_send_title = "";

/* Salvo nei log che il bot è stato avviato */
$time = date("m-d-y H:i", time());
$log_text = "[$time] Bot avviato. URL Feed: $rss".PHP_EOL;
file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
echo $log_text;
/* Salvo il PID attuale in un file, cosi che il watchdogs possa controllarlo */
$pid = getmypid();
file_put_contents($pid_file, $pid);

/* Funzione invio messaggi alla chat telegram */
function telegram_send_chat_message($token, $chat, $messaggio) {
	/* prelievo timestamp attuale per eventuale log dell'errore */
	$time = time();
	/* Inizializzo variabile URL */
	$url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat";
	/* Imposto variabile URL con il messaggio da inviare */
	$send_text=urlencode($messaggio);
	$url = $url ."&text=$send_text";
	//inizio sessione curl 
	$ch = curl_init();
	$optArray = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true
	);
	curl_setopt_array($ch, $optArray);
	$result = curl_exec($ch);
	/* In caso di errore, lo salvo nei log */
	if ($result == FALSE) {
		$time = date("m-d-y H:i", time());
		$log_text = "[$time] Invio messaggio fallito: $messaggio".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	}
	curl_close($ch);
}

/* Inizio del ciclo del bot */
while (true) {
	/* Se $last_send non è stata parametizzata, significa che il bot è appena partito. La imposto quindi uguale a $max_age_articoli, che è il tempo attuale - 20 minuti. Pubblicherà quindi retroattivamente tutte le notizie più vecchie di 20 minuti*/
	if ($last_send == false) $last_send = $max_age_articoli;
	$ora_attuale = time();
	$articoli = @simplexml_load_file($rss);
	/* Se non è riuscito a scaricare il feed, pubblico un messaggio di errore nel log */
	if ($articoli === false) { 
		$time = date("m-d-y H:i", $ora_attuale);
		$log_text = "[$time] Il bot non è riuscito a contattare il Feed RSS. Connessione fallita a $rss.".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	/* Vado avanti solo se $articoli non è in false, ciò vuol dire che simplexml è riuscito a caricare il feed e posso procedere a processare le notizie */	
	}else{
		/* Inverto l'ordine delle notizie, da decrescente a crescente */
		$xmlArray = array();
		foreach ($articoli->channel->item as $item) $xmlArray[] = $item;
		$xmlArray = array_reverse($xmlArray);
		
		/* Inizio ciclo invio notizie */
		foreach ($xmlArray as $item) {
			/* Estraggo il timestamp dell'articolo */
			$timestamp_articolo = strtotime($item->pubDate);
			/* Calcolo la differenza tra il timestamp attuale e quello dell'articolo */
			$diff_timestamp = time() - $timestamp_articolo;
			/* Controllo se la notizia è più recente dell'ultima pubblicata */
			/* Anche se dovrebbe *non farlo* ma lo fa per ignoti motivi, ho aggiunto un controllo che dovrebbe evitare di far pubblicare due volte la stessa notizia */
			/* Non pubblico gli articoli con meno di 5 minuti (300 secondi) di anzianità */
			if ($timestamp_articolo > $last_send AND $diff_timestamp > $ritardo AND $last_send_title != $item->title) {
				$messaggio = ucfirst($item->category) . " - " . $item->title . PHP_EOL;
				$messaggio .= $item->link . PHP_EOL;
				telegram_send_chat_message($token, $chat, $messaggio);
				$last_send = $timestamp_articolo;
				$last_send_title = $item->title;
			}
		}
	}
	sleep($attesa);
}
?>
