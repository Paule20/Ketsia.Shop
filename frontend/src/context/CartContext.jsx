/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useState, useEffect } from 'react';
import api from '../api';
import { isAuthenticated, getUser } from '../auth';

const CartContext = createContext(null);

/**
 * Chaque utilisateur (et les invités non connectés) a sa propre clé de panier
 * dans le localStorage, basée sur son id. Ça permet au panier de :
 *   - survivre à un rafraîchissement de page (F5) ou une fermeture d'onglet,
 *   - ne jamais fuiter d'un compte vers un autre (changer de compte charge
 *     automatiquement le panier propre à ce compte, ou un panier vide s'il
 *     n'en a pas encore).
 */
function cartStorageKey() {
  const user = getUser();
  return `ketsia_cart_${user?.id ?? 'guest'}`;
}

function loadCartFromStorage() {
  try {
    const raw = localStorage.getItem(cartStorageKey());
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export function CartProvider({ children }) {
  const [cart, setCart]               = useState(loadCartFromStorage); // [{ product, quantity, size }]
  const [wishlistIds, setWishlistIds] = useState([]);

  // Recharge le panier + la wishlist du compte courant : au montage (chargement
  // ou refresh de page) ET à chaque login/logout (événement 'auth-changed'
  // envoyé depuis auth.js).
  useEffect(() => {
    function syncWithCurrentUser() {
      setCart(loadCartFromStorage()); // panier propre au compte (ou invité) actuellement connecté

      if (isAuthenticated()) {
        api.get('/api/wishlist')
          .then((r) => setWishlistIds(r.data.map((i) => i.product.id)))
          .catch(() => setWishlistIds([]));
      } else {
        setWishlistIds([]);
      }
    }

    syncWithCurrentUser(); // exécution initiale au montage
    window.addEventListener('auth-changed', syncWithCurrentUser);
    return () => window.removeEventListener('auth-changed', syncWithCurrentUser);
  }, []);

  // Sauvegarde automatique du panier dans le localStorage à chaque modification,
  // sous la clé du compte actuellement connecté (ou "guest").
  useEffect(() => {
    try {
      localStorage.setItem(cartStorageKey(), JSON.stringify(cart));
    } catch {
      // localStorage indisponible (navigation privée, quota dépassé...) :
      // le panier reste fonctionnel en mémoire pour la session en cours.
    }
  }, [cart]);

  // La taille fait partie de la clé d'une ligne panier :
  // le même produit dans 2 tailles différentes = 2 lignes distinctes.
  function addToCart(product, quantity = 1, size = null) {
    setCart((prev) => {
      const existing = prev.find((i) => i.product.id === product.id && i.size === size);
      if (existing) {
        return prev.map((i) =>
          i.product.id === product.id && i.size === size
            ? { ...i, quantity: i.quantity + quantity }
            : i
        );
      }
      return [...prev, { product, quantity, size }];
    });
  }

  function removeFromCart(productId, size = null) {
    setCart((prev) => prev.filter((i) => !(i.product.id === productId && i.size === size)));
  }

  function updateQuantity(productId, quantity, size = null) {
    if (quantity <= 0) { removeFromCart(productId, size); return; }
    setCart((prev) =>
      prev.map((i) =>
        i.product.id === productId && i.size === size ? { ...i, quantity } : i
      )
    );
  }

  async function toggleWishlist(product) {
    if (!isAuthenticated()) { window.location.href = '/login'; return; }
    if (wishlistIds.includes(product.id)) {
      await api.delete(`/api/wishlist/${product.id}`);
      setWishlistIds((prev) => prev.filter((id) => id !== product.id));
    } else {
      await api.post('/api/wishlist', { productId: product.id });
      setWishlistIds((prev) => [...prev, product.id]);
    }
  }

  const cartCount     = cart.reduce((s, i) => s + i.quantity, 0);
  const cartTotal     = cart.reduce((s, i) => s + parseFloat(i.product.price) * i.quantity, 0);
  const wishlistCount = wishlistIds.length;

  return (
    <CartContext.Provider value={{
      cart, cartCount, cartTotal,
      wishlistIds, wishlistCount,
      addToCart, removeFromCart, updateQuantity,
      toggleWishlist,
      setCart,
    }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  return useContext(CartContext);
}


