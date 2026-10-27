# Products API

API RESTful en **Laravel 12** para gestionar productos y sus precios en distintas divisas.

## Requisitos

- PHP 8.2+ con `pdo_mysql`
- Composer 2
- MySQL 8

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos y ajusta `DB_USERNAME` / `DB_PASSWORD` en `.env`:

```sql
CREATE DATABASE products_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan serve
```

URL base de la API: `http://localhost:8000/api`.

## Datos de prueba

Cada tabla tiene su seeder (`CurrencySeeder`, `ProductSeeder`, `ProductPriceSeeder`).
No usan factories y son idempotentes: se pueden ejecutar varias veces sin duplicar datos.

| Tabla | Datos |
|---|---|
| currencies | USD (1), EUR (0.92), MXN (18.50), COP (4100) |
| products | Laptop 14" (USD), Monitor 27" (USD), Teclado mecánico (EUR), Mouse ergonómico (MXN) |
| product_prices | 4 precios; 2 explícitos y 2 calculados con las tasas de cambio |

## Documentación (Swagger / OpenAPI 3)

- UI interactiva: <http://localhost:8000/api/documentation>
- Especificación JSON: [`storage/api-docs/api-docs.json`](storage/api-docs/api-docs.json)

La especificación se genera desde atributos PHP en controladores, FormRequests y Resources.
Tras cambiarlos: `php artisan l5-swagger:generate`.

Cada endpoint se puede probar desde Swagger con **Try it out**.

### Postman

En [`docs/postman/`](docs/postman):

- `products-api.postman_collection.json`: todos los endpoints con datos de prueba, organizados en
  Divisas, Productos, Precios por divisa, Errores y validaciones, y Eliminación.
- `local.postman_environment.json`: entorno con `baseUrl = http://localhost:8000/api`.

Importa ambos archivos, selecciona el entorno y ejecuta la colección con el **Runner**. Las peticiones guardan
los UUID que usan las siguientes, y cada una comprueba su código de respuesta. Para otro servidor, duplica el
entorno y cambia `baseUrl`.

## Endpoints

Base: `/api`. Todos responden JSON. Las operaciones que modifican datos (crear, actualizar, eliminar y registrar
precios) incluyen un campo `message` con la confirmación; al eliminar se devuelve además el producto borrado y
cuántos precios se eliminaron con él. Códigos de error: 404 (recurso o ruta inexistente, o id que no es UUID),
409 (registro duplicado por peticiones simultáneas), 422 (validación) y 429 (límite de peticiones).

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/currencies` | Lista de divisas |
| GET | `/products` | Lista paginada, más recientes primero (`?search=`, `?per_page=` 1-100, `?page=`) |
| POST | `/products` | Crea un producto |
| GET | `/products/{id}` | Producto con sus precios en otras divisas |
| PUT/PATCH | `/products/{id}` | Actualiza solo los campos enviados (ambos métodos son equivalentes) |
| DELETE | `/products/{id}` | Elimina el producto y sus precios (200 con confirmación) |
| GET | `/products/{id}/prices` | Precios del producto en otras divisas |
| POST | `/products/{id}/prices` | Registra un precio en una divisa |

### Ejemplo

```bash
# UUID de las divisas
curl -s localhost:8000/api/currencies

curl -s -X POST localhost:8000/api/products \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"name":"Teclado","description":"Teclado mecánico","price":120.5,"currency_id":"<UUID USD>","tax_cost":19.28,"manufacturing_cost":45}'

# Precio en EUR calculado automáticamente con las tasas de cambio
curl -s -X POST localhost:8000/api/products/<UUID PRODUCTO>/prices \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"currency_id":"<UUID EUR>"}'
```

## Reglas de negocio

- Los identificadores de la API son **UUID** v7 (ordenables por fecha de creación) y no distinguen mayúsculas.
  Un id que no sea UUID válido devuelve 404.
- `exchange_rate` = unidades de la divisa por 1 USD (divisa de referencia).
- `POST /products/{id}/prices`: si no se envía `price`, se calcula como
  `precio_base × tasa_destino / tasa_base`, redondeado a 2 decimales.
- Un producto tiene como mucho un precio por divisa, y nunca en su propia divisa base
  (ese precio ya está en el producto). Lo garantiza la validación (422) y un índice único en BD; si dos
  peticiones simultáneas registran la misma divisa, la segunda recibe 409.
- El nombre del producto es único (sin distinguir mayúsculas): un mismo producto en varias divisas se representa
  con su precio base y sus precios adicionales, no duplicándolo. Un nombre repetido devuelve 422.
- La divisa base de un producto no puede cambiarse a una en la que ya tiene un precio adicional.
- Si el precio calculado supera el máximo (`9999999999.99`) se responde 422 y hay que enviarlo explícitamente.
- La búsqueda (`?search=`) trata `%` y `_` como texto literal.
- Los importes admiten como máximo 2 decimales y un valor de `0` a `9999999999.99`; se pueden enviar como número
  o como string numérico, y se devuelven como string decimal (`"120.50"`) para no perder precisión.

## Estructura

```
app/
├── Http/
│   ├── Controllers/Api/   Currency, Product, ProductPrice (+ atributos OpenAPI)
│   ├── Requests/          Validación (IndexProduct, StoreProduct, UpdateProduct, StoreProductPrice)
│   └── Resources/         Transformación a JSON (Product, ProductPrice, Currency)
├── Models/                Currency, Product, ProductPrice (relaciones, casts, scope search)
└── Providers/             Rate limiter, modo estricto de Eloquent
database/
├── migrations/            FKs, índice único (product_id, currency_id), borrado en cascada
├── factories/ seeders/
tests/Feature/             Product, ProductPrice, Seeder
```

## Seguridad

- **Rate limiting**: 60 peticiones/min por IP (cabeceras `X-RateLimit-*`, 429 al superarlo).
- Validación de toda entrada con **FormRequests** (tipos, rangos, 2 decimales, existencia de FKs).
- Protección contra *mass assignment* (`$fillable` + solo se persiste `validated()`).
- Integridad en BD: claves foráneas, `restrictOnDelete` en divisas, índices únicos en el nombre del producto y
  por producto/divisa.
- Errores siempre en JSON. Con `APP_DEBUG=false` (producción) no incluyen trazas; el 404 nunca expone nombres
  de clases. `.env.example` trae `APP_DEBUG=true` para desarrollo.
- `Model::shouldBeStrict()` fuera de producción: un lazy loading (N+1) lanza excepción.

## Rendimiento (N+1)

Todas las relaciones se cargan con *eager loading* (`with`, `load`) y los Resources usan `whenLoaded`,
así que nunca disparan consultas. El listado hace como máximo 3 consultas (count + productos + divisas) y el
detalle como máximo 4 (producto + divisa + precios + divisas de los precios), sea cual sea el número de registros;
lo fija un test.

## Tests

```bash
php artisan test
```

22 tests de feature (SQLite en memoria, sin tocar MySQL) que cubren CRUD, paginación y búsqueda, validación,
404, conversión de divisas, duplicados, rate limiting, número de consultas (N+1) y seeders.
