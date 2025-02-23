<?php
// JSON-Datenbank laden
$database = json_decode(file_get_contents('database.json'), true);

// Eingehende Nachricht vom Benutzer
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = strtolower(trim($input['message']));

// Prüfen, ob es sich um einen Lernbefehl handelt
if (strpos($userMessage, 'lerne:') === 0) {
    // Erwartetes Format: "lerne: Frage: <deine Frage> Antwort: <die Antwort>"
    $content = substr($userMessage, strlen('lerne:'));
    if (preg_match('/frage:\s*(.*?)\s*antwort:\s*(.*)/i', $content, $matches)) {
        $learnQuestion = trim($matches[1]);
        $learnAnswer = trim($matches[2]);
        if (!isset($database['learned'])) {
            $database['learned'] = array();
        }
        // Neuen Q/A-Eintrag hinzufügen
        $database['learned'][] = array(
            'question' => strtolower($learnQuestion),
            'answer' => $learnAnswer
        );
        file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
        echo json_encode(['reply' => 'Neue Lerninformation gespeichert.']);
        exit;
    } else {
        echo json_encode(['reply' => 'Lernbefehl nicht korrekt formatiert. Verwende: lerne: Frage: <deine Frage> Antwort: <die Antwort>']);
        exit;
    }
}

// Hauptfunktion zur Antwortgenerierung
function getChatbotResponse($userMessage, &$database) {
    // Zuerst in den gelernten Q/A-Paaren suchen
    if (isset($database['learned']) && is_array($database['learned'])) {
        foreach ($database['learned'] as $pair) {
            // Einfache Ähnlichkeitsprüfung (Threshold ca. 70%)
            similar_text($userMessage, strtolower($pair['question']), $percent);
            if ($percent > 70) {
                return $pair['answer'];
            }
        }
    }
    
    // Wenn der Benutzer nach dem Bitcoin-Preis fragt
    if (strpos($userMessage, 'bitcoin preis') !== false) {
        if (isDataRecent($database['bitcoin_price']['last_checked'])) {
            return "Der aktuelle Bitcoin-Preis aus der Datenbank ist: " . $database['bitcoin_price']['price'];
        } else {
            return getBitcoinPrice($database);
        }
    }
    
    // Wenn der Benutzer nach Wetterdaten fragt
    if (strpos($userMessage, 'wetter') !== false) {
        if (isDataRecent($database['weather']['last_checked'])) {
            return "Die aktuelle Temperatur ist: " . $database['weather']['temperature'] . " und der Zustand ist: " . $database['weather']['conditions'];
        } else {
            return getWeatherData($database);
        }
    }
    
    // Vordefinierte Antworten
    if (strpos($userMessage, 'hallo') !== false) {
        return $database['greeting'];
    }
    if (strpos($userMessage, 'wie geht') !== false) {
        return $database['how_are_you'];
    }
    
    // Standardantwort, wenn nichts passt
    return $database['default'];
}

// Funktion zum Abrufen und Aktualisieren des Bitcoin-Preises per Web-Scraping
function getBitcoinPrice(&$database) {
    $url = 'https://www.coindesk.com/price/bitcoin';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    preg_match('/<div class="price-large">(\$[0-9,]+\.[0-9]{2})<\/div>/', $output, $matches);
    if (isset($matches[1])) {
        $database['bitcoin_price']['price'] = $matches[1];
        $database['bitcoin_price']['last_checked'] = date('c');
        file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
        return "Der aktuelle Bitcoin-Preis ist: " . $matches[1];
    } else {
        return "Entschuldigung, ich konnte den Bitcoin-Preis nicht finden.";
    }
}

// Funktion zum Abrufen und Aktualisieren der Wetterdaten per Web-Scraping
function getWeatherData(&$database) {
    $url = 'https://www.wetter.com/deutschland/berlin/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    // Hier ist ein Beispiel; der tatsächliche Regex muss an die Zielseite angepasst werden.
    preg_match('/<div class="today-temp">(\d+°C)<\/div>/', $output, $matches);
    preg_match('/<div class="today-weather">(\w+)<\/div>/', $output, $matches_conditions);
    if (isset($matches[1]) && isset($matches_conditions[1])) {
        $database['weather']['temperature'] = $matches[1];
        $database['weather']['conditions'] = $matches_conditions[1];
        $database['weather']['last_checked'] = date('c');
        file_put_contents('database.json', json_encode($database, JSON_PRETTY_PRINT));
        return "Die aktuelle Temperatur ist: " . $matches[1] . " und der Zustand ist: " . $matches_conditions[1];
    } else {
        return "Entschuldigung, ich konnte die Wetterdaten nicht finden.";
    }
}

// Prüfen, ob die Daten aktuell sind (z. B. innerhalb der letzten Stunde)
function isDataRecent($lastChecked) {
    $timeDiff = time() - strtotime($lastChecked);
    return $timeDiff < 3600;
}

// Antwort generieren und zurückgeben
$response = getChatbotResponse($userMessage, $database);
echo json_encode(['reply' => $response]);
?>
