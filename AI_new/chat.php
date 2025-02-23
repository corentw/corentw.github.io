<?php
// Chatbot Antworten aus einer JSON-Datei laden
$database = json_decode(file_get_contents('database.json'), true);

// Eingehende Nachricht vom Benutzer
$input = json_decode(file_get_contents('php://input'), true);

// Funktion zur Generierung einer Antwort
function getChatbotResponse($userMessage, $database) {
    // Kleinbuchstaben für Vergleich
    $userMessage = strtolower($userMessage);
    
    // Wenn der Benutzer nach dem Bitcoin-Preis fragt
    if (strpos($userMessage, 'bitcoin preis') !== false) {
        // Prüfen, ob der Bitcoin-Preis in der JSON-Datenbank aktuell ist
        if (isDataRecent($database['bitcoin_price']['last_checked'])) {
            return "Der aktuelle Bitcoin-Preis aus der Datenbank ist: " . $database['bitcoin_price']['price'];
        } else {
            // Wenn die Daten nicht aktuell sind, scrape sie
            return getBitcoinPrice($database);
        }
    }
    
    // Weitere Anfragen wie Wetterdaten
    if (strpos($userMessage, 'wetter') !== false) {
        if (isDataRecent($database['weather']['last_checked'])) {
            return "Die aktuelle Temperatur ist: " . $database['weather']['temperature'] . " und der Zustand ist: " . $database['weather']['conditions'];
        } else {
            return getWeatherData($database);
        }
    }

    // Antworten auf vordefinierte Nachrichten
    if (strpos($userMessage, 'hallo') !== false) {
        return $database['greeting'];
    }
    if (strpos($userMessage, 'wie geht') !== false) {
        return $database['how_are_you'];
    }
    
    // Standardantwort
    return $database['default'];
}

// Funktion, um den Bitcoin-Preis zu scrapen und die JSON-Datei zu aktualisieren
function getBitcoinPrice(&$database) {
    // URL der Webseite, die den Bitcoin-Preis anzeigt (z.B. CoinDesk)
    $url = 'https://www.coindesk.com/price/bitcoin';
    
    // cURL Initialisierung
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    
    // Suche den Bitcoin-Preis auf der Seite
    preg_match('/<div class="price-large">(\$[0-9,]+\.[0-9]{2})<\/div>/', $output, $matches);
    
    // Rückgabe des Bitcoin-Preises
    if (isset($matches[1])) {
        // Speichern des neuen Preises und des Zeitpunkts in der JSON-Datenbank
        $database['bitcoin_price']['price'] = $matches[1];
        $database['bitcoin_price']['last_checked'] = date('c');  // Aktuelles Datum/Zeit im ISO 8601-Format
        // Datenbank aktualisieren
        file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
        return "Der aktuelle Bitcoin-Preis ist: " . $matches[1];
    } else {
        return "Entschuldigung, ich konnte den Bitcoin-Preis nicht finden.";
    }
}

// Funktion, um Wetterdaten zu scrapen und die JSON-Datei zu aktualisieren
function getWeatherData(&$database) {
    // Beispiel-URL für Wetterdaten (z.B. wetter.com)
    $url = 'https://www.wetter.com/deutschland/berlin/';
    
    // cURL Initialisierung
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    
    // Suchen der Wetterdaten (hier als Beispiel, tatsächlicher HTML-Code der Seite ist anders)
    preg_match('/<div class="today-temp">(\d+°C)<\/div>/', $output, $matches);
    preg_match('/<div class="today-weather">(\w+)<\/div>/', $output, $matches_conditions);
    
    // Rückgabe der Wetterdaten
    if (isset($matches[1]) && isset($matches_conditions[1])) {
        // Speichern der neuen Wetterdaten und des Zeitpunkts in der JSON-Datenbank
        $database['weather']['temperature'] = $matches[1];
        $database['weather']['conditions'] = $matches_conditions[1];
        $database['weather']['last_checked'] = date('c');  // Aktuelles Datum/Zeit im ISO 8601-Format
        // Datenbank aktualisieren
        file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
        return "Die aktuelle Temperatur ist: " . $matches[1] . " und der Zustand ist: " . $matches_conditions[1];
    } else {
        return "Entschuldigung, ich konnte die Wetterdaten nicht finden.";
    }
}

// Funktion, um zu prüfen, ob die Daten aktuell sind (z.B. innerhalb der letzten Stunde)
function isDataRecent($lastChecked) {
    $timeDiff = time() - strtotime($lastChecked);
    return $timeDiff < 3600;  // 1 Stunde in Sekunden
}

// KI Antwort generieren
$response = getChatbotResponse($input['message'], $database);

// JSON Antwort zurückgeben
echo json_encode(['reply' => $response]);
?>
