# Motosworld E-Commerce

Sistema monolítico de E-Commerce desarrollado con **Laravel 13**, **MySQL**, **Blade** y **Bootstrap** (frontend pendiente de implementación).

Incluye arquitectura Docker lista para desarrollo y despliegue local.

## Requisitos

### Con Docker (recomendado)

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 4.x o superior
- Docker Compose v2

No necesitas Node.js instalado en tu PC: los assets (Vite + Tailwind) se compilan automáticamente en el contenedor `node`.

### Sin Docker (desarrollo local)

- PHP 8.4 o superior
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
git clone <url-del-repositorio> Motosworld-Ecommerce
cd Motosworld-Ecommerce
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Las variables por defecto ya están configuradas para Docker:

| Variable      | Valor por defecto | Descripción                    |
|---------------|-------------------|--------------------------------|
| `APP_URL`     | `http://localhost:8080` | URL de la aplicación       |
| `DB_HOST`     | `mysql`           | Host de MySQL (nombre del servicio) |
| `DB_DATABASE` | `motosworld`      | Nombre de la base de datos     |
| `DB_USERNAME` | `motosworld`      | Usuario de MySQL               |
| `DB_PASSWORD` | `secret`          | Contraseña de MySQL            |
| `APP_PORT`          | `8080`                    | Puerto expuesto por Nginx      |
| `DB_PORT_EXTERNAL`  | `3307`                    | Puerto MySQL en el host (evita conflicto con MySQL local) |

### 3. Construir e iniciar los contenedores

```bash
docker compose up -d --build
```

Esto levanta cuatro servicios (en orden):

1. **node** — compila assets con Vite/Tailwind (`npm ci` + `npm run build`) y termina
2. **mysql** — MySQL 8.0 (puerto externo configurable con `DB_PORT_EXTERNAL`)
3. **app** — PHP 8.4-FPM (migrate + seed en el entrypoint)
4. **nginx** — servidor web (puerto `APP_PORT`, default 8080)

La primera vez puede tardar varios minutos mientras `node` instala dependencias npm.

**Recompilar assets** tras cambiar CSS/JS:

```bash
docker compose run --rm -e FORCE_ASSET_BUILD=true node
```

> **Nota:** El entrypoint genera `APP_KEY` automáticamente si falta en `.env`. Solo ejecuta `cp .env.example .env` antes del primer `docker compose up`.

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

- Usa `SESSION_DRIVER=file` y `CACHE_STORE=file` en `.env` (ver `.env.example`).
- Reconstruye contenedores tras cambios en `docker/php/php.ini` (OPcache):

```bash
docker compose up -d --build
```

## Levantar el proyecto localmente (sin Docker)

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio> Motosworld-Ecommerce
cd Motosworld-Ecommerce
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
DB_DATABASE=motosworld
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### 3. Crear la base de datos

En MySQL:

```sql
CREATE DATABASE motosworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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

| Componente   | Tecnología                          |
|--------------|-------------------------------------|
| Framework    | Laravel 13                          |
| Base de datos| MySQL 8.0                           |
| ORM          | Eloquent                            |
| Frontend     | Blade + Bootstrap (pendiente)       |
| Assets       | Vite                                |
| Contenedores | Docker + Nginx + PHP-FPM            |

## Próximos pasos de desarrollo

1. Crear layouts base en `resources/views/layouts/` con Bootstrap
2. Implementar vistas del panel admin en `resources/views/admin/`
3. Implementar vistas de la tienda en `resources/views/shop/`
4. Definir modelos Eloquent y migraciones para productos, categorías, pedidos, etc.
5. Implementar autenticación y roles (admin / cliente)

## Licencia

Este proyecto es software de código abierto bajo la [licencia MIT](https://opensource.org/licenses/MIT).
