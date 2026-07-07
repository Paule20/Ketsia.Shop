import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import ProductCard from './ProductCard';

// On simule les deux contextes utilisés par ProductCard, pour tester
// le composant isolément (sans vrai CartProvider/ToastProvider).
const addToCart      = vi.fn();
const toggleWishlist  = vi.fn();
const showToast       = vi.fn();
let wishlistIds       = [];

vi.mock('../context/CartContext', () => ({
  useCart: () => ({ addToCart, toggleWishlist, wishlistIds }),
}));

vi.mock('../context/ToastContext', () => ({
  useToast: () => ({ showToast }),
}));

const baseProduct = {
  id: 1,
  name: 'Robe fleurie',
  category: 'Femme — Robes',
  price: '39.99',
  oldPrice: null,
  image: 'https://example.com/robe.jpg',
  rating: 4,
  ribbon: null,
  sizes: ['XS', 'S', 'M', 'L', 'XL'],
};

describe('ProductCard', () => {
  beforeEach(() => {
    addToCart.mockClear();
    toggleWishlist.mockClear();
    showToast.mockClear();
    wishlistIds = [];
  });

  it('affiche le nom, la catégorie et le prix du produit', () => {
    render(<ProductCard product={baseProduct} categorySlug="femme" />);

    expect(screen.getByText('Robe fleurie')).toBeInTheDocument();
    expect(screen.getByText('Femme — Robes')).toBeInTheDocument();
    expect(screen.getByText('39.99 €')).toBeInTheDocument();
  });

  it('affiche l\'ancien prix barré si oldPrice est présent', () => {
    render(<ProductCard product={{ ...baseProduct, oldPrice: '49.99' }} categorySlug="femme" />);
    expect(screen.getByText('49.99 €')).toBeInTheDocument();
  });

  it('affiche toutes les tailles du produit, avec la première sélectionnée par défaut', () => {
    render(<ProductCard product={baseProduct} categorySlug="femme" />);

    baseProduct.sizes.forEach((size) => {
      expect(screen.getByText(size)).toBeInTheDocument();
    });
    expect(screen.getByText('XS')).toHaveClass('on');
  });

  it('utilise les tailles par défaut de la catégorie si product.sizes est vide', () => {
    render(<ProductCard product={{ ...baseProduct, sizes: [] }} categorySlug="fille" />);
    expect(screen.getByText('4A')).toBeInTheDocument();
    expect(screen.getByText('12A')).toBeInTheDocument();
  });

  it('change la taille sélectionnée au clic sur une pastille', () => {
    render(<ProductCard product={baseProduct} categorySlug="femme" />);

    fireEvent.click(screen.getByText('M'));

    expect(screen.getByText('M')).toHaveClass('on');
    expect(screen.getByText('XS')).not.toHaveClass('on');
  });

  it('incrémente et décrémente la quantité, avec un minimum de 1', () => {
    const { container } = render(<ProductCard product={baseProduct} categorySlug="femme" />);

    const [decrementBtn, incrementBtn] = container.querySelectorAll('.qty-ctrl button');

    expect(screen.getByText('1')).toBeInTheDocument();

    fireEvent.click(incrementBtn);
    expect(screen.getByText('2')).toBeInTheDocument();

    fireEvent.click(decrementBtn);
    fireEvent.click(decrementBtn); // ne doit pas descendre sous 1
    expect(screen.getByText('1')).toBeInTheDocument();
  });

  it('appelle addToCart avec le produit, la quantité et la taille sélectionnés', () => {
    const { container } = render(<ProductCard product={baseProduct} categorySlug="femme" />);

    fireEvent.click(screen.getByText('L'));
    const incrementBtn = container.querySelectorAll('.qty-ctrl button')[1];
    fireEvent.click(incrementBtn); // quantité = 2

    fireEvent.click(screen.getByText('Ajouter au panier'));

    expect(addToCart).toHaveBeenCalledWith(baseProduct, 2, 'L');
    expect(showToast).toHaveBeenCalledWith(expect.stringContaining('Taille L'));
    expect(showToast).toHaveBeenCalledWith(expect.stringContaining('Qté 2'));
  });

  it('affiche un cœur vide si le produit n\'est pas en wishlist, et appelle toggleWishlist au clic', () => {
    render(<ProductCard product={baseProduct} categorySlug="femme" />);

    const wishBtn = screen.getByText('🤍');
    expect(wishBtn).not.toHaveClass('on');

    fireEvent.click(wishBtn);
    expect(toggleWishlist).toHaveBeenCalledWith(baseProduct);
  });

  it('affiche un cœur plein si le produit est déjà dans la wishlist', () => {
    wishlistIds = [baseProduct.id];
    render(<ProductCard product={baseProduct} categorySlug="femme" />);

    expect(screen.getByText('❤️')).toHaveClass('on');
  });
});
