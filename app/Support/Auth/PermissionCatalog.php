<?php

namespace App\Support\Auth;

final class PermissionCatalog
{
    public const ACTIONS = [
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Eliminar',
    ];

    /**
     * Recursos alineados a los modelos del dominio (CRUD por modelo).
     *
     * @var array<string, string>
     */
    public const RESOURCES = [
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'permissions' => 'Permisos',
        'customer_profiles' => 'Perfiles de cliente',
        'notifications' => 'Notificaciones',
        'sessions' => 'Sesiones',
        'tokens' => 'Tokens',
        'password_reset_tokens' => 'Tokens de restablecimiento',
        'products' => 'Productos',
        'product_variants' => 'Variantes de producto',
        'product_images' => 'Imágenes de producto',
        'product_offers' => 'Ofertas',
        'categories' => 'Categorías',
        'brands' => 'Marcas',
        'vehicle_models' => 'Modelos de vehículo',
        'colors' => 'Colores',
        'inventory' => 'Inventario',
        'inventory_movements' => 'Movimientos de inventario',
        'orders' => 'Órdenes',
        'order_items' => 'Ítems de orden',
        'order_status_histories' => 'Historial de estados de orden',
        'addresses' => 'Direcciones',
        'payments' => 'Pagos',
        'payment_methods' => 'Medios de pago',
        'carts' => 'Carritos',
        'cart_items' => 'Ítems de carrito',
        'appointments' => 'Reservas',
        'services' => 'Servicios',
        'service_types' => 'Tipos de servicio',
        'service_packages' => 'Paquetes de servicio',
        'blog_posts' => 'Publicaciones del blog',
        'claim_book_entries' => 'Incidencias',
        'contact_messages' => 'Contactos',
    ];

    /**
     * Permisos especiales (fuera del patrón recurso.acción).
     *
     * @return list<array{name: string, slug: string, description: string}>
     */
    public static function specialPermissions(): array
    {
        return [
            [
                'name' => 'Acceder al panel admin',
                'slug' => 'admin.access',
                'description' => 'Permite iniciar sesión y acceder al panel administrativo',
            ],
            [
                'name' => 'Acceder a la tienda',
                'slug' => 'shop.access',
                'description' => 'Permite iniciar sesión en el ecommerce',
            ],
            [
                'name' => 'Realizar pedidos',
                'slug' => 'orders.place',
                'description' => 'Comprar productos en la tienda online',
            ],
        ];
    }

    /**
     * @return list<array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        $permissions = self::specialPermissions();

        foreach (self::RESOURCES as $resource => $label) {
            foreach (self::ACTIONS as $action => $actionLabel) {
                $permissions[] = [
                    'name' => "{$actionLabel} {$label}",
                    'slug' => "{$resource}.{$action}",
                    'description' => "Permite {$actionLabel} {$label} en el sistema",
                ];
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function allSlugs(): array
    {
        return array_column(self::all(), 'slug');
    }

    /**
     * @return list<string>
     */
    public static function adminCrudSlugs(): array
    {
        $slugs = ['admin.access'];

        foreach (array_keys(self::RESOURCES) as $resource) {
            foreach (array_keys(self::ACTIONS) as $action) {
                $slugs[] = "{$resource}.{$action}";
            }
        }

        return $slugs;
    }

    /**
     * @param  list<string>  $resources
     * @param  list<string>|null  $actions
     * @return list<string>
     */
    public static function slugsFor(array $resources, ?array $actions = null): array
    {
        $actions ??= array_keys(self::ACTIONS);
        $slugs = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $slugs[] = "{$resource}.{$action}";
            }
        }

        return $slugs;
    }
}
