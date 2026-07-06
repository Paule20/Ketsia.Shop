import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Elements, CardNumberElement, CardExpiryElement, CardCvcElement, useStripe, useElements } from '@stripe/react-stripe-js';
import { useCart } from '../context/CartContext';
import { stripePromise } from '../stripe';
import api from '../api';

const elementStyle = {
  style: {
    base: {
      fontSize: '14px',
      fontFamily: "'Inter', system-ui, sans-serif",
      color: '#0F0D0E',
      '::placeholder': { color: '#B5AFA6' },
    },
    invalid: { color: '#c0392b' },
  },
};

export default function CheckoutPage() {
  return (
    <Elements stripe={stripePromise}>
      <CheckoutForm />
    </Elements>
  );
}

function CheckoutForm() {
  const { cart, cartTotal, setCart } = useCart();
  const navigate = useNavigate();
  const stripe = useStripe();
  const elements = useElements();

  const [shipping, setShipping] = useState('standard');
  const [loading, setLoading] = useState(false);
  const [payError, setPayError] = useState('');
  const [form, setForm] = useState({
    firstName: '', lastName: '', address: '',
    zip: '', city: '', country: 'France', phone: '',
  });

  const shippingCost = shipping === 'express' ? 4.99 : 0;
  const total = cartTotal + shippingCost;

  function update(field, val) {
    setForm((f) => ({ ...f, [field]: val }));
  }

  async function handleConfirm() {
    if (!stripe || !elements) return;
    setPayError('');
    setLoading(true);

    try {
      // 1. Cree le PaymentIntent cote serveur (montant recalcule depuis la base)
      const intentRes = await api.post('/api/stripe/create-payment-intent', {
        items: cart.map((i) => ({ productId: i.product.id, quantity: i.quantity })),
        shippingMethod: shipping,
      });

      // 2. Confirme le paiement avec les elements Stripe (le numero de carte
      //    ne transite jamais par ton backend, uniquement vers Stripe)
      const { error, paymentIntent } = await stripe.confirmCardPayment(intentRes.data.clientSecret, {
        payment_method: {
          card: elements.getElement(CardNumberElement),
          billing_details: {
            name: `${form.firstName} ${form.lastName}`.trim(),
          },
        },
      });

      if (error) {
        setPayError(error.message || 'Le paiement a échoué. Vérifiez vos informations de carte.');
        setLoading(false);
        return;
      }

      if (paymentIntent.status !== 'succeeded') {
        setPayError('Le paiement n\'a pas pu être confirmé.');
        setLoading(false);
        return;
      }

      // 3. Paiement confirme -> on cree la commande
      const shippingAddress = `${form.firstName} ${form.lastName}, ${form.address}, ${form.zip} ${form.city}, ${form.country}`;
      const res = await api.post('/api/orders', {
        items: cart.map((i) => ({ productId: i.product.id, quantity: i.quantity })),
        shippingAddress,
        shippingMethod: shipping,
        total: total.toFixed(2),
        paymentIntentId: paymentIntent.id,
      });

      setCart([]);
      navigate('/confirmation', { state: { orderId: res.data.id, total: total.toFixed(2) } });
    } catch (err) {
      setPayError(err.response?.data?.error || 'Une erreur est survenue. Merci de réessayer.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <header>
        <div className="header-wrap">
          <Link to="/" className="logo">Ketsia<em>.</em>shop</Link>
          <div style={{ fontSize: 12, color: 'var(--mid)', display: 'flex', alignItems: 'center', gap: 8 }}>
            <Link to="/cart" style={{ color: 'var(--mid)' }}>Panier</Link>
            <span style={{ color: 'var(--stone)' }}>›</span>
            <span style={{ color: 'var(--rose)', fontWeight: 600 }}>Livraison</span>
            <span style={{ color: 'var(--stone)' }}>›</span>
            <span>Paiement</span>
          </div>
        </div>
      </header>

      <div className="checkout-wrap">
        {/* ── Colonne gauche : formulaires ── */}
        <div>
          {/* Étape 1 — Adresse */}
          <div className="checkout-card">
            <div className="checkout-card-title">
              <span className="step">1</span> Adresse de livraison
            </div>
            <div className="field-row">
              <div className="field">
                <label>Prénom</label>
                <input placeholder="Ketsia" value={form.firstName} onChange={(e) => update('firstName', e.target.value)} />
              </div>
              <div className="field">
                <label>Nom</label>
                <input placeholder="Mbemba" value={form.lastName} onChange={(e) => update('lastName', e.target.value)} />
              </div>
            </div>
            <div className="field">
              <label>Adresse</label>
              <input placeholder="12 rue des Acacias" value={form.address} onChange={(e) => update('address', e.target.value)} />
            </div>
            <div className="field-row">
              <div className="field">
                <label>Code postal</label>
                <input placeholder="75008" value={form.zip} onChange={(e) => update('zip', e.target.value)} />
              </div>
              <div className="field">
                <label>Ville</label>
                <input placeholder="Paris" value={form.city} onChange={(e) => update('city', e.target.value)} />
              </div>
            </div>
            <div className="field">
              <label>Pays</label>
              <select
                style={{ width: '100%', padding: '12px 14px', border: '1px solid var(--stone)', borderRadius: 3, fontSize: 14, outline: 'none', background: 'var(--sand)', fontFamily: 'inherit' }}
                value={form.country}
                onChange={(e) => update('country', e.target.value)}
              >
                <option>France</option>
                <option>Belgique</option>
                <option>Suisse</option>
                <option>Luxembourg</option>
              </select>
            </div>
            <div className="field">
              <label>Téléphone</label>
              <input placeholder="+33 6 00 00 00 00" value={form.phone} onChange={(e) => update('phone', e.target.value)} />
            </div>
          </div>

          {/* Étape 2 — Mode de livraison */}
          <div className="checkout-card">
            <div className="checkout-card-title">
              <span className="step">2</span> Mode de livraison
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              <label style={{
                display: 'flex', alignItems: 'center', gap: 14, cursor: 'pointer',
                border: `1px solid ${shipping === 'standard' ? 'var(--rose)' : 'var(--stone)'}`,
                background: shipping === 'standard' ? 'var(--rose-lt)' : 'var(--white)',
                padding: '14px 18px', borderRadius: 4,
              }}>
                <input type="radio" name="ship" checked={shipping === 'standard'} onChange={() => setShipping('standard')} style={{ accentColor: 'var(--rose)' }} />
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600, fontSize: 14 }}>Livraison standard</div>
                  <div style={{ fontSize: 12, color: '#777', marginTop: 2 }}>3-5 jours ouvrés</div>
                </div>
                <span style={{ fontWeight: 700, color: 'var(--green)' }}>Gratuite</span>
              </label>

              <label style={{
                display: 'flex', alignItems: 'center', gap: 14, cursor: 'pointer',
                border: `1px solid ${shipping === 'express' ? 'var(--rose)' : 'var(--stone)'}`,
                background: shipping === 'express' ? 'var(--rose-lt)' : 'var(--white)',
                padding: '14px 18px', borderRadius: 4,
              }}>
                <input type="radio" name="ship" checked={shipping === 'express'} onChange={() => setShipping('express')} style={{ accentColor: 'var(--rose)' }} />
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600, fontSize: 14 }}>Livraison express</div>
                  <div style={{ fontSize: 12, color: '#777', marginTop: 2 }}>24-48h</div>
                </div>
                <span style={{ fontWeight: 700 }}>4,99 €</span>
              </label>
            </div>
          </div>

          {/* Étape 3 — Paiement (Stripe uniquement) */}
          <div className="checkout-card">
            <div className="checkout-card-title">
              <span className="step">3</span> Paiement
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 22 }}>
              <div className="pay-tab on" style={{ flex: 'none', padding: '10px 18px' }}>
                <div className="pay-tab-logo" style={{ fontSize: 16, fontWeight: 800, color: '#6772e5', letterSpacing: -1 }}>stripe</div>
              </div>
              <span style={{ fontSize: 12, color: 'var(--mid)' }}>Visa, Mastercard, American Express et plus — toutes les cartes acceptées</span>
            </div>

            <div style={{ background: 'var(--sand)', border: '1px solid var(--stone)', borderRadius: 4, padding: 16, marginBottom: 14 }}>
              <div style={{ fontSize: 11, color: 'var(--mid)', marginBottom: 10 }}>🔒 Formulaire sécurisé Stripe</div>
              <div className="card-fields">
                <div className="stripe-field" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 6 }}>
                  <span style={{ fontSize: 12, color: '#555' }}>Numéro de carte</span>
                  <CardNumberElement options={elementStyle} />
                </div>
                <div className="stripe-field" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 6 }}>
                  <span style={{ fontSize: 12, color: '#555' }}>Date d'expiration</span>
                  <CardExpiryElement options={elementStyle} />
                </div>
                <div className="stripe-field" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 6 }}>
                  <span style={{ fontSize: 12, color: '#555' }}>CVC</span>
                  <CardCvcElement options={elementStyle} />
                </div>
              </div>
            </div>

            {payError && (
              <div className="alert-box alert-err" style={{ marginBottom: 0 }}>{payError}</div>
            )}

            <div className="secure-note">
              🔒 Vos données sont chiffrées par Stripe. Ketsia.shop ne stocke jamais vos informations bancaires.
            </div>
          </div>
        </div>

        {/* ── Colonne droite : récapitulatif ── */}
        <div>
          <div className="cart-summary" style={{ position: 'sticky', top: 90 }}>
            <div className="summary-title">Récapitulatif</div>

            <div style={{ borderBottom: '1px solid var(--stone)', paddingBottom: 16, marginBottom: 16 }}>
              {cart.map((item) => (
                <div key={`${item.product.id}-${item.size ?? 'nosize'}`} style={{ display: 'flex', gap: 12, alignItems: 'center', marginBottom: 12 }}>
                  <img
                    style={{ width: 50, height: 62, objectFit: 'cover', borderRadius: 3, background: 'var(--sand)' }}
                    src={item.product.image}
                    alt={item.product.name}
                  />
                  <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 13, fontWeight: 500 }}>
                      {item.product.name} ×{item.quantity}
                    </div>
                    {item.size && <div style={{ fontSize: 12, color: 'var(--mid)' }}>Taille: {item.size}</div>}
                  </div>
                  <span style={{ fontSize: 13, fontWeight: 600 }}>
                    {(parseFloat(item.product.price) * item.quantity).toFixed(2)} €
                  </span>
                </div>
              ))}
            </div>

            <div className="sum-row">
              <span style={{ color: '#777' }}>Sous-total</span>
              <span>{cartTotal.toFixed(2)} €</span>
            </div>
            <div className="sum-row">
              <span style={{ color: '#777' }}>Livraison</span>
              {shippingCost === 0
                ? <span style={{ color: 'var(--green)' }}>Gratuite</span>
                : <span>{shippingCost.toFixed(2)} €</span>}
            </div>
            <div className="sum-row total">
              <span>Total TTC</span>
              <span>{total.toFixed(2)} €</span>
            </div>

            <button
              className="btn btn-rose btn-md btn-full"
              style={{ marginTop: 20 }}
              onClick={handleConfirm}
              disabled={loading || !stripe}
            >
              {loading ? 'Traitement...' : 'Confirmer et payer'}
            </button>
            <div style={{ marginTop: 14, fontSize: 11, color: 'var(--mid)', textAlign: 'center' }}>
              Paiement sécurisé par Stripe
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
