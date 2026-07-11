<?php
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $operator = $_POST["operator"] ?? "";
    $phone = trim($_POST["phone"] ?? "");
    $amount = trim($_POST["amount"] ?? "");

    if ($phone == "" || $amount == "") {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Ici, appelez votre backend sécurisé qui communique avec pawaPay.
        $message = "Les informations sont prêtes à être envoyées au serveur de paiement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Décaissement</title>
<style>
body{
font-family:Arial;
background:#f5f5f5;
}
.box{
width:360px;
margin:50px auto;
background:#fff;
padding:20px;
border-radius:8px;
box-shadow:0 0 10px rgba(0,0,0,.15);
}
input,select,button{
width:100%;
padding:10px;
margin-top:10px;
}
button{
background:#007bff;
color:#fff;
border:none;
cursor:pointer;
}
.msg{
margin-top:15px;
font-weight:bold;
}
</style>
</head>
<body>

<div class="box">

<h2>Décaissement</h2>

<form method="post">

<select name="operator">
<option value="MTN_MOMO_BEN">MTN Bénin</option>
<option value="MOOV_BEN">Moov Bénin</option>
</select>

<input
type="text"
name="phone"
placeholder="229XXXXXXXX"
required>

<input
type="number"
name="amount"
placeholder="Montant XOF"
required>

<button type="submit">
Envoyer
</button>

</form>

<div class="msg">
<?= htmlspecialchars($message) ?>
</div>

</div>

</body>
</html>
