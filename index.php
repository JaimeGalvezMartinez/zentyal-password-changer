<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    putenv('LDAPTLS_REQCERT=never');

    $ldap_host = "ldaps://127.0.0.1";  # o la ip de tu Zentyal / ldap
    $base_dn   = "DC=dominio,DC=local"; # Cambiar en DC=nombredeldominio,DC=extensiondominio 

    // 🔐 USUARIO ADMINISTRADOR (OBLIGATORIO)
    $admin_user = "admministradordominio@dominio.local";  # Usuario Zentyal Admin
    $admin_pass = "contraseña_administrador_dominio";  # Contraseña Admin Zentyal

    $usuario     = $_POST['usuario'];
    $old_pass    = $_POST['pass_actual'];
    $new_pass    = $_POST['pass_nueva'];
    $repeat_pass = $_POST['pass_repetir'];

    if ($new_pass !== $repeat_pass) {
        $mensaje = "<div class='error'>Las contraseñas no coinciden</div>";
        goto end;
    }

    $ldap = ldap_connect($ldap_host);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    // 1️⃣ COMPROBAR CREDENCIALES DEL USUARIO
    if (!@ldap_bind($ldap, "$usuario@casa.local", $old_pass)) {
        $mensaje = "<div class='error'>Credenciales incorrectas</div>";
        goto end;
    }

    ldap_unbind($ldap);

    // 2️⃣ BIND COMO ADMIN
    $ldap = ldap_connect($ldap_host);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!ldap_bind($ldap, $admin_user, $admin_pass)) {
        $mensaje = "<div class='error'>Error interno LDAP</div>";
        goto end;
    }

    // 3️⃣ BUSCAR DN REAL DEL USUARIO
    $filter = "(sAMAccountName=$usuario)";
    $search = ldap_search($ldap, $base_dn, $filter, ['dn']);

    if (!$search) {
        $mensaje = "<div class='error'>Usuario no encontrado</div>";
        goto end;
    }

    $entries = ldap_get_entries($ldap, $search);
    if ($entries['count'] !== 1) {
        $mensaje = "<div class='error'>Usuario ambiguo</div>";
        goto end;
    }

    $user_dn = $entries[0]['dn'];

    // 4️⃣ CAMBIAR CONTRASEÑA
    $newPassword = mb_convert_encoding('"' . $new_pass . '"', 'UTF-16LE');
    $entry = ['unicodePwd' => $newPassword];

    if (ldap_mod_replace($ldap, $user_dn, $entry)) {
        $mensaje = "<div class='ok'>Contraseña cambiada correctamente, Por favor, cierre session e inicie session con la nueva contraseña.</div>";
    } else {
        $mensaje = "<div class='error'>No se pudo cambiar la contraseña (política de dominio)</div>";
    }

    ldap_unbind($ldap);
}

end:
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contraseña LDAP</title>
<link rel="icon" type="image/png" href="favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    /* Reset básico */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #1e3a5f, #2c5364);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        width: 380px;
        background: #fff;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .container:hover {
        transform: translateY(-5px);
    }

    h2 {
        margin-bottom: 20px;
        color: #1e3a5f;
    }

    input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    input:focus {
        border-color: #1e3a5f;
        outline: none;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #1e3a5f;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: #163153;
    }

    .ok {
        color: green;
        margin-bottom: 10px;
        display: block;
    }

    .error {
        color: red;
        margin-bottom: 10px;
        display: block;
    }

    /* Mensaje animado */
    .ok, .error {
        animation: fadein 0.5s ease-in-out;
    }

    @keyframes fadein {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body>
<div class="container">
    <h2>Cambiar contraseña LDAP Zentyal</h2>
    <?= $mensaje ?>
    <form method="POST">
        <input name="usuario" placeholder="Usuario (ej. aperez)" required>
        <input type="password" name="pass_actual" placeholder="Contraseña actual" required>
        <input type="password" name="pass_nueva" placeholder="Nueva contraseña" required>
        <input type="password" name="pass_repetir" placeholder="Repetir contraseña" required>
        <button type="submit">Cambiar contraseña</button>
    </form>
</div>
</body>
</html>
