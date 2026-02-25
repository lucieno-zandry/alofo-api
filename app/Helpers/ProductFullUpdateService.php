<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Models\AppImage;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductFullUpdateService
{
    /**
     * Apply a full desired-state update to a product.
     *
     * The request carries the complete desired state. We diff each layer
     * (images, groups, options, variants) against the database and
     * create/update/delete accordingly.
     *
     * Cascade rule (mirrors frontend behaviour):
     *   removing a variant_group  → deletes its options AND all variants
     *                               that referenced those options
     *   removing a variant_option → deletes variants that referenced it
     */
    public function update(Product $product, Request $request): Product
    {
        return DB::transaction(function () use ($product, $request) {

            // ── 1. Product basics ────────────────────────────────────────────
            $product->update([
                'title'       => $request->input('title'),
                'slug'        => $request->input('slug'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
            ]);

            // ── 2. Images ────────────────────────────────────────────────────
            $this->syncImages($product, $request);

            // ── 3. Variant groups + options ──────────────────────────────────
            // Returns a map of  "GroupName:OptionValue" => VariantOption
            // so we can resolve option_refs when processing variants.
            $optionRefMap = $this->syncVariantGroups($product, $request);

            // ── 4. Variants ──────────────────────────────────────────────────
            $this->syncVariants($product, $request, $optionRefMap);

            return $product->fresh([
                'category',
                'images',
                'variant_groups.variant_options',
                'variants.variant_options',
            ]);
        });
    }

    // ── Images ────────────────────────────────────────────────────────────────

    private function syncImages(Product $product, Request $request): void
    {
        $keepIds = collect($request->input('existing_image_ids', []));
        $createdImagesIds = [];

        // Delete images not in the keep list
        $product->images()
            ->whereNotIn('id', $keepIds)
            ->each(function (Image $image) {
                $image->delete();
            });


        // Upload and attach new images
        foreach ($request->file('images', []) as $file) {
            $image = Functions::store_uploaded_image($file, 'products');
            $createdImagesIds[] = $image->id;
        }

        if (!empty($createdImagesIds)) {
            $product->images()->attach($createdImagesIds);
        }
    }

    // ── Variant groups + options ──────────────────────────────────────────────

    /**
     * Returns map: "GroupName:OptionValue" => VariantOption model
     * Used to resolve option_refs in the variants step.
     */
    private function syncVariantGroups(Product $product, Request $request): array
    {
        $incomingGroups = collect($request->input('variant_groups', []));
        $incomingGroupIds = $incomingGroups->pluck('id')->filter()->values();

        // --- Delete removed groups (cascades to options + variants below) ---
        $removedGroups = $product->variant_groups()
            ->whereNotIn('id', $incomingGroupIds)
            ->get();

        foreach ($removedGroups as $group) {
            $this->deleteGroupWithCascade($group);
        }

        // --- Upsert remaining groups ----------------------------------------
        $optionRefMap = [];

        foreach ($incomingGroups as $groupData) {
            $group = isset($groupData['id'])
                ? VariantGroup::find($groupData['id'])
                : null;

            if ($group) {
                $group->update(['name' => $groupData['name']]);
            } else {
                $group = $product->variant_groups()->create(['name' => $groupData['name']]);
            }

            $incomingOptions   = collect($groupData['options'] ?? []);
            $incomingOptionIds = $incomingOptions->pluck('id')->filter()->values();

            // Delete removed options (cascade to variants)
            $removedOptions = $group->variant_options()
                ->whereNotIn('id', $incomingOptionIds)
                ->get();

            foreach ($removedOptions as $option) {
                $this->deleteOptionWithCascade($option);
            }

            // Upsert remaining options
            foreach ($incomingOptions as $optData) {
                $option = isset($optData['id'])
                    ? VariantOption::find($optData['id'])
                    : null;

                if ($option) {
                    $option->update(['value' => $optData['value']]);
                } else {
                    $option = $group->variant_options()->create(['value' => $optData['value']]);
                }

                $optionRefMap["{$group->name}:{$option->value}"] = $option;
            }
        }

        return $optionRefMap;
    }

    /**
     * Delete a variant group and cascade:
     *  - its options
     *  - all variants that had ALL their option refs inside this group
     */
    private function deleteGroupWithCascade(VariantGroup $group): void
    {
        $optionIds = $group->variant_options()->pluck('id');

        // Variants that reference any option in this group
        $affectedVariantIds = DB::table('variant_variant_option')
            ->whereIn('variant_option_id', $optionIds)
            ->pluck('variant_id')
            ->unique();

        Variant::whereIn('id', $affectedVariantIds)->delete();

        $group->variant_options()->delete();
        $group->delete();
    }

    /**
     * Delete a single option and cascade to variants that referenced it.
     */
    private function deleteOptionWithCascade(VariantOption $option): void
    {
        $variantIds = DB::table('variant_variant_option')
            ->where('variant_option_id', $option->id)
            ->pluck('variant_id');

        Variant::whereIn('id', $variantIds)->delete();

        $option->delete();
    }

    // ── Variants ──────────────────────────────────────────────────────────────

    private function syncVariants(Product $product, Request $request, array $optionRefMap): void
    {
        $incomingVariants = collect($request->input('variants', []));
        $incomingIds      = $incomingVariants->pluck('id')->filter()->values();

        // Delete variants not in the incoming list
        $product->variants()->whereNotIn('id', $incomingIds)->delete();

        foreach ($incomingVariants as $variantData) {
            $variant = isset($variantData['id'])
                ? Variant::find($variantData['id'])
                : null;

            $fields = [
                'sku'           => $variantData['sku'],
                'price'         => $variantData['price'],
                'special_price' => $variantData['special_price'] ?? null,
                'stock'         => $variantData['stock'],
            ];

            if ($variant) {
                $variant->update($fields);
            } else {
                $variant = $product->variants()->create($fields);
            }

            // Resolve option refs ("GroupName:OptionValue") to option IDs
            $optionIds = collect($variantData['option_refs'] ?? [])
                ->map(fn($ref) => $optionRefMap[$ref]->id ?? null)
                ->filter()
                ->values();

            $variant->variant_options()->sync($optionIds);
        }
    }
}
