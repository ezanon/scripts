<?php
require __DIR__ . '/vendor/autoload.php';

// Função para obter o cliente autenticado usando a conta de serviço
function getService() {
    $client = new Google_Client();
    $client->setApplicationName('Google Calendar API PHP Delete Event');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig('nomadic-tine-316917-bd61656c0914.json'); // Caminho para o arquivo da conta de serviço
    $client->setSubject('stiigc@usp.br'); // Email do calendário que está compartilhado com a conta de serviço

    return new Google_Service_Calendar($client);
}

// Obtenha o ID do evento a ser apagado via GET
//$eventId = $_GET['eventId'] ?? null; // O nome do parâmetro pode ser alterado conforme necessário
$eventId = "NmRtaWdnNDE1cDdpbW5oYWZlOXFrdXNzbGsgY180cjU0b2s1YzU3cDFlM2dhc3JwNWI0cTlic0Bn";

if ($eventId) {
    $calendarId = 'c_4r54ok5c57p1e3gasrp5b4q9bs@group.calendar.google.com'; // ID do calendário
    $service = getService();

    // Tente apagar o evento
    try {
        $service->events->delete($calendarId, $eventId);
        echo "Evento apagado com sucesso!";
    } catch (Google_Service_Exception $e) {
        echo 'Erro ao apagar o evento: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'Erro inesperado: ' . $e->getMessage();
    }
} else {
    echo "ID do evento não fornecido.";
}
?>
