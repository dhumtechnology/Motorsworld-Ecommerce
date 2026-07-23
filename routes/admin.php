<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ProductController;
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

    Route::get('/productos', [ProductController::class, 'index'])->name('products.index');
    Route::get('/productos/crear', [ProductController::class, 'create'])->name('products.create');
    Route::post('/productos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/productos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/productos/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('/productos', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');

    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categorias/crear', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::delete('/categorias', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');

    Route::get('/marcas', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/marcas/crear', [BrandController::class, 'create'])->name('brands.create');
    Route::post('/marcas', [BrandController::class, 'store'])->name('brands.store');
    Route::get('/marcas/{brand}/editar', [BrandController::class, 'edit'])->name('brands.edit');
    Route::put('/marcas/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/marcas/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    Route::delete('/marcas', [BrandController::class, 'bulkDestroy'])->name('brands.bulk-destroy');

    Route::get('/modelos', [VehicleModelController::class, 'index'])->name('models.index');
    Route::get('/modelos/crear', [VehicleModelController::class, 'create'])->name('models.create');
    Route::post('/modelos', [VehicleModelController::class, 'store'])->name('models.store');
    Route::get('/modelos/{vehicleModel}/editar', [VehicleModelController::class, 'edit'])->name('models.edit');
    Route::put('/modelos/{vehicleModel}', [VehicleModelController::class, 'update'])->name('models.update');
    Route::delete('/modelos/{vehicleModel}', [VehicleModelController::class, 'destroy'])->name('models.destroy');
    Route::delete('/modelos', [VehicleModelController::class, 'bulkDestroy'])->name('models.bulk-destroy');

    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/usuarios/crear', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::delete('/usuarios', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-destroy');

    Route::get('/clientes', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/ordenes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/ordenes/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/pagos', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/pagos/{payment}', [PaymentController::class, 'show'])->name('payments.show');

    Route::get('/medios-de-pago', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::get('/medios-de-pago/crear', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
    Route::post('/medios-de-pago', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::get('/medios-de-pago/{paymentMethod}/editar', [PaymentMethodController::class, 'edit'])->name('payment-methods.edit');
    Route::put('/medios-de-pago/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('/medios-de-pago/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::delete('/medios-de-pago', [PaymentMethodController::class, 'bulkDestroy'])->name('payment-methods.bulk-destroy');

    Route::get('/reservas', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/reservas/{appointment}/editar', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/reservas/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    Route::get('/servicios', [ServiceTypeController::class, 'index'])->name('service-types.index');
    Route::get('/servicios/crear', [ServiceTypeController::class, 'create'])->name('service-types.create');
    Route::post('/servicios', [ServiceTypeController::class, 'store'])->name('service-types.store');
    Route::get('/servicios/{serviceType}/editar', [ServiceTypeController::class, 'edit'])->name('service-types.edit');
    Route::put('/servicios/{serviceType}', [ServiceTypeController::class, 'update'])->name('service-types.update');
    Route::delete('/servicios/{serviceType}', [ServiceTypeController::class, 'destroy'])->name('service-types.destroy');
    Route::delete('/servicios', [ServiceTypeController::class, 'bulkDestroy'])->name('service-types.bulk-destroy');

    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventario/crear', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventario', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventario/importar', [InventoryController::class, 'importForm'])->name('inventory.import');
    Route::post('/inventario/importar', [InventoryController::class, 'import'])->name('inventory.import.store');
    Route::get('/inventario/plantilla', [InventoryController::class, 'downloadTemplate'])->name('inventory.template');
    Route::post('/inventario/exportar', [InventoryController::class, 'export'])->name('inventory.export');
    Route::get('/inventario/{inventoryMovement}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::delete('/inventario/{inventoryMovement}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
});
