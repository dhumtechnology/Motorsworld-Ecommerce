# Motoworld E-Commerce

Sistema monolítico de E-Commerce desarrollado con **Laravel 13**, **MySQL**, **Blade** y **Bootstrap** (frontend pendiente de implementación).

Incluye arquitectura Docker lista para desarrollo y despliegue local.

## Requisitos

### Con Docker (recomendado)

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 4.x o superior
- Docker Compose v2

No necesitas Node.js instalado en tu PC: el contenedor `node` compila a `public/build` y **recompila al guardar** Blade/CSS/JS (Tailwind incluido).

### Sin Docker (desarrollo local)

- PHP 8.3 o superior
- Composer 2.x
- MySQL 8.0
- Node.js 20+ y npm (para assets con Vite)
- Extensiones PHP: `pdo_mysql`, `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `intl`

## Estructura del proyecto

```
├── app/Http/Controllers/
│   ├── Admin/          # Controladores del panel administrativo
│   └── Shop/           # Controladores de la tienda
├── docker/
│   ├── nginx/          # Configuración de Nginx
│   └── php/            # Dockerfile y entrypoint PHP-FPM
├── resources/views/
│   ├── admin/          # Vistas Blade del admin (pendiente)
│   ├── shop/           # Vistas Blade de la tienda (pendiente)
│   ├── layouts/        # Layouts base (pendiente)
│   └── components/     # Componentes Blade (pendiente)
├── routes/
│   ├── web.php         # Rutas públicas
│   ├── admin.php       # Rutas del panel admin (/admin)
│   └── shop.php        # Rutas de la tienda (/tienda)
└── docker-compose.yml
```

## Levantar el proyecto con Docker

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio> Motoworld-Ecommerce
cd Motoworld-Ecommerce
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Las variables por defecto ya están configuradas para Docker:

| Variable           | Valor por defecto       | Descripción                                                                                                  |
| ------------------ | ----------------------- | ------------------------------------------------------------------------------------------------------------ |
| `APP_URL`          | `http://localhost:8080` | URL de la aplicación                                                                                         |
| `DB_HOST`          | `mysql`                 | Host de MySQL (nombre del servicio)                                                                          |
| `DB_DATABASE`      | `motoworld`             | Nombre de la base de datos                                                                                   |
| `DB_USERNAME`      | `motoworld`             | Usuario de MySQL                                                                                             |
| `DB_PASSWORD`      | `secret`                | Contraseña de MySQL                                                                                          |
| `APP_PORT`         | `8080`                  | Puerto expuesto por Nginx                                                                                    |
| `DB_PORT_EXTERNAL` | `3307`                  | Puerto MySQL en el host (evita conflicto con MySQL local)                                                    |
| `ASSET_MODE`       | `watch`                 | `watch` = build rápido + recompilar al guardar; `dev` = HMR (:5173, más lento en Docker); `once` = una build |
| `SKIP_ASSET_BUILD` | `false`                 | `true` si usas `npm run dev` en el host en lugar del contenedor                                              |
| `VITE_PORT`        | `5173`                  | Solo relevante con `ASSET_MODE=dev`                                                                          |

### 3. Construir e iniciar los contenedores

**Primera vez** (o si cambió `Dockerfile` / `docker-compose.yml`):

```bash
docker compose up -d --build
```

**Día a día** (rápido; no reconstruye imágenes ni fuerza seed/Vite):

```bash
docker compose up -d
```

Servicios:

1. **mysql** — MySQL 8.0
2. **node** — Vite + Tailwind en **watch**: sirve CSS/JS estáticos desde `public/build` y recompila al editar Blade/CSS/JS
3. **app** — PHP-FPM tras migrate; seeders en segundo plano y solo si la BD está vacía (`SEED_ON_START=auto`)
4. **nginx** — espera a que FPM escuche en `:9000` antes de aceptar tráfico

Si abres el navegador demasiado pronto verás la página “arrancando…” (no un 502 vacío). Espera a:

```bash
docker compose ps
# app = healthy, nginx = Up, node = Up
```

### Assets / Tailwind (importante para el equipo)

Tailwind **solo genera CSS de las clases presentes en el build**. Sin watcher, una clase nueva en Blade aparece en el HTML pero **sin estilo**.

**Por qué a uno le funcionaba y a otros no:** quien corre `npm run dev` en el host tiene HMR nativo (rápido). Quien solo hacía `docker compose up` compilaba una vez (o nada) y las clases nuevas nunca entraban al CSS.

Con Docker (recomendado para todos — páginas rápidas):

```bash
docker compose up -d
docker compose logs -f node   # "[assets] Observando…" / "Listo → public/build"
```

Flujo:

1. Editas Blade/CSS/JS
2. En logs de `node` aparece una recompilación
3. Recargas el navegador (Ctrl+F5 si el CSS quedó cacheado)

> **Si la UI sale sin estilos:**
>
> ```bash
> docker compose restart node
> docker compose logs -f node
> ```

### Imágenes subidas en el admin (productos, marcas, etc.)

Las subidas se guardan en `storage/app/public/` y se sirven en `/storage/...`.

- **No se versionan en Git** (cada entorno tiene sus propios archivos).
- Nginx las sirve con un `alias` (no depende del symlink `public/storage`, que en Windows + Docker suele fallar).
- Tras actualizar el repo, recarga nginx: `docker compose up -d nginx` (o `docker compose restart nginx`).
- Si compartes un dump de BD con compañeros, también debes copiar `storage/app/public/` o las imágenes saldrán rotas.

**Por qué a uno le funcionaba y a otros no (antes del fix):** quien tenía el symlink bien creado veía las fotos; en Windows/Docker el enlace `public/storage` a menudo queda roto y `/storage/...` devolvía 404 en tienda y admin.

Compilación única (CI):

```bash
docker compose run --rm -e ASSET_MODE=once node
```

No uses el CDN de Tailwind: pisa los estilos del proyecto.

> **HMR más ágil (opcional):** en el host `npm run dev` + `SKIP_ASSET_BUILD=true` y `docker compose stop node`.  
> `ASSET_MODE=dev` en Docker también da HMR, pero en Docker Desktop/Windows suele ser **mucho más lento**.

> **Nota:** El entrypoint genera `APP_KEY` automáticamente si falta en `.env`. Solo ejecuta `cp .env.example .env` antes del primer `docker compose up`.
> Si tu `.env` aún tiene `SEED_ON_START=true`, cámbialo a `auto` (o `false`) para no reseedeár en cada reinicio.

**Login admin (único usuario sembrado):**

- Email: `admin@motoworld.test`
- Password: `password`

**Seeders iniciales (`db:seed`):** permisos, rol Administrador (+ rol Usuario para clientes de tienda), superadmin, marcas (KTM, Husqvarna, Royal Enfield, CFMOTO, CFLITE), categorías (Motos, Accesorios, Baterías, Neumáticos, Repuestos) y servicios con sus paquetes.

### 4. Verificar la aplicación

Abre en el navegador:

- **Home Laravel:** [http://localhost:8080](http://localhost:8080)
- **Tienda:** [http://localhost:8080/tienda](http://localhost:8080/tienda)
- **Panel admin:** [http://localhost:8080/admin](http://localhost:8080/admin)
- **Health check:** [http://localhost:8080/up](http://localhost:8080/up)

### Comandos Docker útiles

```bash
# Ver estado de los contenedores
docker compose ps

# Ver logs en tiempo real
docker compose logs -f

# Ejecutar comandos Artisan
docker compose exec app php artisan <comando>

# Instalar dependencias PHP
docker compose exec app composer install

# Acceder al contenedor PHP
docker compose exec app bash

# Detener contenedores
docker compose down

# Detener y eliminar volúmenes (borra la BD)
docker compose down -v
```

### Problemas frecuentes (`/catalogo` lento, 500 o 504)

**500 — error de vista o rutas en Blade**

Revisa que exista `resources/views/layouts/shop.blade.php` y que use `route('shop.catalog')`, no `shop.catalog.index`.

```bash
docker compose exec app php artisan view:clear
docker compose exec app php artisan config:clear
docker compose exec app tail -20 storage/logs/laravel.log
```

**504 — Gateway Time-out**

Suele ocurrir en Windows cuando PHP tarda más de lo que Nginx espera (BD mal configurada, contenedor lento o primera compilación de vistas). Verifica `.env`:

```env
DB_HOST=mysql
DB_PORT=3306
DB_PORT_EXTERNAL=3307   # solo puerto en tu PC
```

**Lentitud general en Docker Desktop (Windows/Mac)**

El cuello de botella habitual es el bind mount del proyecto (`.:/var/www/html`): miles de lecturas desde el disco de Windows hacia Linux.

El `docker-compose.yml` ya monta volúmenes Linux para las rutas más costosas:

| Volumen               | Ruta en el contenedor      | Efecto                                                 |
| --------------------- | -------------------------- | ------------------------------------------------------ |
| `app_vendor`          | `vendor/`                  | Autoload de Composer en disco rápido                   |
| `app_blade_cache`     | `storage/framework/views/` | Vistas Blade compiladas sin tocar Windows              |
| `app_bootstrap_cache` | `bootstrap/cache/`         | Caché de Laravel en disco rápido                       |
| `app_file_cache`      | `storage/framework/cache/` | Caché de archivos (`CACHE_STORE=file`) en disco rápido |

Tras actualizar `docker-compose.yml`, recrea los contenedores una vez:

```bash
docker compose down
docker compose up -d --build
```

En `.env` (ver `.env.example`):

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUERY_CACHE_TTL=300
LOG_LEVEL=warning
SEED_ON_START=false   # tras el primer arranque, evita re-seed en cada restart
```

Variables de rendimiento **globales** (no solo catálogo):

| Variable                                                   | Efecto                                                                  |
| ---------------------------------------------------------- | ----------------------------------------------------------------------- |
| Volúmenes Docker (`vendor`, vistas, bootstrap, file cache) | Menos I/O Windows en **toda** la app                                    |
| `QUERY_CACHE_TTL`                                          | Cache de consultas repetidas en cualquier módulo vía `QueryResultCache` |
| `LOG_LEVEL=warning`                                        | Menos escritura a disco en logs                                         |
| `SEED_ON_START=false`                                      | Arranque Docker más rápido                                              |

- En Docker Desktop → Settings → General, activa **Use the WSL 2 based engine** (no requiere mover el repo a WSL) y, si aparece, **VirtioFS** para mejorar I/O de bind mounts.
- Mantén el proyecto en un disco SSD local (no OneDrive/red).
- Reconstruye contenedores tras cambios en `docker/php/php.ini` (OPcache):

```bash
docker compose up -d --build
```

Si añades paquetes Composer en el host, sincroniza el volumen `vendor`:

```bash
docker compose exec app composer install
```

## Levantar el proyecto localmente (sin Docker)

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio> Motoworld-Ecommerce
cd Motoworld-Ecommerce
composer install
cp .env.example .env
```

### 2. Configurar `.env` para MySQL local

Edita `.env` y ajusta la conexión a tu instancia local de MySQL:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=motoworld
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### 3. Crear la base de datos

En MySQL:

```sql
CREATE DATABASE motoworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Generar clave y migrar

```bash
php artisan key:generate
php artisan migrate
```

### 5. Instalar assets (opcional, para Vite y la vista `welcome` por defecto)

```bash
npm install
npm run build
```

> Sin este paso, las rutas principales (`/`, `/admin`, `/tienda`) funcionan con respuestas de texto. Las vistas Blade con Bootstrap se implementarán en `resources/views/`.

### 6. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en [http://localhost:8000](http://localhost:8000).

Para desarrollo con recarga de assets:

```bash
composer run dev
```

## Stack tecnológico

| Componente    | Tecnología                    |
| ------------- | ----------------------------- |
| Framework     | Laravel 13                    |
| Base de datos | MySQL 8.0                     |
| ORM           | Eloquent                      |
| Frontend      | Blade + Bootstrap (pendiente) |
| Assets        | Vite                          |
| Contenedores  | Docker + Nginx + PHP-FPM      |

## Próximos pasos de desarrollo

1. Crear layouts base en `resources/views/layouts/` con Bootstrap
2. Implementar vistas del panel admin en `resources/views/admin/`
3. Implementar vistas de la tienda en `resources/views/shop/`
4. Definir modelos Eloquent y migraciones para productos, categorías, pedidos, etc.
5. Implementar autenticación y roles (admin / cliente)

## Licencia

Este proyecto es software de código abierto bajo la [licencia MIT](https://opensource.org/licenses/MIT).
