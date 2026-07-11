<?php
// 1. Définir un mot de passe fort pour protéger l'accès à ce script de retrait
define('SECRET_PASSWORD', 'VOTRE_MOT_DE_PASSE_CONFIDENTIEL_ICI'); 

// 2. Vos accès pawaPay
$pawaPayToken = "VOTRE_CLE_API_PAWAPAY"; 
$apiUrl = "https://pawapay.io"; // Changez par api.sandbox.pawapay.io pour vos tests

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator = $_POST['operator'];
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];
    $inputPassword = $_POST['password'];

    // Sécurité : Vérification du mot de passe
    if ($inputPassword !== SECRET_PASSWORD) {
        die("Erreur : Mot de passe de validation incorrect.");
    }

    // Génération automatique d'un identifiant unique requis par pawaPay
    $payoutId = bin2hex(random_bytes(16)); 

    // Préparation des données pour MTN ou MOOV Bénin
    $data = [
        "payoutId" => $payoutId,
        "amount" => (string)$amount,
        "currency" => "XOF",
        "country" => "BEN",
        "correspondent" => $operator,
        "recipient" => [
            "type" => "MSISDN",
            "address" => $phone
        ],
        "statementDescription" => "Retrait CanadaService"
    ];

    // Envoi de la requête à l'API pawaPay en cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $pawaPayToken,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Analyse du retour pawaPay
    if ($httpCode === 200 || $httpCode === 201) {
        echo "<h3>Succès ! La demande de retrait a été transmise à pawaPay.</h3>";
        echo "ID de transaction : " . $payoutId;
    } else {
        echo "<h3>Échec du retrait. Code d'erreur API : " . $httpCode . "</h3>";
        echo "Détails : " . $response;
    }
}
?>
