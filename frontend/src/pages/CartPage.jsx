import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';
import { useCart } from '../context/CartContext';

export default function CartPage() {
  const { cart, cartTotal, updateQuantity, removeFromCart } = useCart();
  const [promoCode, setPromoCode] = useState('');
  const [promoError, setPromoError] = useState('');
  const [discount, setDiscount] = useState(0);
  const navigate = useNavigate();

  const total = Math.max(cartTotal - discount, 0);

  async function applyPromo() {
    if (!promoCode.trim()) return;
    setPromoError('');
    try {
      const res = await api.post('/api/cart/promo', { code: promoCode.trim() });
      setDiscount(res.data.discount ?? 0);
    } catch {
      setPromoError('Code promo invalide');
      setDiscount(0);
    }
  }

  return (
    <>
      <div className="bc">Accueil / <span>Mon panier</span></div>

      <div className="cart-page-title">
        Mon panier <span className="count">({cart.length} article{cart.length > 1 ? 's' : ''})</span>
      </div>

      {cart.length === 0 ? (
        <div className="cart-empty">
          Votre panier est vide. <Link to="/">Voir le catalogue</Link>
        </div>
      ) : (
        <div className="cart-layout">
          <div className="cart-items">
            <div className="cart-items-header">
              <span>Produit</span>
              <span>Prix unitaire</span>
              <span>Quantité</span>
              <span>Total</span>
              <span></span>
            </div>

            {cart.map((item) => (
              <div className="cart-row" key={`${item.product.id}-${item.size ?? 'nosize'}`}>
                <div className="cart-prod">
                  <img className="cart-thumb" src={item.product.image} alt={item.product.name} />
                  <div>
                    <div className="cart-prod-name">{item.product.name}</div>
                    <div className="cart-prod-meta">
                      {item.size && `Taille: ${item.size}`}
                      {item.size && item.color && ' · '}
                      {item.color && `Couleur: ${item.color}`}
                    </div>
                  </div>
                </div>

                <span style={{ fontSize: 14 }}>{parseFloat(item.product.price).toFixed(2)} €</span>

                <div className="qty-ctrl">
                  <button onClick={() => updateQuantity(item.product.id, item.quantity - 1, item.size)}>−</button>
                  <span>{item.quantity}</span>
                  <button onClick={() => updateQuantity(item.product.id, item.quantity + 1, item.size)}>+</button>
                </div>

                <span style={{ fontWeight: 600 }}>{(parseFloat(item.product.price) * item.quantity).toFixed(2)} €</span>

                <button className="remove-btn" onClick={() => removeFromCart(item.product.id, item.size)}>✕</button>
              </div>
            ))}
          </div>

          <div className="cart-summary">
            <div className="summary-title">Récapitulatif</div>
            <div className="sum-row"><span style={{ color: '#777' }}>Sous-total</span><span>{cartTotal.toFixed(2)} €</span></div>
            <div className="sum-row"><span style={{ color: '#777' }}>Livraison</span><span style={{ color: 'var(--green)' }}>Gratuite</span></div>

            <div className="promo-wrap">
              <input
                placeholder="Code promo"
                value={promoCode}
                onChange={(e) => setPromoCode(e.target.value)}
              />
              <button onClick={applyPromo}>Appliquer</button>
            </div>
            {promoError && <div style={{ fontSize: 12, color: 'var(--rose)', marginTop: -14, marginBottom: 14 }}>{promoError}</div>}

            <div className="sum-row total"><span>Total</span><span>{total.toFixed(2)} €</span></div>

            <button className="btn btn-rose btn-md btn-full" style={{ marginTop: 20 }} onClick={() => navigate('/commande')}>
              Passer la commande
            </button>
            <button className="btn btn-outline btn-sm btn-full" style={{ marginTop: 10 }} onClick={() => navigate('/')}>
              Continuer mes achats
            </button>
            <div style={{ marginTop: 18, fontSize: 11, color: 'var(--mid)', textAlign: 'center' }}>
              🔒 Paiement sécurisé · Retour gratuit 30 jours
            </div>
          </div>
        </div>
      )}
    </>
  );
}