```
Proyecto/
├── config/
│   ├── database.php          # Configuración de BD
│   └── app.php               # Configuración general
├── src/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── Catalogacion/
│   │   │   ├── DocumentoController.php
│   │   │   └── BusquedaController.php
│   │   ├── Prestamos/
│   │   │   └── PrestamoController.php
│   │   ├── Reportes/
│   │   │   └── ReporteController.php
│   │   ├── Admin/
│   │   │   ├── UsuarioController.php
│   │   │   └── RolController.php
│   │   └── Normalizacion/
│   │       └── NormalizacionController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   ├── Ubicacion.php
│   │   ├── UnidadArea.php
│   │   ├── ContenedorFisico.php
│   │   ├── RegistroDiario.php
│   │   ├── RegistroIngreso.php
│   │   ├── RegistroEgreso.php
│   │   └── Prestamo.php
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php
│   │   └── Database.php
│   └── Middleware/
│       ├── AuthMiddleware.php
│       └── RoleMiddleware.php
├── public/
│   ├── index.php             # Punto de entrada
│   ├── assets/
│   │   ├── css/
│   │   │   ├── main.css
│   │   │   ├── sidebar.css
│   │   │   └── components.css
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   └── components/
│   │   └── images/
│   │       └── logo-tamep.png
├── views/
│   ├── layouts/
│   │   ├── main.php          # Layout principal con sidebar
│   │   └── auth.php          # Layout para login
│   ├── components/
│   │   ├── sidebar.php
│   │   ├── header.php
│   │   └── footer.php
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   └── index.php
│   ├── catalogacion/
│   │   ├── index.php
│   │   ├── busqueda.php
│   │   └── detalle.php
│   ├── prestamos/
│   │   ├── index.php
│   │   ├── solicitar.php
│   │   └── historico.php
│   ├── reportes/
│   │   └── index.php
│   ├── admin/
│   │   └── usuarios/
│   │       ├── index.php
│   │       └── form.php
│   └── normalizacion/
│       └── index.php
└── .htaccess                 # Para rutas limpias
```
