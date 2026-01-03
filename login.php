<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

/* 🔐 SI YA ESTÁ AUTENTICADO, REDIRIGIR */
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header("Location: index.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    putenv('LDAPTLS_REQCERT=never');

    /* CONFIGURACIÓN LDAP */
    $ldap_host = "ldaps://127.0.0.1";
    $base_dn   = "DC=nombredominio,DC=local";

    /* ADMIN DEL DOMINIO (ZENTYAL) */
    $admin_user = "domainadmin@dominio.local";
    $admin_pass = "";

    /* DATOS FORMULARIO */
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === "" || $password === "") {
        $mensaje = "❌ Debes rellenar todos los campos";
    } else {

        $ldap = ldap_connect($ldap_host);

        if ($ldap === false) {
            $mensaje = "❌ Error conectando al servidor LDAP";
        } else {

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            /* 1️⃣ BIND COMO ADMIN */
            if (@ldap_bind($ldap, $admin_user, $admin_pass)) {

                /* 2️⃣ BUSCAR DN DEL USUARIO */
                $filtro = "(sAMAccountName=$usuario)";
                $atributos = ["dn"];

                $search = ldap_search($ldap, $base_dn, $filtro, $atributos);
                $entries = ldap_get_entries($ldap, $search);

                if ($entries["count"] === 1) {

                    $user_dn = $entries[0]["dn"];

                    /* 3️⃣ AUTENTICAR USUARIO */
                    if (@ldap_bind($ldap, $user_dn, $password)) {

                        /* ✅ SESIÓN CORRECTA */
                        $_SESSION['autenticado'] = true;
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['dn'] = $user_dn;

                        ldap_unbind($ldap);
                        header("Location: index.php");
                        exit;

                    } else {
                        $mensaje = "❌ Usuario o contraseña incorrectos";
                    }

                } else {
                    $mensaje = "❌ Usuario no encontrado";
                }

            } else {
                $mensaje = "❌ Error interno LDAP (admin)";
            }

            ldap_unbind($ldap);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login LDAP Zentyal</title>
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

.login-box {
    width: 380px;
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    text-align: center;
}

h1 {
    margin-bottom: 20px;
    color: #1e3a5f;
}

input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
}

input:focus {
    border-color: #1e3a5f;
    outline: none;
}

button {
    width: 100%;
    padding: 12px;
    background: #1e3a5f;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #163153;
}

.error {
    color: red;
    margin-bottom: 10px;
}

</style>
</head>

<body>
<div class="login-box">
    <h1>Login LDAP Zentyal</h1>

    <?php if ($mensaje): ?>
        <div class="error"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
