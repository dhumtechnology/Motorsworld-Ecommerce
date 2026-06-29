<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use App\Models\Comments\Comment;
use App\Models\Products\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductReviewSeeder extends Seeder
{
    /**
     * Seed additional_information and product reviews (comments linked to products).
     * Idempotent: upserts reviews by product_id + user_id.
     */
    public function run(): void
    {
        $this->seedAdditionalInformation();

        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Usuario'))
            ->with('customerProfile')
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('ProductReviewSeeder: no hay clientes. Ejecute UserSeeder primero.');

            return;
        }

        $reviewDefinitions = [
            'MW-ACC-001' => [
                ['stars' => 5, 'comment' => 'Excelente casco, cómodo para trayectos largos y buena ventilación.', 'days_ago' => 12],
                ['stars' => 4, 'comment' => 'Buena relación calidad-precio. La visera podría ser un poco más resistente.', 'days_ago' => 28],
                ['stars' => 5, 'comment' => 'Llegó bien embalado. Cumple con la certificación indicada.', 'days_ago' => 45],
            ],
            'MW-MOTO-001' => [
                ['stars' => 5, 'comment' => 'Ideal para empezar en pista. Motor suave y manejo predecible.', 'days_ago' => 8],
                ['stars' => 4, 'comment' => 'Muy ágil en ciudad. El asiento es firme pero cómodo.', 'days_ago' => 22],
            ],
            'MW-MOTO-004' => [
                ['stars' => 5, 'comment' => 'Gran opción urbana. Bajo consumo y buena aceleración.', 'days_ago' => 15],
                ['stars' => 5, 'comment' => 'La mejor compra en su rango de precio. Mantenimiento económico.', 'days_ago' => 33],
                ['stars' => 4, 'comment' => 'Suspensión correcta para calles irregulares. Recomendada.', 'days_ago' => 50],
            ],
            'MW-LUB-001' => [
                ['stars' => 5, 'comment' => 'Aceite de calidad, el motor suena más silencioso desde el primer cambio.', 'days_ago' => 6],
                ['stars' => 4, 'comment' => 'Buen producto sintético. Envase práctico de 1 L.', 'days_ago' => 19],
            ],
            'MW-NEU-001' => [
                ['stars' => 4, 'comment' => 'Buen agarre en seco y mojado. Instalación sencilla.', 'days_ago' => 11],
            ],
        ];

        foreach ($reviewDefinitions as $sku => $reviews) {
            $product = Product::query()->where('sku', $sku)->first();

            if ($product === null) {
                continue;
            }

            foreach ($reviews as $index => $review) {
                $customer = $customers[$index % $customers->count()];

                Comment::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'user_id' => $customer->id,
                    ],
                    [
                        'comment' => $review['comment'],
                        'stars' => $review['stars'],
                        'created_at' => Carbon::now()->subDays($review['days_ago']),
                    ],
                );
            }
        }
    }

    private function seedAdditionalInformation(): void
    {
        $templates = [
            'MW-MOTO-001' => "Engine: 321 cc parallel twin\nTransmission: 6-speed\nFuel capacity: 14 L\nWeight: 169 kg\nWarranty: 12 months",
            'MW-MOTO-002' => "Engine: 649 cc inline-four\nTransmission: 6-speed\nFuel capacity: 15.4 L\nWeight: 202 kg\nWarranty: 12 months",
            'MW-MOTO-003' => "Engine: 399 cc parallel twin\nTransmission: 6-speed\nFuel capacity: 15 L\nWeight: 168 kg\nWarranty: 12 months",
            'MW-MOTO-004' => "Engine: 199.5 cc single cylinder\nTransmission: 5-speed\nFuel capacity: 12 L\nWeight: 154 kg\nWarranty: 12 months",
            'MW-MOTO-005' => "Engine: 373 cc single cylinder\nTransmission: 6-speed\nFuel capacity: 13.5 L\nWeight: 149 kg\nWarranty: 12 months",
            'MW-ACC-001' => "Material: ABS shell\nCertification: DOT/ECE\nSizes: S–XL\nWeight: 1.4 kg\nIncludes: clear visor",
            'MW-ACC-002' => "Material: textile + leather palm\nSeason: summer\nProtection: knuckle guards\nSizes: S–XXL",
            'MW-LUB-001' => "Type: synthetic 4T\nViscosity: 10W-40\nVolume: 1 L\nAPI: SN\nJASO: MA2",
            'MW-NEU-001' => "Size: 120/70-17\nType: radial\nCompound: mixed street\nLoad/Speed: 58W",
        ];

        foreach ($templates as $sku => $information) {
            Product::query()
                ->where('sku', $sku)
                ->update(['additional_information' => $information]);
        }
    }
}
