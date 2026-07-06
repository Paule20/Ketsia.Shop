<?php

namespace App\Entity;

use App\Entity\Wishlist;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente un article vendu sur Ketsia.shop.
 * Appartient à une Category et peut avoir une sous-catégorie textuelle
 * (ex: "Robes", "Chemises") pour un filtrage plus fin.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Prix en euros, stocké avec 2 décimales */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être positif.')]
    private ?string $price = null;

    /** Quantité disponible en stock — décrémentée à chaque commande */
    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le stock ne peut pas être négatif.')]
    private ?int $stock = 0;

    /** Catégorie parente obligatoire (Femme, Homme, Fille, Garçon) */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    private ?Category $category = null;

    /** Sous-catégorie libre (ex: "Robes", "Chemises", "Pantalons") */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subCategory = null;

    /** URL de l'image du produit (hébergée ailleurs, CDN ou service tiers) */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide.")]
    private ?string $imageUrl = null;

    /** Tailles disponibles pour ce produit (ex: ["XS","S","M","L","XL"] ou ["4A","6A","8A"]) */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $sizes = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** Lignes de commande référençant ce produit */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    #[ORM\OneToMany(targetEntity: Wishlist::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $wishlists;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->wishlists = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->stock = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    public function setSubCategory(?string $subCategory): static
    {
        $this->subCategory = $subCategory;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getSizes(): array
    {
        return $this->sizes;
    }

    public function setSizes(array $sizes): static
    {
        $this->sizes = $sizes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    /** @return Collection<int, Wishlist> */
    public function getWishlists(): Collection
    {
        return $this->wishlists;
    }
}
