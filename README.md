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
- Servidor <strong>Zentyal/LDAP</strong> accesible (puede ser local o remoto)
- Acceso a usuario administrador en <strong>Zentyal/LDAP</strong>.

## Funcionamiento

1. Primero, Tenemos que loguearnos con nuestro usuario y contraseña de LDAP, para, poder acceder al formulario para cambiar la contraseña.

<img width="1919" height="960" alt="imagen" src="https://github.com/user-attachments/assets/8518c14f-5a12-4fa0-87e5-23dcd2fbb895" />

2. Después, Cuanto introducimos nuestras credenciales correctamente en el <strong>Login</strong>. Podremos acceder al formulario para cambiar las credenciales del usuario en LDAP/Zentyal

<img width="1917" height="1005" alt="imagen" src="https://github.com/user-attachments/assets/3b8148d1-a0bf-47f5-87f9-fa5676377f76" />

3. Podemos introducir datos en el formuralio. Luego mediante consultas PHP se va a comunicar con el servidor <strong>Zentyal/LDAP</strong> para modificar las credenciales del usuario.

<img width="1917" height="1003" alt="imagen" src="https://github.com/user-attachments/assets/b1e09bd0-92a6-4b1f-bd34-f8c9f5465267" />

4. Las credenciales se habrán cambiado con exito en <strong>Zentyal/LDAP</strong>. Ahora, podremos cerrar sessión e iniciar sessión con las nuevas credenciales.
   
<img width="1918" height="998" alt="imagen" src="https://github.com/user-attachments/assets/67076921-a284-49e7-b545-f2462e98a24b" />

 

