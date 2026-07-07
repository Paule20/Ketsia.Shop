import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { CartProvider, useCart } from './CartContext';

// Mock de l'API : pas de vrais appels réseau dans un test unitaire.
vi.mock('../api', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: [] })),
    post: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
  },
}));

// Mock de l'authentification : contrôlé test par test via authState.
let authState = { authenticated: false, user: null };
vi.mock('../auth', () => ({
  isAuthenticated: () => authState.authenticated,
  getUser: () => authState.user,
}));

import api from '../api';

function wrapper({ children }) {
  return <CartProvider>{children}</CartProvider>;
}

const product = { id: 1, name: 'Robe fleurie', price: '39.99' };
const product2 = { id: 2, name: 'Chemise oxford', price: '10.00' };

describe('CartContext', () => {
  beforeEach(() => {
    localStorage.clear();
    authState = { authenticated: false, user: null };
    vi.clearAllMocks();
  });

  it('démarre avec un panier vide si rien en localStorage', () => {
    const { result } = renderHook(() => useCart(), { wrapper });
    expect(result.current.cart).toEqual([]);
    expect(result.current.cartCount).toBe(0);
    expect(result.current.cartTotal).toBe(0);
  });

  it('ajoute un nouvel article au panier', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 2, 'M'));

    expect(result.current.cart).toEqual([{ product, quantity: 2, size: 'M' }]);
    expect(result.current.cartCount).toBe(2);
    expect(result.current.cartTotal).toBeCloseTo(79.98);
  });

  it('cumule la quantité si le même produit et la même taille sont ajoutés à nouveau', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));
    act(() => result.current.addToCart(product, 2, 'M'));

    expect(result.current.cart).toHaveLength(1);
    expect(result.current.cart[0].quantity).toBe(3);
  });

  it('crée une ligne distincte pour le même produit dans une taille différente', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));
    act(() => result.current.addToCart(product, 1, 'L'));

    expect(result.current.cart).toHaveLength(2);
    expect(result.current.cartCount).toBe(2);
  });

  it('met à jour la quantité d\'une ligne existante', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));
    act(() => result.current.updateQuantity(product.id, 5, 'M'));

    expect(result.current.cart[0].quantity).toBe(5);
  });

  it('supprime la ligne si updateQuantity reçoit 0 ou moins', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));
    act(() => result.current.updateQuantity(product.id, 0, 'M'));

    expect(result.current.cart).toEqual([]);
  });

  it('supprime uniquement la ligne correspondant au produit ET à la taille', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));
    act(() => result.current.addToCart(product, 1, 'L'));
    act(() => result.current.removeFromCart(product.id, 'M'));

    expect(result.current.cart).toEqual([{ product, quantity: 1, size: 'L' }]);
  });

  it('calcule correctement cartCount et cartTotal avec plusieurs lignes', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 2, 'M'));   // 2 x 39.99
    act(() => result.current.addToCart(product2, 3, 'L'));  // 3 x 10.00

    expect(result.current.cartCount).toBe(5);
    expect(result.current.cartTotal).toBeCloseTo(109.98);
  });

  it('sauvegarde le panier dans le localStorage sous la clé "guest" si non connecté', () => {
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));

    const stored = JSON.parse(localStorage.getItem('ketsia_cart_guest'));
    expect(stored).toHaveLength(1);
    expect(stored[0].product.id).toBe(1);
  });

  it('sauvegarde le panier sous la clé du compte connecté', () => {
    authState = { authenticated: true, user: { id: 42 } };
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => result.current.addToCart(product, 1, 'M'));

    expect(localStorage.getItem('ketsia_cart_42')).not.toBeNull();
    expect(localStorage.getItem('ketsia_cart_guest')).toBeNull();
  });

  it('recharge le panier propre au compte lors d\'un événement auth-changed', () => {
    // Panier pré-existant pour l'utilisateur 42, différent du panier invité
    localStorage.setItem('ketsia_cart_42', JSON.stringify([{ product: product2, quantity: 7, size: 'S' }]));

    const { result } = renderHook(() => useCart(), { wrapper });
    expect(result.current.cart).toEqual([]); // invité au départ

    authState = { authenticated: true, user: { id: 42 } };
    act(() => window.dispatchEvent(new Event('auth-changed')));

    expect(result.current.cart[0].quantity).toBe(7);
  });

  it('toggleWishlist : ajoute puis retire un produit pour un utilisateur connecté', async () => {
    authState = { authenticated: true, user: { id: 42 } };
    const { result } = renderHook(() => useCart(), { wrapper });

    await act(async () => result.current.toggleWishlist(product));
    expect(api.post).toHaveBeenCalledWith('/api/wishlist', { productId: product.id });
    expect(result.current.wishlistIds).toContain(product.id);

    await act(async () => result.current.toggleWishlist(product));
    expect(api.delete).toHaveBeenCalledWith(`/api/wishlist/${product.id}`);
    expect(result.current.wishlistIds).not.toContain(product.id);
  });
});
