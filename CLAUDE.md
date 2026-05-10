# BOTICA - Contexto del Proyecto

## Descripción
Sistema de gestión para farmacia/botica. Maneja productos, ventas, empleados, categorías, laboratorios y más.

## Repositorio
https://github.com/DmcTurco/BOTICA

## Stack Tecnológico
- **Framework:** Laravel 12
- **Autenticación:** Laravel Fortify + Sanctum (multi-guard: admin, company, employee)
- **Base de datos:** SQLite (`database/database.sqlite`)
- **Frontend:** Vite + Blade templates
- **Testing:** PestPHP

## Modelos del dominio
| Modelo | Descripción |
|--------|-------------|
| `Admin` | Administradores del sistema |
| `User` | Usuarios generales |
| `Company` | Empresa/botica |
| `Employee` | Empleados |
| `Product` | Productos farmacéuticos (tiene categoría, unidad, laboratorio y presentación) |
| `Category` | Categorías de productos |
| `Unit` | Unidades de medida |
| `Laboratory` | Laboratorios/fabricantes |
| `Presentation` | Presentaciones (tabletas, jarabe, etc.) |
| `Sales` | Ventas |

## Estructura del proyecto
```
app/
  Actions/              # Acciones de negocio (Fortify, etc.)
  Http/
    Controllers/
      AdminController.php
      Company/
        CompanyController.php
        ProductController.php
        SalesController.php
        LaboratoryController.php
        CategoryController.php
        UnitController.php
        PresentationController.php
      Employee/
        EmployeeController.php
    Requests/           # Form Requests (pendiente de crear)
    Responses/          # Respuestas personalizadas (Fortify)
  Models/               # Modelos Eloquent
  Providers/            # Service Providers
config/                 # auth, database, fortify, sanctum...
database/
  migrations/           # 14 migraciones existentes
  factories/
  seeders/
routes/
  web.php               # Rutas web (multi-guard)
  api.php               # Rutas API (Sanctum)
resources/views/        # Vistas Blade
tests/
  Feature/
  Unit/
```

## Rutas disponibles
```
GET  /                          welcome
GET  /admin/home                admin dashboard
GET  /company/home              company dashboard
     /company/sales             resource (CRUD)
     /company/products          resource (CRUD)
     /company/laboratories      resource (CRUD)
     /company/categories        resource (CRUD)
GET  /employee/home             employee dashboard
GET  /api/user                  usuario autenticado (Sanctum)
```

## Guards de autenticación
- `admin` — acceso al panel de administración
- `company` — acceso al panel de empresa/botica
- `employee` — acceso al panel de empleado

## Reglas y convenciones
- Seguir convenciones Laravel estrictas (MVC)
- Nombres de clases en PascalCase, métodos en camelCase
- **Validar inputs en Form Requests** — nunca validar directamente en el controlador
- Usar Eloquent ORM, evitar queries SQL crudas
- Las migraciones deben tener método `down()` completo
- API rutas protegidas con Sanctum
- Documentar métodos públicos con PHPDoc

## Contexto de negocio
- Los **precios y el stock son críticos** — validar siempre antes de vender
- Las ventas deben descontar stock atómicamente (usar transacciones DB)
- Los productos tienen relaciones: `category`, `unit`, `laboratory`, `presentation`
- Priorizar seguridad en transacciones de venta

## Comandos de desarrollo frecuentes
```bash
php artisan serve                          # Servidor local
php artisan migrate                        # Ejecutar migraciones
php artisan migrate:fresh --seed           # Resetear BD con seeders
php artisan make:model NombreModelo -mcr   # Modelo + migración + controlador resource
php artisan make:request NombreRequest     # Form Request
php artisan route:list                     # Listar todas las rutas
./vendor/bin/pest                          # Ejecutar tests
npm run dev                                # Frontend dev (Vite)
```

## Herramientas de desarrollo
- Token GitHub: ver `C:\Users\Turco\Desktop\claude\.github_config`
- Script GitHub: `C:\Users\Turco\Desktop\claude\tools\github_helper.py`

## Skills disponibles (slash commands)
- `/nuevo-recurso` — Crea recurso completo: modelo + migración + controlador + Form Request + ruta
- `/form-request` — Genera Form Requests de validación para un controlador existente
- `/nuevo-test` — Crea tests Pest (Feature/Unit) para un controlador o modelo
