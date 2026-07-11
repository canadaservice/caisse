<?php
// 1. Configuration et récupération sécurisée du Token Render
$token = getenv('PAWAPAY_TOKEN') ?: ($_ENV['PAWAPAY_TOKEN'] ?? null);

$message = "";
$messageType = ""; // 'success' ou 'error'

// 2. Traitement du formulaire lors de la soumission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!$token) {
        $message = "Configuration système manquante : Le jeton PAWAPAY_TOKEN n'est pas configuré sur Render.";
        $messageType = "error";
    } else {
        // Nettoyage et récupération des données du formulaire
        $operator = filter_input(INPUT_POST, 'operator', FILTER_SANITIZE_SPECIAL_CHARS);
        $rawPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        // Validation basique des champs
        if (!$operator || !$rawPhone || !$amount || $amount <= 0) {
            $message = "Veuillez remplir correctement tous les champs du formulaire.";
            $messageType = "error";
        } else {
            // Conserver uniquement les chiffres du numéro de téléphone
            $phone = preg_replace('/[^0-9]/', '', $rawPhone);

            // Gestion automatique des formats Bénin (Nouveau plan à 10/13 chiffres)
            // Si l'utilisateur saisit 10 chiffres (ex: 01xxxxxxxx), on ajoute l'indicatif pays 229
            if (strlen($phone) === 10) {
                $phone = "229" . $phone;
            }

            // Validation finale : Le numéro complet doit faire exactement 13 chiffres
            if (strlen($phone) !== 13 || !str_starts_with($phone, '229')) {
                $message = "Le numéro saisi est invalide. Il doit faire 10 chiffres (ex: 01xxxxxxxx) ou 13 chiffres avec l'indicatif (22901xxxxxxxx).";
                $messageType = "error";
            } else {
                // Génération d'un identifiant de transaction unique obligatoire (UUID v4)
                $cryptoBytes = random_bytes(16);
                $cryptoBytes = chr(ord($cryptoBytes) & 0x0f | 0x40); // Version 4
                $cryptoBytes = chr(ord($cryptoBytes) & 0x3f | 0x80); // Variant
                $payoutId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($cryptoBytes), 4));

                // Préparation des données pour l'API pawaPay
                $payload = [
                    "payoutId" => $payoutId,
                    "amount" => (string)$amount,
                    "currency" => "XOF",
                    "country" => "BEN", 
                    "correspondent" => $operator, // Reçoit 'MTN_BEN' ou 'MOOV_BEN'
                    "recipient" => [
                        "type" => "MSISDN",
                        "address" => [
                            "value" => "+" . $phone // Format international complet requis (+22901XXXXXXXX)
                        ]
                    ],
                    "statementDescription" => "Retrait Caisse"
                ];

                // Initialisation de la requête HTTP (URL Sandbox pour vos tests)
                $url = "https://pawapay.io"; 
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // Analyse de la réponse de l'API
                if ($httpCode === 202) {
                    $message = "Demande de décaissement acceptée ! ID de suivi : " . $payoutId;
                    $messageType = "success";
                } else {
                    $responseData = json_decode($response, true);
                    $errorDetail = $responseData['message'] ?? "Erreur inconnue";
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
    <title>Décaissement - Caisse Retrait</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
        }
        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 30px;
            color: #000000;
        }
        .form-group {
            margin-bottom: 20px;
        }
        select, input {
            width: 100%;
            padding: 14px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
        }
        select:focus, input:focus {
            border-color: #007bff;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-text {
            font-size: 14px;
            color: #666666;
            margin-top: 15px;
            text-align: center;
        }
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
            
            <!-- Sélection de l'opérateur -->
            <div class="form-group">
                <select name="operator" required>
                    <option value="MTN_BEN">MTN Bénin</option>
                    <option value="MOOV_BEN">Moov Bénin</option>
                </select>
            </div>

            <!-- Saisie du numéro de téléphone (Validation souple HTML, traitement robuste PHP) -->
            <div class="form-group">
                <input type="text" name="phone" placeholder="Ex: 0142222197 ou 2290142222197" required>
            </div>

            <!-- Saisie du montant -->
            <div class="form-group">
                <input type="number" name="amount" step="any" placeholder="Montant XOF" required min="1">
            </div>

            <!-- Bouton d'action -->
            <button type="submit" class="btn-submit">Envoyer</button>
            
        </form>

        <p class="info-text">Les informations sont prêtes à être envoyées au serveur de paiement.</p>
    </div>

</body>
</html>
