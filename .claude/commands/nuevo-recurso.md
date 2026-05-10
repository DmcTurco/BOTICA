# Crear nuevo recurso completo

Crea un recurso completo para el proyecto BOTICA siguiendo las convenciones de Laravel 12.

## Pasos a ejecutar

El argumento `$ARGUMENTS` contiene el nombre del recurso en PascalCase (ej: `Supplier`, `Purchase`).

1. **Modelo + migración** — ejecuta `php artisan make:model $ARGUMENTS -m` y completa los campos en la migración según el contexto del negocio farmacéutico. Siempre incluye `down()`.

2. **Controlador resource** — ejecuta `php artisan make:controller Company/$ARGUMENTS\Controller --resource` y completa los métodos `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` usando Eloquent y retornando vistas Blade.

3. **Form Requests** — crea `app/Http/Requests/Store$ARGUMENTS\Request.php` y `app/Http/Requests/Update$ARGUMENTS\Request.php` con reglas de validación apropiadas. El controlador debe tipar estos Form Requests en `store()` y `update()`.

4. **Ruta resource** — agrega en `routes/web.php` dentro del grupo del guard `company`:
   ```php
   Route::resource('$ARGUMENTS_LOWERCASE', \App\Http\Controllers\Company\$ARGUMENTS\Controller::class);
   ```

5. **Vistas Blade** — crea las vistas en `resources/views/company/$ARGUMENTS_LOWERCASE/`: `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`.

6. **Verifica** — ejecuta `php artisan route:list | grep $ARGUMENTS_LOWERCASE` para confirmar que las rutas quedaron registradas.

Al finalizar muestra un resumen de los archivos creados y las rutas registradas.
