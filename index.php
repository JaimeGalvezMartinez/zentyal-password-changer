<?php
/***********************
 * PROTECCIÓN POR SESIÓN
 ***********************/
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: login.php");
    exit;
}

/***********************
 * CONFIGURACIÓN PHP
 ***********************/
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    putenv('LDAPTLS_REQCERT=never');

    $ldap_host = "ldaps://127.0.0.1"; # ip de ZENTYAL/LDAP
    $base_dn   = "DC=nombredominio,DC=local";  

    // 🔐 ADMIN DEL DOMINIO (ZENTYAL)
    $admin_user = "domainadmin@nombredominio.local";  # usuario admin zentyal 
    $admin_pass = ""; # La clave de Usuario Admin LDAP

    $usuario     = trim($_POST['usuario']);
    $old_pass    = $_POST['pass_actual'];
    $new_pass    = $_POST['pass_nueva'];
    $repeat_pass = $_POST['pass_repetir'];

    if ($new_pass !== $repeat_pass) {
        $mensaje = "<div class='error'>Las contraseñas no coinciden</div>";
        goto end;
    }

    /*********************************
     * 1️⃣ COMPROBAR CREDENCIALES USER
     *********************************/
    $ldap = ldap_connect($ldap_host);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!@ldap_bind($ldap, "$usuario@casa.local", $old_pass)) {
        $mensaje = "<div class='error'>Usuario o contraseña actual incorrectos</div>";
        goto end;
    }

    ldap_unbind($ldap);

    /*********************************
     * 2️⃣ BIND COMO ADMIN
     *********************************/
    $ldap = ldap_connect($ldap_host);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!ldap_bind($ldap, $admin_user, $admin_pass)) {
        $mensaje = "<div class='error'>Error interno al conectar con LDAP</div>";
        goto end;
    }

    /*********************************
     * 3️⃣ BUSCAR DN REAL DEL USUARIO
     *********************************/
    $usuario_ldap = ldap_escape($usuario, '', LDAP_ESCAPE_FILTER);
    $filter = "(sAMAccountName=$usuario_ldap)";
    $search = ldap_search($ldap, $base_dn, $filter, ['dn']);

    if (!$search) {
        $mensaje = "<div class='error'>Usuario no encontrado en el dominio</div>";
        goto end;
    }

    $entries = ldap_get_entries($ldap, $search);

    if ($entries['count'] !== 1) {
        $mensaje = "<div class='error'>Usuario ambiguo o inexistente</div>";
        goto end;
    }

    $user_dn = $entries[0]['dn'];

    /*********************************
     * 4️⃣ CAMBIAR CONTRASEÑA
     *********************************/
    $newPassword = mb_convert_encoding('"' . $new_pass . '"', 'UTF-16LE');
    $entry = ['unicodePwd' => $newPassword];

    if (ldap_mod_replace($ldap, $user_dn, $entry)) {
        $mensaje = "<div class='ok'>Contraseña cambiada correctamente. Vuelve a iniciar sesión.</div>";
    } else {
        $mensaje = "<div class='error'>No se pudo cambiar la contraseña (política del dominio)</div>";
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
}

button:hover {
    background: #163153;
}

.ok {
    color: green;
    margin-bottom: 10px;
}

.error {
    color: red;
    margin-bottom: 10px;
}

a.logout {
    display: block;
    margin-top: 15px;
    font-size: 14px;
    color: #1e3a5f;
    text-decoration: none;
}
</style>
</head>

<body>
<div class="container">
    <h2>Cambiar contraseña Zentyal LDAP</h2>

    <?= $mensaje ?>

    <form method="POST">
        <input name="usuario" placeholder="Usuario (ej. aperez)" required>
        <input type="password" name="pass_actual" placeholder="Contraseña actual" required>
        <input type="password" name="pass_nueva" placeholder="Nueva contraseña" required>
        <input type="password" name="pass_repetir" placeholder="Repetir contraseña" required>
        <button type="submit">Cambiar contraseña</button>
    </form>

    <a class="logout" href="logout.php">Cerrar sesión</a>

</div>
</body>
</html>

