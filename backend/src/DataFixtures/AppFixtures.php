<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Donnees de test pour l'environnement de developpement.
 * Commande : php bin/console doctrine:fixtures:load
 *
 * Cree :
 * - 1 compte admin      : admin@ketsia.shop / Admin1234!
 * - 1 compte utilisateur: user@ketsia.shop  / User1234!
 * - 4 categories        : Femme, Homme, Fille, Garcon
 * - 100 produits par categorie (400 au total)
 *
 * IMPORTANT : chaque produit est defini dans POOLS[...]['items'] comme une paire
 * explicite ['name' => ..., 'subCat' => ...]. Avant, 'names' et 'subCats' etaient
 * deux tableaux independants cycles avec des modulos differents (% 20 et % 5),
 * ce qui associait des noms a des sous-categories n'ayant aucun rapport
 * (ex: "Trench beige" categorise en "Sous-vetements"). Ici, name et subCat sont
 * indissociables, et l'image est choisie a partir de ce meme subCat -> coherence
 * garantie entre nom, description (= subCat) et photo.
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /** Nombre de produits generes par categorie */
    private const PRODUCTS_PER_CATEGORY = 100;

    /** Tailles disponibles par categorie */
    private const SIZES = [
        'femme'  => ['XS', 'S', 'M', 'L', 'XL'],
        'homme'  => ['XS', 'S', 'M', 'L', 'XL'],
        'fille'  => ['2A', '4A', '6A', '8A', '10A', '12A', '14A'],
        'garcon' => ['2A', '4A', '6A', '8A', '10A', '12A', '14A'],
    ];

    /**
     * Pool de generation par categorie.
     * 'items' : chaque entree est ['name' => ..., 'subCat' => ...] - le lien est fixe,
     * plus de cycle independant entre noms et sous-categories.
     */
    private const POOLS = [
        'femme' => [
            'items' => [
                ['name' => 'Robe fleurie',        'subCat' => 'Robes'],
                ['name' => 'Robe midi',           'subCat' => 'Robes'],
                ['name' => 'Robe portefeuille',   'subCat' => 'Robes'],
                ['name' => 'Robe bustier',         'subCat' => 'Robes'],
                ['name' => 'Robe longue bohème',   'subCat' => 'Robes'],
                ['name' => 'Blouse en soie',       'subCat' => 'Hauts & Blouses'],
                ['name' => 'Chemisier col V',      'subCat' => 'Hauts & Blouses'],
                ['name' => 'Top en lin',           'subCat' => 'Hauts & Blouses'],
                ['name' => 'Cardigan oversize',    'subCat' => 'Hauts & Blouses'],
                ['name' => 'Pull doux',            'subCat' => 'Hauts & Blouses'],
                ['name' => 'Pantalon fluide',      'subCat' => 'Pantalons'],
                ['name' => 'Jean taille haute',    'subCat' => 'Pantalons'],
                ['name' => 'Short en coton',       'subCat' => 'Pantalons'],
                ['name' => 'Jupe plissée',         'subCat' => 'Pantalons'],
                ['name' => 'Legging confort',      'subCat' => 'Pantalons'],
                ['name' => 'Manteau camel',        'subCat' => 'Manteaux'],
                ['name' => 'Veste en jean',        'subCat' => 'Manteaux'],
                ['name' => 'Blazer croisé',        'subCat' => 'Manteaux'],
                ['name' => 'Doudoune légère',      'subCat' => 'Manteaux'],
                ['name' => 'Trench beige',         'subCat' => 'Manteaux'],
            ],
            'priceMin' => 14.99, 'priceMax' => 139.99,
        ],
        'homme' => [
            'items' => [
                ['name' => 'Chemise oxford',        'subCat' => 'Chemises'],
                ['name' => 'Chemise à carreaux',    'subCat' => 'Chemises'],
                ['name' => 'Chemise lin',           'subCat' => 'Chemises'],
                ['name' => 'T-shirt col rond',      'subCat' => 'T-shirts & Polos'],
                ['name' => 'Polo piqué',            'subCat' => 'T-shirts & Polos'],
                ['name' => 'Tee oversize',          'subCat' => 'T-shirts & Polos'],
                ['name' => 'Pull col roulé',        'subCat' => 'T-shirts & Polos'],
                ['name' => 'Gilet sans manches',    'subCat' => 'T-shirts & Polos'],
                ['name' => 'Cardigan laine',        'subCat' => 'T-shirts & Polos'],
                ['name' => 'Jean coupe droite',     'subCat' => 'Pantalons & Jeans'],
                ['name' => 'Pantalon chino',        'subCat' => 'Pantalons & Jeans'],
                ['name' => 'Short cargo',           'subCat' => 'Pantalons & Jeans'],
                ['name' => 'Veste blazer',          'subCat' => 'Vestes'],
                ['name' => 'Bomber léger',          'subCat' => 'Vestes'],
                ['name' => 'Manteau caban',         'subCat' => 'Vestes'],
                ['name' => 'Doudoune légère',       'subCat' => 'Vestes'],
                ['name' => 'Parka technique',       'subCat' => 'Vestes'],
                ['name' => 'Costume deux-pièces',   'subCat' => 'Vestes'],
                ['name' => 'Sweat zippé',           'subCat' => 'Sportswear'],
                ['name' => 'Jogging slim',          'subCat' => 'Sportswear'],
            ],
            'priceMin' => 14.99, 'priceMax' => 129.99,
        ],
        'fille' => [
            'items' => [
                ['name' => 'Robe liberty',       'subCat' => 'Robes'],
                ['name' => 'Robe à smocks',      'subCat' => 'Robes'],
                ['name' => 'Robe de fête',       'subCat' => 'Robes'],
                ['name' => 'Robe chambre',       'subCat' => 'Robes'],
                ['name' => 'Blouse à fleurs',    'subCat' => 'Hauts'],
                ['name' => 'Pull rayé',          'subCat' => 'Hauts'],
                ['name' => 'Pyjama étoiles',     'subCat' => 'Hauts'],
                ['name' => 'Sweat capuche',      'subCat' => 'Hauts'],
                ['name' => 'Cardigan doux',      'subCat' => 'Hauts'],
                ['name' => 'Body col claudine',  'subCat' => 'Hauts'],
                ['name' => 'Jupe tutu',          'subCat' => 'Pantalons'],
                ['name' => 'Pantalon brodé',     'subCat' => 'Pantalons'],
                ['name' => 'Legging imprimé',    'subCat' => 'Pantalons'],
                ['name' => 'Combi-short',        'subCat' => 'Pantalons'],
                ['name' => 'Ensemble été',       'subCat' => 'Pantalons'],
                ['name' => 'Salopette denim',    'subCat' => 'Pantalons'],
                ['name' => 'Veste polaire',      'subCat' => 'Manteaux'],
                ['name' => 'Manteau laine',      'subCat' => 'Manteaux'],
                ['name' => 'Doudoune capuche',   'subCat' => 'Manteaux'],
                ['name' => 'Gilet tricot',       'subCat' => 'Manteaux'],
            ],
            'priceMin' => 12.99, 'priceMax' => 54.99,
        ],
        'garcon' => [
            'items' => [
                ['name' => 'T-shirt graphique',      'subCat' => 'T-shirts'],
                ['name' => 'Chemise à carreaux',     'subCat' => 'T-shirts'],
                ['name' => 'Pull marin',             'subCat' => 'T-shirts'],
                ['name' => 'Tee-shirt coton',        'subCat' => 'T-shirts'],
                ['name' => 'Pyjama dinosaures',      'subCat' => 'T-shirts'],
                ['name' => 'Body col rond',          'subCat' => 'T-shirts'],
                ['name' => 'Polo rayé',              'subCat' => 'T-shirts'],
                ['name' => 'Jean skinny',            'subCat' => 'Pantalons & Shorts'],
                ['name' => 'Short bermuda',          'subCat' => 'Pantalons & Shorts'],
                ['name' => 'Salopette denim',        'subCat' => 'Pantalons & Shorts'],
                ['name' => 'Veste bomber',           'subCat' => 'Vestes'],
                ['name' => 'Manteau duffle',         'subCat' => 'Vestes'],
                ['name' => 'Parka légère',           'subCat' => 'Vestes'],
                ['name' => 'Gilet tricot',           'subCat' => 'Vestes'],
                ['name' => 'Doudoune capuche',       'subCat' => 'Vestes'],
                ['name' => 'Ensemble polo cargo',    'subCat' => 'Sportswear'],
                ['name' => 'Short sport',            'subCat' => 'Sportswear'],
                ['name' => 'Sweat à capuche',        'subCat' => 'Sportswear'],
                ['name' => 'Pantalon jogging',       'subCat' => 'Sportswear'],
                ['name' => 'Survêtement',            'subCat' => 'Sportswear'],
            ],
            'priceMin' => 12.99, 'priceMax' => 59.99,
        ],
    ];

    /**
     * Images choisies PAR SOUS-CATEGORIE (= la description du produit).
     * FEMME est complet (verifie manuellement, photo par photo).
     * Pour completer HOMME / FILLE / GARCON, suis cette methode (2 min/sous-cat) :
     *   1. https://unsplash.com/s/photos/TON-MOT-CLE (en anglais, plus de resultats)
     *   2. Clic sur une photo qui correspond visuellement
     *   3. Clic droit sur l'image en grand -> "Copier l'adresse de l'image"
     *   4. Colle l'URL ici (garde le suffixe "?w=600&q=75" si possible)
     * Tant qu'un tableau est vide, un pool de repli generique est utilise (voir
     * FALLBACK_IMAGES plus bas) -> aucun produit ne se retrouve jamais sans image.
     */
    private const IMAGES_BY_SUBCAT = [
        'femme' => [
            'Robes' => [
                'https://images.unsplash.com/photo-1732711532250-fb5b48586cf1?w=600&q=75',
                'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=75',
            ],
            'Hauts & Blouses' => [
                'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?w=600&q=75',
            ],
            'Pantalons' => [
                'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=600&q=75',
                'https://images.unsplash.com/photo-1590159983013-d4ff5fc71c1d?w=600&q=75',
            ],
            'Manteaux' => [
                'https://images.unsplash.com/photo-1657697722009-4497fd0c2e14?w=600&q=75',
            ],
        ],
        'homme' => [
            'Chemises'          => [], // TODO
            'T-shirts & Polos'  => [], // TODO
            'Pantalons & Jeans' => [], // TODO
            'Vestes'            => [], // TODO
            'Sportswear'        => [], // TODO
        ],
        'fille' => [
            'Robes'     => [], // TODO
            'Hauts'     => [], // TODO
            'Pantalons' => [], // TODO
            'Manteaux'  => [], // TODO
        ],
        'garcon' => [
            'T-shirts'           => [], // TODO
            'Pantalons & Shorts' => [], // TODO
            'Vestes'             => [], // TODO
            'Sportswear'         => [], // TODO
        ],
    ];

    /** Pool de repli generique si une sous-categorie n'a pas encore d'images renseignees ci-dessus */
    private const FALLBACK_IMAGES = [
        'femme'  => [
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=75',
            'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=600&q=75',
            'https://images.unsplash.com/photo-1520975867703-c2e069f7c9d4?w=600&q=75',
            'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&q=75',
        ],
        'homme'  => [
            'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=600&q=75',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=75',
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=600&q=75',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=600&q=75',
        ],
        'fille'  => [
            'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=600&q=75',
            'https://images.unsplash.com/photo-1476234251651-f353703a034d?w=600&q=75',
            'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=600&q=75',
        ],
        'garcon' => [
            'https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=600&q=75',
            'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=600&q=75',
            'https://images.unsplash.com/photo-1472746729193-e9d0b326c3e6?w=600&q=75',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        // --- Utilisateurs ---

        $admin = new User();
        $admin->setEmail('admin@ketsia.shop');
        $admin->setFirstName('Admin');
        $admin->setLastName('Ketsia');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin1234!'));
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@ketsia.shop');
        $user->setFirstName('Marie');
        $user->setLastName('Dupont');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'User1234!'));
        $manager->persist($user);

        // --- Categories ---

        $categories = [];
        $categoryData = [
            ['name' => 'Femme',  'slug' => 'femme'],
            ['name' => 'Homme',  'slug' => 'homme'],
            ['name' => 'Fille',  'slug' => 'fille'],
            ['name' => 'Garcon', 'slug' => 'garcon'],
        ];

        foreach ($categoryData as $data) {
            $cat = new Category();
            $cat->setName($data['name']);
            $cat->setSlug($data['slug']);
            $manager->persist($cat);
            $categories[$data['slug']] = $cat;
        }

        // --- Produits : generation de 100 par categorie ---

        foreach (self::POOLS as $slug => $pool) {
            $items = $pool['items'];
            $sizes = self::SIZES[$slug];

            for ($i = 0; $i < self::PRODUCTS_PER_CATEGORY; $i++) {
                $item     = $items[$i % count($items)];
                $variant  = intdiv($i, count($items));
                $name     = $variant > 0 ? sprintf('%s %d', $item['name'], $variant + 1) : $item['name'];
                $subCat   = $item['subCat'];

                $price = round(
                    $pool['priceMin'] + fmod($i * 3.7 + $i * $i * 0.05, $pool['priceMax'] - $pool['priceMin']),
                    2
                );

                $product = new Product();
                $product->setName($name);
                $product->setDescription(sprintf(
                    '%s. Pièce confortable et intemporelle, pensée pour un usage quotidien.',
                    $subCat
                ));
                $product->setPrice((string) $price);
                $product->setStock(random_int(5, 80));
                $product->setCategory($categories[$slug]);
                $product->setSubCategory($subCat);
                $product->setImageUrl($this->pickImage($slug, $subCat, $i));
                $product->setSizes($sizes);

                $manager->persist($product);
            }
        }

        $manager->flush();
    }

    /**
     * Choisit une image cohérente avec la sous-catégorie (= description) du produit.
     * Retombe sur le pool générique de la catégorie si la sous-catégorie n'a pas
     * encore d'images renseignées dans IMAGES_BY_SUBCAT.
     */
    private function pickImage(string $slug, string $subCat, int $index): string
    {
        $pool = self::IMAGES_BY_SUBCAT[$slug][$subCat] ?? [];

        if (empty($pool)) {
            $pool = self::FALLBACK_IMAGES[$slug];
        }

        return $pool[$index % count($pool)];
    }
}

