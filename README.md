# zentyal-password-changer
Formulario web en PHP para permitir a los usuarios cambiar su contraseña en un servidor Zentyal / LDAP, Este formulario PHP Lo he creado basicamente para facilitar a cada usuario cambiar facilmente su contraseña.

## 💡 Características

- Verifica las credenciales actuales del usuario antes de cambiar la contraseña.
- Permite cambiar la contraseña a través de un **bind de administrador** en LDAP.
- Mensajes de error y éxito claros.
- Diseño moderno y responsivo con CSS.
- Compatible con **Zentyal 8.0**

## 🛠 Requisitos

- Servidor web con PHP 7+ (Apache, Nginx, etc.)
- Extensión LDAP de PHP. ldap mbstring
- ```bash
  sudo apt update
  sudo apt install php-ldap php-mbstring
  sudo systemctl restart apache2
´´´
- Servidor LDAP/AD accesible (puede ser local o remoto)
- Acceso a usuario administrador en LDAP

