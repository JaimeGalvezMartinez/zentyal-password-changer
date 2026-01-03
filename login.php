<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    putenv('LDAPTLS_REQCERT=never');

    /* CONFIGURACIÓN LDAP */
    $ldap_host = "ldaps://127.0.0.1";   # Cambia por la ip del servidor LDAP / Zentyal
    $base_dn   = "DC=dominio,DC=local";  # Cambia en DC=nombre del dominio + DC= .extension del dominio

    /* ADMIN DEL DOMINIO (ZENTYAL) */
    $admin_user = "domainadmin@dominio.local";
    $admin_pass = "";

    /* DATOS FORMULARIO */
    $usuario  = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $mensaje = "❌ Debes rellenar todos los campos";
    } else {

        $ldap = ldap_connect($ldap_host);

        if (!$ldap) {
            $mensaje = "❌ Error conectando al LDAP";
        } else {

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            /* BIND ADMIN */
            if (!@ldap_bind($ldap, $admin_user, $admin_pass)) {
                $mensaje = "❌ Error autenticando con el administrador LDAP";
            } else {

                /* BUSCAR USUARIO */
                $filtro = "(sAMAccountName=$usuario)";
                $atributos = ["dn", "cn"];

                $search = ldap_search($ldap, $base_dn, $filtro, $atributos);
                $entries = ldap_get_entries($ldap, $search);

                if ($entries["count"] == 0) {
                    $mensaje = "❌ Usuario no encontrado";
                } else {

                    $user_dn = $entries[0]["dn"];

                    /* AUTENTICAR USUARIO */
                    if (@ldap_bind($ldap, $user_dn, $password)) {

                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['dn'] = $user_dn;

                        ldap_unbind($ldap);
                        header("Location: index.php");
                        exit;

                    } else {
                        $mensaje = "❌ Usuario o contraseña incorrectos";
                    }
                }
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

    h1 {
        margin-bottom: 20px;
        color: black;
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

<div class="login-box">
    <h1>Login LDAP Zentyal</h1>

    <?php if (!empty($mensaje)): ?>
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
