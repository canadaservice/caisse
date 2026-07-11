<?php
// 1. Intégration directe de votre clé d'API réelle (Mode Production / LIVE)
$token = "eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjI4NzMiLCJtYXYiOiIxIiwiZXhwIjoyMDkzMjUyMTI3LCJpYXQiOjE3Nzc2MzI5MjcsInBtIjoiREFGLFBBRiIsImp0aSI6IjBhZDY0ZGZjLTA0NWMtNGE1NS04YjI3LThhZDdmNWQ1YjQyMSJ9.S5bEkSU7TzgfYGZbOIwXj55g-XcWqpzv2as9jbDmMl8sNgPz8GLJxWbGrJVrZmyaJ_bSch5MGb6FlVoUE3HtJg";

$message = "";
$messageType = ""; 

// 2. Traitement du formulaire de décaissement (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($token)) {
        $message = "Configuration système manquante : La clé d'API pawaPay est vide.";
        $messageType = "error";
    } else {
        // Collecte et assainissement des entrées utilisateur
        $operator = filter_input(INPUT_POST, 'operator', FILTER_SANITIZE_SPECIAL_CHARS);
        $rawPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        if (!$operator || !$rawPhone || !$amount || $amount <= 0) {
            $message = "Veuillez remplir correctement tous les champs du formulaire.";
            $messageType = "error";
        } else {
            // Nettoyage du numéro de téléphone (conserver uniquement les chiffres)
            $phone = preg_replace('/[^0-9]/', '', $rawPhone);

            // Gestion automatique du plan à 10 chiffres du Bénin
            if (strlen($phone) === 10) {
                $phone = "229" . $phone;
            }

            // Validation de conformité de la structure internationale béninoise
            if (strlen($phone) !== 13 || !str_starts_with($phone, '229')) {
                $message = "Le numéro saisi est invalide. Entrez vos 10 chiffres (ex: 01xxxxxxxx) ou 13 chiffres avec l'indicatif (22901xxxxxxxx).";
                $messageType = "error";
            } else {
                // Génération de l'UUID v4 standardisé pour le suivi unique pawaPay
                $uuidData = random_bytes(16);
                $uuidData = chr(ord($uuidData) & 0x0f | 0x40); 
                $uuidData = chr(ord($uuidData) & 0x3f | 0x80); 
                $payoutId = sprintf('%s-%s-%s-%s-%s',
                    substr(bin2hex($uuidData), 0, 8),
                    substr(bin2hex($uuidData), 8, 4),
                    substr(bin2hex($uuidData), 12, 4),
                    substr(bin2hex($uuidData), 16, 4),
                    substr(bin2hex($uuidData), 20, 12)
                );

                // Construction de l'objet de données (Payload) pour le Bénin
                $payload = [
                    "payoutId" => $payoutId,
                    "amount" => (string)$amount,
                    "currency" => "XOF",
                    "country" => "BEN", 
                    "correspondent" => $operator, // Transmet 'MTN_BEN' ou 'MOOV_BEN'
                    "recipient" => [
                        "type" => "MSISDN",
                        "address" => [
                            "value" => "+" . $phone
                        ]
                    ],
                    "statementDescription" => "Retrait Caisse"
                ];

                $jsonPayload = json_encode($payload);

                // URL officielle Live (Production V2) de pawaPay
                $url = "https://pawapay.io"; 
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . trim($token),
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload)
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // Vérification de la prise en compte par la passerelle de Production (200, 201 ou 202)
                if ($httpCode === 200 || $httpCode === 201 || $httpCode === 202) {
                    $message = "Succès ! Le décaissement a été accepté. ID de suivi : " . $payoutId;
                    $messageType = "success";
                } else {
                    $responseData = json_decode($response, true);
                    $errorDetail = $responseData['message'] ?? $responseData['error'] ?? "Veuillez vérifier vos permissions pour le réseau sélectionné ou la provision de votre solde.";
                    $message = "Échec du décaissement (Code HTTP " . $httpCode . ") : " . $errorDetail;
                    $messageType = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Décaissement Bénin - Caisse Retrait</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body {
            background-color: #f8f9fa;
            display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;
        }
        .card {
            background: #ffffff; padding: 40px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); width: 100%; max-width: 500px;
        }
        h1 { font-size: 32px; font-weight: 700; margin-top: 0; margin-bottom: 30px; color: #000000; text-align: center; }
        .form-group { margin-bottom: 20px; }
        select, input { width: 100%; padding: 14px; border: 1px solid #cccccc; border-radius: 4px; font-size: 16px; outline: none; transition: border-color 0.2s; }
        select:focus, input:focus { border-color: #007bff; }
        .btn-submit {
            width: 100%; padding: 14px; background-color: #007bff; color: white; border: none;
            border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #0056b3; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; text-align: center; font-weight: bold; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-text { font-size: 14px; color: #666666; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>

    <div class="card">
        <h1>Décaissement</h1>

        <!-- Affichage des messages de retour API -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <!-- Choix des opérateurs béninois -->
            <div class="form-group">
                <select name="operator" required>
                    <option value="MTN_BEN">MTN Bénin</option>
                    <option value="MOOV_BEN">Moov Bénin</option>
                </select>
            </div>

            <!-- Saisie du numéro -->
            <div class="form-group">
                <input type="text" name="phone" placeholder="Ex: 0142222197 ou 2290142222197" required>
            </div>

            <!-- Saisie du montant en XOF -->
            <div class="form-group">
                <input type="number" name="amount" step="any" placeholder="Montant XOF" required min="1">
            </div>

            <button type="submit" class="btn-submit">Envoyer</button>
            
        </form>

        <p class="info-text">🔒 Plateforme connectée en mode direct avec votre clé de production.</p>
    </div>

</body>
</html>
