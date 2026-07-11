<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Interface de Retrait Secrétisé - CanadaService</title>
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
    <form action="traiter_retrait.php" method="POST">
        
        <div class="form-group">
            <label for="operator">Opérateur Mobile :</label>
            <select name="operator" id="operator" required>
                <option value="MTN_BEN">MTN Bénin</option>
                <option value="MOOV_BEN">Moov Bénin</option>
            </select>
        </div>

        <div class="form-group">
            <label for="phone">Numéro de Téléphone (ex: 229XXXXXXXX) :</label>
            <input type="text" name="phone" id="phone" placeholder="229XXXXXXXX" required pattern="^229[0-9]{8}$">
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
