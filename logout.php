<?php
session_start();

/* Eliminar todas las variables de sesión */
$_SESSION = [];

/* Destruir la sesión */
session_destroy();

/* Evitar cacheo */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* Redirigir al login */
header("Location: login.php");
exit;
