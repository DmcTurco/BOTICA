# Generar Form Requests de validación

Genera Form Requests de validación para un controlador existente en el proyecto BOTICA.

## Instrucciones

El argumento `$ARGUMENTS` es el nombre del controlador o modelo (ej: `Product`, `Sale`, `Laboratory`).

1. **Lee el controlador** en `app/Http/Controllers/Company/$ARGUMENTS\Controller.php` para entender qué campos reciben `store()` y `update()`.

2. **Lee la migración** correspondiente para obtener los tipos y restricciones de cada columna.

3. **Crea los Form Requests**:
   - `app/Http/Requests/Store$ARGUMENTS\Request.php`
   - `app/Http/Requests/Update$ARGUMENTS\Request.php`

   Estructura base:
   ```php
   <?php
   
   namespace App\Http\Requests;
   
   use Illuminate\Foundation\Http\FormRequest;
   
   class Store$ARGUMENTSRequest extends FormRequest
   {
       public function authorize(): bool
       {
           return true;
       }
   
       public function rules(): array
       {
           return [
               // reglas basadas en la migración
           ];
       }
   }
   ```

4. **Reglas importantes para el dominio farmacéutico**:
   - Precios: `numeric|min:0`
   - Stock: `integer|min:0`
   - Nombres: `string|max:255`
   - Campos únicos: `unique:tabla,columna` (en Update usar `Rule::unique()->ignore($this->route('id'))`)
   - Relaciones FK: `exists:tabla,id`

5. **Actualiza el controlador** para que `store()` y `update()` reciban los Form Requests en lugar de `Request $request`.

6. **Elimina** cualquier lógica de `$request->validate()` que exista directamente en el controlador.

Al finalizar, muestra las reglas de validación creadas para cada Form Request.
