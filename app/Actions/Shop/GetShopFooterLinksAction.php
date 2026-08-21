<?php

namespace App\Actions\Shop;

use App\Models\Products\Category;
use Illuminate\Support\Collection;

class GetShopFooterLinksAction
{
    /**
     * @return array{
     *     clientes: array<string, string>,
     *     productos: array<string, string>,
     *     acerca_de: array<string, string>
     * }
     */
    public function execute(): array
    {
        $categories = Category::query()
            ->whereIn('name', ['Repuestos', 'Accesorios'])
            ->pluck('id', 'name');

        return [
            'clientes' => $this->clientesLinks(),
            'productos' => $this->productosLinks($categories),
            'acerca_de' => $this->acercaDeLinks(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function clientesLinks(): array
    {
        return [
            'Quienes somos' => route('shop.about'),
            'Política de calidad' => route('shop.about').'#politicas-de-calidad',
            'Contáctanos' => route('shop.contact'),
            'Blog' => route('shop.blog.index'),
            'Inicia sesión' => auth()->check() ? route('shop.account.show') : route('login'),
            'Regístrate' => auth()->check() ? route('shop.account.show') : route('register'),
        ];
    }

    /**
     * @param  Collection<string, int>  $categories
     * @return array<string, string>
     */
    private function productosLinks(Collection $categories): array
    {
        return [
            'Motocicletas' => route('shop.catalog', ['section' => 'motos']),
            'Repuestos' => $this->catalogCategoryUrl($categories->get('Repuestos')),
            'Accesorios' => $this->catalogCategoryUrl($categories->get('Accesorios')),
            'Quad Lock' => route('shop.catalog', [
                'section' => 'accesorios',
                'search' => 'Quad Lock',
            ]),
            'Agenda tu servicio' => route('shop.services.index'),
            'Ventas al por mayor' => '#',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function acercaDeLinks(): array
    {
        return [
            'Envíos y devoluciones' => route('shop.shipping-returns'),
            'Preguntas frecuentes' => route('shop.faq'),
            'Formas de pago y promociones' => route('shop.payment-promotions'),
            'Política de privacidad' => route('shop.privacy-policy'),
            'Ayuda' => route('shop.help'),
        ];
    }

    private function catalogCategoryUrl(mixed $categoryId): string
    {
        if (! is_numeric($categoryId)) {
            return route('shop.catalog', ['section' => 'accesorios']);
        }

        return route('shop.catalog', [
            'section' => 'accesorios',
            'categories' => [(int) $categoryId],
        ]);
    }
}
