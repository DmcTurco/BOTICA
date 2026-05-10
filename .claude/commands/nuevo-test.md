# Crear tests Pest para un controlador o modelo

Genera tests con PestPHP para el proyecto BOTICA.

## Instrucciones

El argumento `$ARGUMENTS` es el nombre del controlador o modelo a testear (ej: `Product`, `Sale`, `Category`).

1. **Analiza** el controlador `app/Http/Controllers/Company/$ARGUMENTS\Controller.php` y el modelo `app/Models/$ARGUMENTS.php`.

2. **Determina el tipo de test**:
   - Si es un controlador con rutas HTTP → Feature test en `tests/Feature/$ARGUMENTS\Test.php`
   - Si es lógica de modelo → Unit test en `tests/Unit/$ARGUMENTS\Test.php`

3. **Crea el Feature test** siguiendo este patrón:
   ```php
   <?php
   
   use App\Models\Company;
   use App\Models\$ARGUMENTS;
   
   beforeEach(function () {
       // autenticar con el guard 'company' usando actingAs()
   });
   
   it('lista los $ARGUMENTS_PLURAL', function () {
       // GET /company/$ARGUMENTS_LOWERCASE
       // assertOk, assertViewIs
   });
   
   it('crea un $ARGUMENTS', function () {
       // POST /company/$ARGUMENTS_LOWERCASE
       // assertRedirect, assertDatabaseHas
   });
   
   it('valida campos requeridos al crear', function () {
       // POST con datos vacíos
       // assertSessionHasErrors
   });
   
   it('actualiza un $ARGUMENTS', function () {
       // PUT /company/$ARGUMENTS_LOWERCASE/{id}
       // assertRedirect, assertDatabaseHas
   });
   
   it('elimina un $ARGUMENTS', function () {
       // DELETE /company/$ARGUMENTS_LOWERCASE/{id}
       // assertRedirect, assertDatabaseMissing
   });
   ```

4. **Usa factories** — si no existe `database/factories/$ARGUMENTS\Factory.php`, créala con `php artisan make:factory $ARGUMENTS\Factory --model=$ARGUMENTS`.

5. **Contexto farmacéutico** — en tests de ventas, verifica que el stock se descuenta correctamente. En tests de productos, verifica que las relaciones (categoría, unidad, etc.) se guardan.

6. **Ejecuta los tests** con `./vendor/bin/pest tests/Feature/$ARGUMENTS\Test.php` y asegúrate de que pasen.

Al finalizar muestra los tests creados y el resultado de su ejecución.
