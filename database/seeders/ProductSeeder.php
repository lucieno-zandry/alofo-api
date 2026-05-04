<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------
        // Catégories – Vanille & Épices de Madagascar
        // -----------------------------
        $categories = [
            'Vanille',
            'Épices',
            'Huiles Essentielles',
            'Plantes & Racines séchées',
            'Coffrets & Produits transformés'
        ];

        $categoryModels = [];

        foreach ($categories as $title) {
            $categoryModels[$title] = Category::create([
                'title' => $title,
            ]);
        }

        // -----------------------------
        // Catalogue de produits Madagascar (30)
        // -----------------------------
        $products = [
            // Vanille
            ['Gousses de vanille Bourbon de Madagascar – Grade A', 'Vanille'],
            ['Gousses de vanille Bourbon – Grade B', 'Vanille'],
            ['Poudre de vanille pure de Madagascar', 'Vanille'],
            ['Extrait de vanille bio (double concentré)', 'Vanille'],
            ['Pâte de vanille Bourbon', 'Vanille'],
            ['Sucre vanillé artisanal', 'Vanille'],
            ['Gousses de vanille éclatées (défauts)', 'Vanille'],
            ['Caviar de vanille (graines)', 'Vanille'],

            // Épices
            ['Clous de girofle entiers – Madagascar', 'Épices'],
            ['Poivre sauvage Voatsiperifery', 'Épices'],
            ['Poivre noir de Sakay', 'Épices'],
            ['Bâtons de cannelle de Ceylan', 'Épices'],
            ['Cannelle en poudre de Madagascar', 'Épices'],
            ['Badiane étoilée entière', 'Épices'],
            ['Poivre vert en saumure', 'Épices'],
            ['Gingembre séché en rondelles', 'Épices'],
            ['Curcuma en poudre (sauvage)', 'Épices'],
            ['Muscade entière', 'Épices'],

            // Huiles essentielles
            ['Huile essentielle d’Ylang-Ylang Extra', 'Huiles Essentielles'],
            ['Huile essentielle de Ravintsara', 'Huiles Essentielles'],
            ['Huile essentielle de Niaouli', 'Huiles Essentielles'],
            ['Huile essentielle de girofle', 'Huiles Essentielles'],
            ['Huile essentielle de géranium', 'Huiles Essentielles'],

            // Plantes & racines séchées
            ['Racines de vétiver séchées', 'Plantes & Racines séchées'],
            ['Citronnelle séchée', 'Plantes & Racines séchées'],
            ['Poudre de feuilles de moringa', 'Plantes & Racines séchées'],

            // Coffrets
            ['Coffret découverte épices (vanille + girofle + poivre)', 'Coffrets & Produits transformés'],
            ['Échantillonneur de vanille (5 gousses)', 'Coffrets & Produits transformés'],
            ['Kit découverte huiles essentielles (5x5ml)', 'Coffrets & Produits transformés']
        ];

        foreach ($products as [$title, $categoryName]) {
            $product = Product::create([
                'title' => $title,
                'slug' => Str::slug($title) . "-" . Str::uuid(),
                'description' => "Produit authentique : {$title}. Issu de l'agriculture durable à Madagascar. Qualité premium, arôme riche et saveur exceptionnelle – parfait pour la cuisine, la pâtisserie ou le bien-être.",
                'category_id' => $categoryModels[$categoryName]->id,
            ]);

            // -----------------------------
            // Logique de variantes (réaliste pour Madagascar)
            // -----------------------------

            if ($categoryName === 'Vanille') {
                $this->creerVariantesVanille($product);
            }

            if ($categoryName === 'Épices') {
                $this->creerVariantesEpices($product);
            }

            if ($categoryName === 'Huiles Essentielles') {
                $this->creerVariantesHuilesEssentielles($product);
            }

            if ($categoryName === 'Plantes & Racines séchées') {
                $this->creerVariantesPlantesRacines($product);
            }

            if ($categoryName === 'Coffrets & Produits transformés') {
                $this->creerVariantesCoffrets($product);
            }
        }
    }

    private function creerVariantesVanille(Product $product): void
    {
        $titre = $product->title;
        // Gousses, caviar, ou gousses éclatées : grade + poids
        if (str_contains($titre, 'Gousses') || str_contains($titre, 'Caviar') || str_contains($titre, 'éclatées')) {
            $groupeGrade = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Qualité'
            ]);
            $groupePoids = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Poids'
            ]);

            $grades = ['Bourbon Supérieur', 'Qualité extraction'];
            $poids = ['50g (3-4 gousses)', '100g (7-8 gousses)', '250g (18-20 gousses)'];

            $optionsGrade = collect($grades)->map(fn($g) => VariantOption::create([
                'variant_group_id' => $groupeGrade->id,
                'value' => $g
            ]));

            $optionsPoids = collect($poids)->map(fn($p) => VariantOption::create([
                'variant_group_id' => $groupePoids->id,
                'value' => $p
            ]));

            foreach ($optionsGrade as $grade) {
                foreach ($optionsPoids as $poids) {
                    $variant = Variant::create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(Str::slug($product->title . '-' . $grade->value . '-' . $poids->value)),
                        'price' => rand(12, 100), // euros
                        'stock' => rand(10, 80)
                    ]);
                    $variant->variant_options()->sync([$grade->id, $poids->id]);
                }
            }
        } else {
            // Poudre, extrait, pâte, sucre – variante simple taille/poids
            $groupeTaille = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Format'
            ]);

            $tailles = match (true) {
                str_contains($titre, 'Extrait') => ['50ml', '100ml', '200ml'],
                str_contains($titre, 'Poudre') => ['50g', '100g', '500g'],
                str_contains($titre, 'Pâte') => ['60g', '120g'],
                str_contains($titre, 'Sucre') => ['200g', '500g'],
                default => ['Petit', 'Moyen', 'Grand']
            };

            $optionsTaille = collect($tailles)->map(fn($t) => VariantOption::create([
                'variant_group_id' => $groupeTaille->id,
                'value' => $t
            ]));

            foreach ($optionsTaille as $taille) {
                $variant = Variant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::slug($product->title . '-' . $taille->value)),
                    'price' => rand(6, 40),
                    'stock' => rand(20, 150)
                ]);
                $variant->variant_options()->sync([$taille->id]);
            }
        }
    }

    private function creerVariantesEpices(Product $product): void
    {
        $titresAvecForme = [
            'Clous de girofle entiers – Madagascar',
            'Poivre noir de Sakay',
            'Bâtons de cannelle de Ceylan',
            'Badiane étoilée entière',
            'Poivre vert en saumure',
            'Gingembre séché en rondelles',
            'Muscade entière'
        ];

        if (in_array($product->title, $titresAvecForme)) {
            $groupeForme = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Forme'
            ]);
            $groupePoids = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Poids'
            ]);

            $formes = ['Entier', 'Moulu'];
            if (str_contains($product->title, 'bâtons') || str_contains($product->title, 'Badiane') || str_contains($product->title, 'Muscade')) {
                $formes = ['Entier'];
            }
            if (str_contains($product->title, 'rondelles') || str_contains($product->title, 'saumure')) {
                $formes = ['Entier'];
            }

            $poids = ['50g', '100g', '250g'];

            $optionsForme = collect($formes)->map(fn($f) => VariantOption::create([
                'variant_group_id' => $groupeForme->id,
                'value' => $f
            ]));

            $optionsPoids = collect($poids)->map(fn($p) => VariantOption::create([
                'variant_group_id' => $groupePoids->id,
                'value' => $p
            ]));

            foreach ($optionsForme as $forme) {
                foreach ($optionsPoids as $poids) {
                    $variant = Variant::create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(Str::slug($product->title . '-' . $forme->value . '-' . $poids->value)),
                        'price' => rand(4, 30),
                        'stock' => rand(15, 120)
                    ]);
                    $variant->variant_options()->sync([$forme->id, $poids->id]);
                }
            }
        } else {
            // Poudre simple (cannelle en poudre, curcuma)
            $groupePoids = VariantGroup::create([
                'product_id' => $product->id,
                'name' => 'Poids'
            ]);

            $poids = ['100g', '250g', '500g'];
            $optionsPoids = collect($poids)->map(fn($p) => VariantOption::create([
                'variant_group_id' => $groupePoids->id,
                'value' => $p
            ]));

            foreach ($optionsPoids as $poids) {
                $variant = Variant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::slug($product->title . '-' . $poids->value)),
                    'price' => rand(5, 25),
                    'stock' => rand(25, 200)
                ]);
                $variant->variant_options()->sync([$poids->id]);
            }
        }
    }

    private function creerVariantesHuilesEssentielles(Product $product): void
    {
        $groupeVolume = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Volume'
        ]);

        $volumes = ['10ml', '30ml', '50ml', '100ml'];
        $optionsVolume = collect($volumes)->map(fn($v) => VariantOption::create([
            'variant_group_id' => $groupeVolume->id,
            'value' => $v
        ]));

        foreach ($optionsVolume as $volume) {
            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(Str::slug($product->title . '-' . $volume->value)),
                'price' => rand(10, 60),
                'stock' => rand(10, 60)
            ]);
            $variant->variant_options()->sync([$volume->id]);
        }
    }

    private function creerVariantesPlantesRacines(Product $product): void
    {
        $groupePoids = VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Poids'
        ]);

        $poids = match (true) {
            str_contains($product->title, 'vétiver') => ['100g', '250g', '500g'],
            str_contains($product->title, 'Citronnelle') => ['50g', '100g', '200g'],
            default => ['100g', '250g']
        };

        $optionsPoids = collect($poids)->map(fn($p) => VariantOption::create([
            'variant_group_id' => $groupePoids->id,
            'value' => $p
        ]));

        foreach ($optionsPoids as $poids) {
            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(Str::slug($product->title . '-' . $poids->value)),
                'price' => rand(6, 28),
                'stock' => rand(20, 90)
            ]);
            $variant->variant_options()->sync([$poids->id]);
        }
    }

    private function creerVariantesCoffrets(Product $product): void
    {
        // Coffrets : pas de variante (article unique) ou parfois un format unique
        Variant::create([
            'product_id' => $product->id,
            'sku' => strtoupper(Str::slug($product->title)) . '-BOX',
            'price' => rand(25, 55),
            'stock' => rand(5, 40)
        ]);
    }
}
