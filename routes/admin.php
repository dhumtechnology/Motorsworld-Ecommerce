<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ClaimBookController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeBannerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductOfferController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServicePackageController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\VehicleModelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del panel administrativo
|--------------------------------------------------------------------------
|
| Prefijo: /admin
| Nombre de rutas: admin.*
|
| Las vistas Blade se ubicarán en: resources/views/admin/
|
*/

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Productos
    Route::get('/productos', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::get('/productos/crear', [ProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
    Route::post('/productos', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
    Route::get('/productos/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
    Route::get('/productos/{product}/editar', [ProductController::class, 'edit'])->middleware('permission:products.update')->name('products.edit');
    Route::put('/productos/{product}', [ProductController::class, 'update'])->middleware('permission:products.update')->name('products.update');
    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');
    Route::delete('/productos', [ProductController::class, 'bulkDestroy'])->middleware('permission:products.delete')->name('products.bulk-destroy');

    // Ofertas
    Route::get('/ofertas', [ProductOfferController::class, 'index'])->middleware('permission:product_offers.view')->name('offers.index');
    Route::get('/ofertas/crear', [ProductOfferController::class, 'create'])->middleware('permission:product_offers.create')->name('offers.create');
    Route::post('/ofertas', [ProductOfferController::class, 'store'])->middleware('permission:product_offers.create')->name('offers.store');
    Route::get('/ofertas/{productOffer}/editar', [ProductOfferController::class, 'edit'])->middleware('permission:product_offers.update')->name('offers.edit');
    Route::put('/ofertas/{productOffer}', [ProductOfferController::class, 'update'])->middleware('permission:product_offers.update')->name('offers.update');
    Route::delete('/ofertas/{productOffer}', [ProductOfferController::class, 'destroy'])->middleware('permission:product_offers.delete')->name('offers.destroy');
    Route::delete('/ofertas', [ProductOfferController::class, 'bulkDestroy'])->middleware('permission:product_offers.delete')->name('offers.bulk-destroy');

    // Categorías
    Route::get('/categorias', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::get('/categorias/crear', [CategoryController::class, 'create'])->middleware('permission:categories.create')->name('categories.create');
    Route::post('/categorias', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::get('/categorias/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view')->name('categories.show');
    Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->middleware('permission:categories.update')->name('categories.edit');
    Route::put('/categorias/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');
    Route::delete('/categorias', [CategoryController::class, 'bulkDestroy'])->middleware('permission:categories.delete')->name('categories.bulk-destroy');

    // Marcas
    Route::get('/marcas', [BrandController::class, 'index'])->middleware('permission:brands.view')->name('brands.index');
    Route::get('/marcas/orden', [BrandController::class, 'reorder'])->middleware('permission:brands.update')->name('brands.reorder');
    Route::put('/marcas/orden', [BrandController::class, 'updateOrder'])->middleware('permission:brands.update')->name('brands.reorder.update');
    Route::get('/marcas/crear', [BrandController::class, 'create'])->middleware('permission:brands.create')->name('brands.create');
    Route::post('/marcas', [BrandController::class, 'store'])->middleware('permission:brands.create')->name('brands.store');
    Route::get('/marcas/{brand}', [BrandController::class, 'show'])->middleware('permission:brands.view')->name('brands.show');
    Route::get('/marcas/{brand}/editar', [BrandController::class, 'edit'])->middleware('permission:brands.update')->name('brands.edit');
    Route::put('/marcas/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.update')->name('brands.update');
    Route::delete('/marcas/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete')->name('brands.destroy');
    Route::delete('/marcas', [BrandController::class, 'bulkDestroy'])->middleware('permission:brands.delete')->name('brands.bulk-destroy');

    // Modelos
    Route::get('/modelos', [VehicleModelController::class, 'index'])->middleware('permission:vehicle_models.view')->name('models.index');
    Route::get('/modelos/crear', [VehicleModelController::class, 'create'])->middleware('permission:vehicle_models.create')->name('models.create');
    Route::post('/modelos', [VehicleModelController::class, 'store'])->middleware('permission:vehicle_models.create')->name('models.store');
    Route::get('/modelos/{vehicleModel}', [VehicleModelController::class, 'show'])->middleware('permission:vehicle_models.view')->name('models.show');
    Route::get('/modelos/{vehicleModel}/editar', [VehicleModelController::class, 'edit'])->middleware('permission:vehicle_models.update')->name('models.edit');
    Route::put('/modelos/{vehicleModel}', [VehicleModelController::class, 'update'])->middleware('permission:vehicle_models.update')->name('models.update');
    Route::delete('/modelos/{vehicleModel}', [VehicleModelController::class, 'destroy'])->middleware('permission:vehicle_models.delete')->name('models.destroy');
    Route::delete('/modelos', [VehicleModelController::class, 'bulkDestroy'])->middleware('permission:vehicle_models.delete')->name('models.bulk-destroy');

    // Usuarios del panel
    Route::get('/usuarios', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/usuarios/crear', [AdminUserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/usuarios/{user}', [AdminUserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
    Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
    Route::delete('/usuarios', [AdminUserController::class, 'bulkDestroy'])->middleware('permission:users.delete')->name('users.bulk-destroy');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/crear', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:roles.view')->name('roles.show');
    Route::get('/roles/{role}/editar', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
    Route::delete('/roles', [RoleController::class, 'bulkDestroy'])->middleware('permission:roles.delete')->name('roles.bulk-destroy');

    // Permisos
    Route::get('/permisos', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
    Route::get('/permisos/crear', [PermissionController::class, 'create'])->middleware('permission:permissions.create')->name('permissions.create');
    Route::post('/permisos', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('permissions.store');
    Route::get('/permisos/{permission}', [PermissionController::class, 'show'])->middleware('permission:permissions.view')->name('permissions.show');
    Route::get('/permisos/{permission}/editar', [PermissionController::class, 'edit'])->middleware('permission:permissions.update')->name('permissions.edit');
    Route::put('/permisos/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
    Route::delete('/permisos/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');
    Route::delete('/permisos', [PermissionController::class, 'bulkDestroy'])->middleware('permission:permissions.delete')->name('permissions.bulk-destroy');

    // Clientes
    Route::get('/clientes', [CustomerController::class, 'index'])->middleware('permission:customer_profiles.view')->name('customers.index');
    Route::get('/clientes/{user}', [CustomerController::class, 'show'])->middleware('permission:customer_profiles.view')->name('customers.show');
    Route::delete('/clientes/{user}', [CustomerController::class, 'destroy'])->middleware('permission:customer_profiles.delete')->name('customers.destroy');

    // Órdenes
    Route::get('/ordenes', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/ordenes/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::put('/ordenes/{order}/estado', [OrderController::class, 'updateStatus'])->middleware('permission:orders.update')->name('orders.update-status');

    // Pagos
    Route::get('/pagos', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::get('/pagos/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view')->name('payments.show');

    // Medios de pago
    Route::get('/medios-de-pago', [PaymentMethodController::class, 'index'])->middleware('permission:payment_methods.view')->name('payment-methods.index');
    Route::get('/medios-de-pago/crear', [PaymentMethodController::class, 'create'])->middleware('permission:payment_methods.create')->name('payment-methods.create');
    Route::post('/medios-de-pago', [PaymentMethodController::class, 'store'])->middleware('permission:payment_methods.create')->name('payment-methods.store');
    Route::get('/medios-de-pago/{paymentMethod}/editar', [PaymentMethodController::class, 'edit'])->middleware('permission:payment_methods.update')->name('payment-methods.edit');
    Route::put('/medios-de-pago/{paymentMethod}', [PaymentMethodController::class, 'update'])->middleware('permission:payment_methods.update')->name('payment-methods.update');
    Route::delete('/medios-de-pago/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->middleware('permission:payment_methods.delete')->name('payment-methods.destroy');
    Route::delete('/medios-de-pago', [PaymentMethodController::class, 'bulkDestroy'])->middleware('permission:payment_methods.delete')->name('payment-methods.bulk-destroy');

    // Reservas
    Route::get('/reservas', [AppointmentController::class, 'index'])->middleware('permission:appointments.view')->name('appointments.index');
    Route::get('/reservas/{appointment}/editar', [AppointmentController::class, 'edit'])->middleware('permission:appointments.update')->name('appointments.edit');
    Route::put('/reservas/{appointment}', [AppointmentController::class, 'update'])->middleware('permission:appointments.update')->name('appointments.update');

    // Tipos de servicio
    Route::get('/servicios', [ServiceTypeController::class, 'index'])->middleware('permission:service_types.view')->name('service-types.index');
    Route::get('/servicios/crear', [ServiceTypeController::class, 'create'])->middleware('permission:service_types.create')->name('service-types.create');
    Route::post('/servicios', [ServiceTypeController::class, 'store'])->middleware('permission:service_types.create')->name('service-types.store');
    Route::get('/servicios/{serviceType}/editar', [ServiceTypeController::class, 'edit'])->middleware('permission:service_types.update')->name('service-types.edit');
    Route::put('/servicios/{serviceType}', [ServiceTypeController::class, 'update'])->middleware('permission:service_types.update')->name('service-types.update');
    Route::delete('/servicios/{serviceType}', [ServiceTypeController::class, 'destroy'])->middleware('permission:service_types.delete')->name('service-types.destroy');
    Route::delete('/servicios', [ServiceTypeController::class, 'bulkDestroy'])->middleware('permission:service_types.delete')->name('service-types.bulk-destroy');

    // Paquetes de servicio
    Route::get('/paquetes-de-servicio', [ServicePackageController::class, 'index'])->middleware('permission:service_packages.view')->name('service-packages.index');
    Route::get('/paquetes-de-servicio/crear', [ServicePackageController::class, 'create'])->middleware('permission:service_packages.create')->name('service-packages.create');
    Route::post('/paquetes-de-servicio', [ServicePackageController::class, 'store'])->middleware('permission:service_packages.create')->name('service-packages.store');
    Route::get('/paquetes-de-servicio/{servicePackage}/editar', [ServicePackageController::class, 'edit'])->middleware('permission:service_packages.update')->name('service-packages.edit');
    Route::put('/paquetes-de-servicio/{servicePackage}', [ServicePackageController::class, 'update'])->middleware('permission:service_packages.update')->name('service-packages.update');
    Route::delete('/paquetes-de-servicio/{servicePackage}', [ServicePackageController::class, 'destroy'])->middleware('permission:service_packages.delete')->name('service-packages.destroy');
    Route::delete('/paquetes-de-servicio', [ServicePackageController::class, 'bulkDestroy'])->middleware('permission:service_packages.delete')->name('service-packages.bulk-destroy');

    // Configuración — banners del home
    Route::get('/configuracion', [HomeBannerController::class, 'index'])->middleware('permission:home_banners.view')->name('home-banners.index');
    Route::get('/configuracion/crear', [HomeBannerController::class, 'create'])->middleware('permission:home_banners.create')->name('home-banners.create');
    Route::post('/configuracion', [HomeBannerController::class, 'store'])->middleware('permission:home_banners.create')->name('home-banners.store');
    Route::get('/configuracion/{homeBanner}/editar', [HomeBannerController::class, 'edit'])->middleware('permission:home_banners.update')->name('home-banners.edit');
    Route::put('/configuracion/{homeBanner}', [HomeBannerController::class, 'update'])->middleware('permission:home_banners.update')->name('home-banners.update');
    Route::delete('/configuracion/{homeBanner}', [HomeBannerController::class, 'destroy'])->middleware('permission:home_banners.delete')->name('home-banners.destroy');
    Route::delete('/configuracion', [HomeBannerController::class, 'bulkDestroy'])->middleware('permission:home_banners.delete')->name('home-banners.bulk-destroy');

    // Blog
    Route::get('/blog', [BlogPostController::class, 'index'])->middleware('permission:blog_posts.view')->name('blog-posts.index');
    Route::get('/blog/crear', [BlogPostController::class, 'create'])->middleware('permission:blog_posts.create')->name('blog-posts.create');
    Route::post('/blog', [BlogPostController::class, 'store'])->middleware('permission:blog_posts.create')->name('blog-posts.store');
    Route::get('/blog/{blogPost}/editar', [BlogPostController::class, 'edit'])->middleware('permission:blog_posts.update')->name('blog-posts.edit');
    Route::put('/blog/{blogPost}', [BlogPostController::class, 'update'])->middleware('permission:blog_posts.update')->name('blog-posts.update');
    Route::delete('/blog/{blogPost}', [BlogPostController::class, 'destroy'])->middleware('permission:blog_posts.delete')->name('blog-posts.destroy');
    Route::delete('/blog', [BlogPostController::class, 'bulkDestroy'])->middleware('permission:blog_posts.delete')->name('blog-posts.bulk-destroy');

    // Incidencias (quejas / reclamos)
    Route::get('/libro-reclamaciones/quejas', [ClaimBookController::class, 'complaints'])->middleware('permission:claim_book_entries.view')->name('claim-book.complaints');
    Route::get('/libro-reclamaciones/reclamos', [ClaimBookController::class, 'claims'])->middleware('permission:claim_book_entries.view')->name('claim-book.claims');
    Route::get('/libro-reclamaciones/{claimBookEntry}', [ClaimBookController::class, 'show'])->middleware('permission:claim_book_entries.view')->name('claim-book.show');
    Route::put('/libro-reclamaciones/{claimBookEntry}', [ClaimBookController::class, 'update'])->middleware('permission:claim_book_entries.update')->name('claim-book.update');
    Route::post('/libro-reclamaciones/{claimBookEntry}/responder', [ClaimBookController::class, 'reply'])->middleware('permission:claim_book_entries.update')->name('claim-book.reply');

    // Contactos (mensajes del formulario de contacto)
    Route::get('/contactos', [ContactMessageController::class, 'index'])->middleware('permission:contact_messages.view')->name('contacts.index');
    Route::get('/contactos/{contactMessage}', [ContactMessageController::class, 'show'])->middleware('permission:contact_messages.view')->name('contacts.show');
    Route::put('/contactos/{contactMessage}', [ContactMessageController::class, 'update'])->middleware('permission:contact_messages.update')->name('contacts.update');
    Route::post('/contactos/{contactMessage}/responder', [ContactMessageController::class, 'reply'])->middleware('permission:contact_messages.update')->name('contacts.reply');

    // Inventario
    Route::get('/inventario', [InventoryController::class, 'index'])->middleware('permission:inventory_movements.view')->name('inventory.index');
    Route::get('/inventario/crear', [InventoryController::class, 'create'])->middleware('permission:inventory_movements.create')->name('inventory.create');
    Route::post('/inventario', [InventoryController::class, 'store'])->middleware('permission:inventory_movements.create')->name('inventory.store');
    Route::get('/inventario/importar', [InventoryController::class, 'importForm'])->middleware('permission:inventory_movements.create')->name('inventory.import');
    Route::post('/inventario/importar', [InventoryController::class, 'import'])->middleware('permission:inventory_movements.create')->name('inventory.import.store');
    Route::get('/inventario/plantilla', [InventoryController::class, 'downloadTemplate'])->middleware('permission:inventory_movements.view')->name('inventory.template');
    Route::post('/inventario/exportar', [InventoryController::class, 'export'])->middleware('permission:inventory_movements.view')->name('inventory.export');
    Route::get('/inventario/{inventoryMovement}', [InventoryController::class, 'show'])->middleware('permission:inventory_movements.view')->name('inventory.show');
    Route::delete('/inventario/{inventoryMovement}', [InventoryController::class, 'destroy'])->middleware('permission:inventory_movements.delete')->name('inventory.destroy');
});
