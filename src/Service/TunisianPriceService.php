<?php

namespace App\Service;

/**
 * Service for Tunisian food prices
 * Prices are in Tunisian Dinars (TND)
 */
class TunisianPriceService
{
    /**
     * Price database for common food items in Tunisia
     * Prices are average market prices in TND
     * Format: price per unit, calories per 100g
     * @var array<string, array{price: float, unit: string, category: string, calories: int}>
     */
    private array $prices = [
        // Fruits & Légumes
        'Pommes' => ['price' => 3.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 52],
        'Bananes' => ['price' => 2.800, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 89],
        'Oranges' => ['price' => 2.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 47],
        'Citrons' => ['price' => 4.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 29],
        'Fraises' => ['price' => 8.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 32],
        'Pastèques' => ['price' => 1.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 30],
        'Dattes' => ['price' => 12.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 277],
        'Raisins' => ['price' => 6.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 69],
        'Pêches' => ['price' => 5.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 39],
        'Abricots' => ['price' => 7.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 48],
        'Figues' => ['price' => 8.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 74],
        'Grenades' => ['price' => 5.000, 'unit' => 'pièce', 'category' => 'Fruits & Légumes', 'calories' => 83],
        
        // Légumes
        'Tomates' => ['price' => 2.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 18],
        'Pommes de terre' => ['price' => 1.800, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 77],
        'Oignons' => ['price' => 1.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 40],
        'Ail' => ['price' => 8.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 149],
        'Carottes' => ['price' => 2.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 41],
        'Navets' => ['price' => 1.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 28],
        'Choux' => ['price' => 1.800, 'unit' => 'pièce', 'category' => 'Fruits & Légumes', 'calories' => 25],
        'Salade' => ['price' => 1.000, 'unit' => 'pièce', 'category' => 'Fruits & Légumes', 'calories' => 15],
        'Concombres' => ['price' => 2.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 15],
        'Poivrons' => ['price' => 4.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 31],
        'Aubergines' => ['price' => 3.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 25],
        'Courgettes' => ['price' => 2.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 17],
        'Haricots verts' => ['price' => 5.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 31],
        'Pois chiches' => ['price' => 4.500, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 164],
        'Lentilles' => ['price' => 4.000, 'unit' => 'kg', 'category' => 'Fruits & Légumes', 'calories' => 116],
        
        // Viandes & Poissons
        'Boeuf' => ['price' => 32.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 250],
        'Veau' => ['price' => 38.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 172],
        'Agneau' => ['price' => 45.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 294],
        'Poulet' => ['price' => 9.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 165],
        'Dinde' => ['price' => 14.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 135],
        'Lapin' => ['price' => 18.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 173],
        'Cordon bleu' => ['price' => 22.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 210],
        'Merguez' => ['price' => 15.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 280],
        'Saucisses' => ['price' => 12.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 301],
        
        // Poissons
        'Poisson rouge' => ['price' => 20.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 97],
        'Thon' => ['price' => 25.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 130],
        'Sardines' => ['price' => 12.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 208],
        'Crevettes' => ['price' => 35.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 99],
        'Calmar' => ['price' => 28.000, 'unit' => 'kg', 'category' => 'Viandes & Poissons', 'calories' => 92],
        
        // Produits laitiers
        'Lait' => ['price' => 1.500, 'unit' => 'L', 'category' => 'Produits laitiers', 'calories' => 42],
        'Yaourt nature' => ['price' => 0.800, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 61],
        'Yaourt fruité' => ['price' => 1.200, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 83],
        'Fromage blanc' => ['price' => 6.000, 'unit' => 'kg', 'category' => 'Produits laitiers', 'calories' => 98],
        'Fromage fondu' => ['price' => 8.000, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 285],
        'K cheddar' => ['price' => 12.000, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 403],
        'Gouda' => ['price' => 15.000, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 356],
        'Beurre' => ['price' => 8.000, 'unit' => '250g', 'category' => 'Produits laitiers', 'calories' => 717],
        'Crème fraîche' => ['price' => 3.500, 'unit' => '200g', 'category' => 'Produits laitiers', 'calories' => 195],
        'Oeufs' => ['price' => 0.300, 'unit' => 'pièce', 'category' => 'Produits laitiers', 'calories' => 155],
        
        // Pain & Céréales
        'Pain français' => ['price' => 0.250, 'unit' => 'pain', 'category' => 'Pain & Céréales', 'calories' => 265],
        'Pain complet' => ['price' => 0.800, 'unit' => 'pain', 'category' => 'Pain & Céréales', 'calories' => 247],
        'Baguette' => ['price' => 0.450, 'unit' => 'pièce', 'category' => 'Pain & Céréales', 'calories' => 274],
        'Farine' => ['price' => 0.900, 'unit' => 'kg', 'category' => 'Pain & Céréales', 'calories' => 364],
        'Semoule' => ['price' => 1.200, 'unit' => 'kg', 'category' => 'Pain & Céréales', 'calories' => 376],
        'Riz' => ['price' => 2.500, 'unit' => 'kg', 'category' => 'Pain & Céréales', 'calories' => 130],
        'Pâtes' => ['price' => 1.800, 'unit' => 'kg', 'category' => 'Pain & Céréales', 'calories' => 131],
        'Couscous' => ['price' => 2.000, 'unit' => 'kg', 'category' => 'Pain & Céréales', 'calories' => 112],
        'Flocons avoine' => ['price' => 4.000, 'unit' => '500g', 'category' => 'Pain & Céréales', 'calories' => 389],
        'Muesli' => ['price' => 5.000, 'unit' => '500g', 'category' => 'Pain & Céréales', 'calories' => 340],
        
        // Boissons
        'Eau minérale' => ['price' => 0.500, 'unit' => '1.5L', 'category' => 'Boissons', 'calories' => 0],
        'Jus d\'orange' => ['price' => 2.500, 'unit' => '1L', 'category' => 'Boissons', 'calories' => 45],
        'Jus de pomme' => ['price' => 2.500, 'unit' => '1L', 'category' => 'Boissons', 'calories' => 46],
        'Soda cola' => ['price' => 1.800, 'unit' => '1.5L', 'category' => 'Boissons', 'calories' => 38],
        'Soda limonade' => ['price' => 1.500, 'unit' => '1L', 'category' => 'Boissons', 'calories' => 33],
        'Café' => ['price' => 15.000, 'unit' => '250g', 'category' => 'Boissons', 'calories' => 2],
        'Thé' => ['price' => 8.000, 'unit' => '100g', 'category' => 'Boissons', 'calories' => 1],
        'Tchaye' => ['price' => 25.000, 'unit' => 'kg', 'category' => 'Boissons', 'calories' => 2],
        'Yerba Mate' => ['price' => 12.000, 'unit' => '500g', 'category' => 'Boissons', 'calories' => 2],
        
        // Huiles & Graisses
        'Huile d\'olive' => ['price' => 15.000, 'unit' => 'L', 'category' => 'Huiles & Graisses', 'calories' => 884],
        'Huile de tournesol' => ['price' => 5.000, 'unit' => 'L', 'category' => 'Huiles & Graisses', 'calories' => 884],
        'Margarine' => ['price' => 4.000, 'unit' => '250g', 'category' => 'Huiles & Graisses', 'calories' => 717],
        
        // Épices & Assaisonnements
        'Sel' => ['price' => 0.500, 'unit' => 'kg', 'category' => 'Épices & Assaisonnements', 'calories' => 0],
        'Poivre' => ['price' => 15.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 251],
        'Paprika' => ['price' => 8.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 282],
        'Cumin' => ['price' => 10.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 375],
        'Curcuma' => ['price' => 12.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 312],
        'Cannelle' => ['price' => 15.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 247],
        'Gingembre' => ['price' => 10.000, 'unit' => '100g', 'category' => 'Épices & Assaisonnements', 'calories' => 80],
        'Sauce tomate' => ['price' => 1.800, 'unit' => '400g', 'category' => 'Épices & Assaisonnements', 'calories' => 29],
        'Concentré tomate' => ['price' => 1.500, 'unit' => '130g', 'category' => 'Épices & Assaisonnements', 'calories' => 82],
        'Vinaigre' => ['price' => 2.000, 'unit' => 'L', 'category' => 'Épices & Assaisonnements', 'calories' => 18],
        
        // Conserves & Appertifs
        'Thon en conserve' => ['price' => 4.500, 'unit' => 'pièce', 'category' => 'Conserves', 'calories' => 116],
        'Sardines en conserve' => ['price' => 3.500, 'unit' => 'pièce', 'category' => 'Conserves', 'calories' => 208],
        'Olives' => ['price' => 6.000, 'unit' => '300g', 'category' => 'Conserves', 'calories' => 145],
        'Capres' => ['price' => 5.000, 'unit' => '100g', 'category' => 'Conserves', 'calories' => 23],
        'Harissa' => ['price' => 2.500, 'unit' => '200g', 'category' => 'Conserves', 'calories' => 40],
        'Pistaches' => ['price' => 25.000, 'unit' => 'kg', 'category' => 'Snacks', 'calories' => 560],
        'Noix' => ['price' => 18.000, 'unit' => 'kg', 'category' => 'Snacks', 'calories' => 654],
        'Amandes' => ['price' => 22.000, 'unit' => 'kg', 'category' => 'Snacks', 'calories' => 579],
        'Cacahuètes' => ['price' => 12.000, 'unit' => 'kg', 'category' => 'Snacks', 'calories' => 567],
        'Raisins secs' => ['price' => 10.000, 'unit' => 'kg', 'category' => 'Snacks', 'calories' => 299],
        
        // Sucre & Confiserie
        'Sucre' => ['price' => 1.800, 'unit' => 'kg', 'category' => 'Sucre & Confiserie', 'calories' => 387],
        'Sucre glace' => ['price' => 3.000, 'unit' => '500g', 'category' => 'Sucre & Confiserie', 'calories' => 389],
        'Miel' => ['price' => 25.000, 'unit' => 'kg', 'category' => 'Sucre & Confiserie', 'calories' => 304],
        'Chocolats' => ['price' => 8.000, 'unit' => 'pièce', 'category' => 'Sucre & Confiserie', 'calories' => 546],
        'Bonbons' => ['price' => 5.000, 'unit' => '200g', 'category' => 'Sucre & Confiserie', 'calories' => 400],
        
        // Produits secs
        'Haricots secs' => ['price' => 4.000, 'unit' => 'kg', 'category' => 'Produits secs', 'calories' => 127],
        'Lentilles secs' => ['price' => 4.500, 'unit' => 'kg', 'category' => 'Produits secs', 'calories' => 116],
        'Pois chiches secs' => ['price' => 4.000, 'unit' => 'kg', 'category' => 'Produits secs', 'calories' => 164],
        'Feves sees' => ['price' => 3.500, 'unit' => 'kg', 'category' => 'Produits secs', 'calories' => 110],
    ];

    /**
     * Get price for an item
     */
    public function getPrice(string $itemName): ?array
    {
        // Exact match
        if (isset($this->prices[$itemName])) {
            return $this->prices[$itemName];
        }
        
        // Case-insensitive search
        $itemNameLower = strtolower($itemName);
        foreach ($this->prices as $name => $data) {
            if (strtolower($name) === $itemNameLower) {
                return $data;
            }
        }
        
        // Partial match
        foreach ($this->prices as $name => $data) {
            if (stripos($name, $itemNameLower) !== false || stripos($itemNameLower, strtolower($name)) !== false) {
                return $data;
            }
        }
        
        return null;
    }

    /**
     * Calculate total price for an item with quantity
     */
    public function calculateTotal(string $itemName, float $quantity): float
    {
        $priceData = $this->getPrice($itemName);
        
        if (!$priceData) {
            return 0;
        }
        
        return $priceData['price'] * $quantity;
    }

    /**
     * Get all items in a category
     */
    public function getItemsByCategory(string $category): array
    {
        $items = [];
        
        foreach ($this->prices as $name => $data) {
            if ($data['category'] === $category) {
                $items[$name] = $data;
            }
        }
        
        return $items;
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $categories = [];
        
        foreach ($this->prices as $data) {
            if (!in_array($data['category'], $categories)) {
                $categories[] = $data['category'];
            }
        }
        
        return $categories;
    }

    /**
     * Get all items with prices
     */
    public function getAllPrices(): array
    {
        return $this->prices;
    }

    /**
     * Format price in Tunisian Dinar format (e.g., 30dt500)
     */
    public function formatPrice(float $price): string
    {
        $dt = floor($price); // Dinars
        $ml = round(($price - $dt) * 1000); // Millimes
        
        return $dt . 'dt' . str_pad((string) $ml, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Format price with decimal (e.g., 30.500 TND)
     */
    public function formatPriceDecimal(float $price): string
    {
        return number_format($price, 3, '000', ' ') . ' TND';
    }

    /**
     * Search items by name
     */
    public function searchItems(string $query): array
    {
        $queryLower = strtolower($query);
        $results = [];
        
        foreach ($this->prices as $name => $data) {
            if (stripos($name, $queryLower) !== false) {
                $results[$name] = $data;
            }
        }
        
        return $results;
    }
}
