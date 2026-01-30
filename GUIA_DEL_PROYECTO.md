# Guía de Entendimiento del Proyecto: Sistema de Gestión de Archivos TAMEP

Esta guía explica cómo funciona el proyecto paso a paso, desde su estructura general hasta el flujo detallado de una solicitud.

## 1. Visión General
El proyecto es una aplicación web construida en **PHP** (versión >= 8.0) utilizando una arquitectura **MVC (Modelo-Vista-Controlador)** personalizada. No utiliza un framework pesado como Laravel o Symfony, sino una estructura propia ligera y modular.

### Tecnologías Clave:
*   **Lenguaje**: PHP 8.0+
*   **Base de Datos**: MySQL / MariaDB (accesible vía PDO)
*   **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
*   **Dependencias**: Gestionadas con Composer (ej. `phpspreadsheet`, `phpmailer`)

## 2. Estructura de Carpetas Clave

*   **`public/`**: Es la única carpeta accesible desde el navegador. Contiene el archivo `index.php` (punto de entrada) y los archivos estáticos (CSS, JS, imágenes).
*   **`src/`**: Contiene toda la lógica del negocio (el "cerebro" de la aplicación).
    *   `Controllers/`: Controladores que manejan las peticiones.
    *   `Models/`: Modelos que interactúan con la base de datos.
    *   `Core/`: Archivos base del framework propio (Router, Database, etc.).
*   **`views/`**: Contiene las plantillas HTML/PHP que ve el usuario.
*   **`config/`**: Archivos de configuración general y de base de datos.

## 3. Flujo de una Solicitud (Paso a Paso)

Cuando un usuario interactúa con la aplicación (por ejemplo, hace clic en "Buscar Documentos"), ocurre lo siguiente:

### Paso 1: El Navegador hace la petición
El usuario entra a una URL, por ejemplo: `http://tusitio.com/catalogacion`.
El servidor web (Apache/Nginx) dirige *todas* las peticiones al archivo `public/index.php`.

### Paso 2: Punto de Entrada (`public/index.php`)
Este archivo es el "recepcionista". Sus tareas son:
1.  Cargar el `autoloader` (para poder usar las clases automáticamente).
2.  Iniciar la sesión del usuario (`Session::start()`).
3.  Definir todas las rutas disponibles (URL -> Controlador).
4.  Llamar al `Router` para despachar la solicitud.

### Paso 3: El Enrutador (`src/Core/Router.php`)
El Router analiza la URL solicitada (`/catalogacion`).
1.  Busca en su lista de rutas definidas.
2.  Si la encuentra, verifica si tiene **Middlewares** (como `AuthMiddleware`).
    *   *Si no estás logueado, el middleware te redirige al login.*
3.  Si todo está bien, llama al **Controlador** correspondiente: `CatalogacionController`.

### Paso 4: El Controlador (`src/Controllers/...`)
El controlador (ej. `CatalogacionController`) recibe la orden.
1.  Ejecuta el método correspondiente (ej. `index()`).
2.  Si necesita datos, llama a los **Modelos** (ej. `Documento::all()`).
3.  Procesa la información necesaria.

### Paso 5: El Modelo (`src/Models/...`)
El modelo es el encargado de hablar con la Base de Datos.
1.  Usa la clase `Database` (`src/Core/Database.php`) para conectarse.
2.  Ejecuta consultas SQL (`SELECT * FROM documentos...`).
3.  Devuelve los resultados al controlador.

### Paso 6: La Vista (`views/...`)
El controlador toma los datos y carga una vista (ej. `views/catalogacion/index.php`).
1.  La vista renderiza el HTML final mezclado con los datos PHP.
2.  Generalmente incluye partes comunes como el sidebar (`views/components/sidebar.php`).

### Paso 7: Respuesta
El HTML generado se envía de vuelta al navegador del usuario, quien ve la página cargada.

## 4. Módulos Principales

El sistema está organizado en módulos dentro de `src/Controllers` y `views`:

*   **Auth**: Manejo de Login y Logout.
*   **Dashboard**: Pantalla principal con resúmenes.
*   **Catalogación**: Gestión de documentos (buscar, crear, editar). Probablemente el módulo central.
*   **Préstamos**: Gestión de préstamos de documentos físicos, devoluciones e historial.
*   **Reportes**: Generación de informes (probablemente usando SQL complejos).
*   **Contenedores**: Gestión física de dónde están guardados los archivos (Cajas, Estantes).
*   **Admin**: Gestión de usuarios y roles (solo para administradores).

## 5. Base de Datos
La conexión se gestiona en `src/Core/Database.php` usando el patrón **Singleton** (una sola conexión reutilizada). La configuración (usuario, pass, host) vive en `config/database.php`.

---
**Resumen para Desarrolladores:**
Si quieres cambiar algo visual -> Ve a `views/`.
Si quieres cambiar la lógica -> Ve a `src/Controllers/`.
Si quieres cambiar consultas SQL -> Ve a `src/Models/`.
Si quieres agregar una nueva página -> Agrega la ruta en `public/index.php` y crea el método en el controller.
