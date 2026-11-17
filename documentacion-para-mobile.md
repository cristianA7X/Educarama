# Documentación para el Desarrollo Mobile (React Native)

**Proyecto:** Educarama Mobile
**Backend:** PHP (API RESTful)
**Entorno actual:** Localhost / Servidor Web

---

## 1. Introducción

El backend de Educarama ha sido adaptado para funcionar como una API dual. Esto significa que el mismo backend atiende:

- A la versión Web (usando cookies/sesiones tradicionales de PHP).
- A la App Mobile en React Native (usando tokens JWT - JSON Web Tokens).

El punto de entrada único para todas las peticiones desde la app móvil es:

```
[URL_DEL_SERVIDOR]/public/api.php?accion=[NOMBRE_DE_LA_ACCION]
```

---

## 2. Autenticación (Login)

Para iniciar sesión desde la app móvil, no se usan cookies.

**Endpoint:** `POST /public/api.php?accion=usuario.iniciarSesion`

**Body** (FormData o JSON según configuración de tu fetch):

- `email`: (string)
- `contrasenia`: (string)

**Respuesta exitosa (HTTP 200):** el backend devolverá un JSON con la propiedad `token` dentro de `datos`. Este token debe guardarse en el dispositivo (ej. usando `AsyncStorage` o `SecureStore`).

```json
{
  "ok": true,
  "datos": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  },
  "redireccion": "/Educarama/views/pages/inicio.php"
}
```

> **Nota:** la app mobile puede ignorar la propiedad `redireccion` y simplemente navegar al Home de la app.

---

## 3. Peticiones Autenticadas (Cualquier otro endpoint)

Para cualquier acción que requiera que el usuario esté logueado (ej: listar protagonistas, ver un perfil, cargar una acción de progresión), es obligatorio enviar el token JWT en las cabeceras (Headers) de la petición.

**Cabecera requerida:**

```
Authorization: Bearer [TU_TOKEN_GUARDADO]
```

**Ejemplo de llamada Fetch en React Native:**

```javascript
const token = await AsyncStorage.getItem('userToken');

fetch('https://tu-servidor.com/public/api.php?accion=protagonista.guardar', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json', // O 'multipart/form-data' si mandás archivos
    'Authorization': `Bearer ${token}`  // ¡Crucial para que no te rebote!
  },
  body: JSON.stringify({
    // ... tus datos ...
  })
})
```

---

## 4. Manejo de Errores Comunes

La API está estandarizada para devolver JSON y códigos HTTP específicos. Tu app debe manejar estas respuestas:

- **HTTP 200 (OK):** todo salió bien. Revisa `response.json().ok` (será `true`).
- **HTTP 401 (No Autorizado) o 403 (Prohibido):** el token no fue enviado, expiró o es inválido. **Acción en la app:** borrar el token local y redirigir al usuario a la pantalla de Login.
- **HTTP 422 (Unprocessable Entity / Regla de Negocio):** ocurrió un error esperado (ej. "Contraseña incorrecta", "Faltan datos"). El mensaje de error estará en la propiedad `mensaje` del JSON de respuesta. **Acción en la app:** mostrar ese mensaje al usuario en un Alert o Toast.
- **HTTP 500 (Error de Servidor):** algo falló en el backend.

**Ejemplo de respuesta de error (ej. HTTP 422):**

```json
{
  "ok": false,
  "mensaje": "El DNI ingresado ya existe en el sistema."
}
```

---

## 5. Notas sobre CORS y CSRF

- **CORS:** ya está habilitado en el backend (`Access-Control-Allow-Origin: *`). No deberías tener problemas de políticas cruzadas en Expo/React Native.
- **CSRF (Tokens de Seguridad):** el sistema exige un campo `csrf_token` para la web, pero está configurado para ignorarlo automáticamente si detecta un JWT válido en las cabeceras. No es necesario que la app mobile gestione o envíe tokens CSRF, solo el JWT.
