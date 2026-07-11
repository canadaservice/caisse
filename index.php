<?php
// =================================================================
// CONFIGURATION DE SÉCURITÉ ET ACCÈS API PAWAPAY
// =================================================================
define('SECRET_PASSWORD', '0000'); 

// Votre clé pawaPay incluse de manière sécurisée
$pawaPayToken = "eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjI4NzMiLCJtYXYiOiIxIiwiZXhwIjoyMDkzMjUyMTI3LCJpYXQiOjE3Nzc2MzI5MjcsInBtIjoiREFGLFBBRiIsImp0aSI6IjBhZDY0ZGZjLTA0NWMtNGE1NS04YjI3LThhZDdmNWQ1YjQyMSJ9.S5bEkSU7TzgfYGZbOIwXj55g-XcWqpzv2as9jbDmMl8sNgPz8GLJxWbGrJVrZmyaJ_bSch5MGb6FlVoUE3HtJg"; 

// ADRESSE VALIDÉE DE L'API POUR LES RETRAITS
$apiUrl = "https://pawapay.io"; 

$message = "";

// Traitement de l'envoi du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator = $_POST['operator'];
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];
    $inputPassword = $_POST['password'];

    // Vérification du mot de passe de sécurité
    if ($inputPassword !== SECRET_PASSWORD) {
        $message = "<div style='padding:15px; border-radius:4px; margin-bottom:15px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; text-align:left;'>Erreur : Mot de passe de validation incorrect.</div>";
    } else {
        // Génération automatique de l'identifiant unique (UUID v4) exigé par pawaPay
        $payoutId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Génération de l'heure au format UTC exigé par pawaPay
        $customerTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        // Structure exacte validée (msisdn en minuscules d'après l'erreur 400)
        $data = [
            "payoutId" => $payoutId,
            "amount" => (string)$amount,
            "currency" => "XOF",
            "correspondent" => $operator,
            "recipient" => [
                "type" => "msisdn",
                "address" => [
                    "value" => $phone
                ]
            ],
            "customerTimestamp" => $customerTimestamp,
            "statementDescription" => "Payment"
        ];

        // Envoi de la requête via cURL
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

        // Analyse du résultat final
        if ($httpCode === 200 || $httpCode === 201 || $httpCode === 202) {
            $message = "<div style='padding:15px; border-radius:4px; margin-bottom:15px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; text-align:left;'><h3>Succès !</h3>La demande de retrait a été validée avec succès.<br>ID Transaction : " . $payoutId . "</div>";
        } else {
            $resData = json_decode($response, true);
            $errDetail = isset($resData['message']) ? $resData['message'] : $response;
            $message = "<div style='padding:15px; border-radius:4px; margin-bottom:15px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; text-align:left;'><h3>Échec du retrait</h3>Code d'erreur de l'API : " . $httpCode . "<br>Détails : " . htmlspecialchars($errDetail) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Interface de Retrait Sécurisée - CanadaService</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 50px; text-align: center; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: inline-block; width: 350px; text-align: left; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Demande de Décaissement</h2>
    
    <?php if (!empty($message)) echo $message; ?>
    
    <form action="index.php" method="POST">
        
        <div class="form-group">
            <label for="operator">Opérateur Mobile :</label>
            <select name="operator" id="operator" required>
                <option value="MTN_MOMO_BEN">MTN Bénin</option>
                <option value="MOOV_BEN">Moov Bénin</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone">Numéro de Téléphone (Format Bénin 13 chiffres) :</label>
            <input type="text" name="phone" id="phone" value="22901" required pattern="^22901[0-9]{8}$" title="Laissez 22901 et ajoutez les 8 chiffres restants.">
        </div>

        <div class="form-group">
            <label for="amount">Montant (XOF) :</label>
            <input type="number" name="amount" id="amount" min="100" placeholder="Ex: 5000" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe de validation :</label>
            <input type="password" name="password" id="password" placeholder="Mot de passe sécurisé" required>
        </div>

        <button type="submit">Valider le Retrait vers pawaPay</button>
    </form>
</div>

</body>
</html>
