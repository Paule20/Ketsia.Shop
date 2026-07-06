import { useState } from 'react';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';

// Tailles par défaut si l'API ne renvoie pas product.sizes
const DEFAULT_SIZES = {
  femme:  ['XS', 'S', 'M', 'L', 'XL'],
  homme:  ['XS', 'S', 'M', 'L', 'XL'],
  fille:  ['4A', '6A', '8A', '10A', '12A'],
  garcon: ['4A', '6A', '8A', '10A', '12A'],
};

export default function ProductCard({ product, categorySlug }) {
  const { addToCart, toggleWishlist, wishlistIds } = useCart();
  const { showToast } = useToast();

  const sizes = product.sizes?.length
    ? product.sizes
    : (DEFAULT_SIZES[categorySlug] ?? DEFAULT_SIZES.femme);

  const [selectedSize, setSelectedSize] = useState(sizes[0]);
  const [quantity, setQuantity] = useState(1);

  const isWished = wishlistIds?.includes(product.id);

  function changeQty(delta) {
    setQuantity((q) => Math.max(1, q + delta));
  }

  function handleAddToCart() {
    addToCart(product, quantity, selectedSize);
    showToast(`Article ajouté au panier · Taille ${selectedSize} · Qté ${quantity}`);
  }

  return (
    <div className="prod-card">
      <div className="prod-img-wrap">
        <div className="prod-img-bg" style={{ backgroundImage: `url('${product.image}')` }} />
        {product.ribbon && <span className="prod-ribbon">{product.ribbon}</span>}
        <button
          type="button"
          className={`wish-btn ${isWished ? 'on' : ''}`}
          onClick={() => toggleWishlist(product)}
        >
          {isWished ? '❤️' : '🤍'}
        </button>
      </div>

      <div className="prod-body">
        <div className="prod-cat">{product.category}</div>
        <div className="prod-name">{product.name}</div>

        <div className="prod-price">
          <span className="price">{parseFloat(product.price).toFixed(2)} €</span>
          {product.oldPrice && (
            <span className="price-old">{parseFloat(product.oldPrice).toFixed(2)} €</span>
          )}
        </div>

        {product.rating != null && (
          <div className="stars">
            {'★'.repeat(Math.round(product.rating))}
            {'☆'.repeat(5 - Math.round(product.rating))}
          </div>
        )}

        <div className="prod-size-label">Taille</div>
        <div className="size-pills">
          {sizes.map((s) => (
            <button
              type="button"
              key={s}
              className={`size-pill ${selectedSize === s ? 'on' : ''}`}
              onClick={() => setSelectedSize(s)}
            >
              {s}
            </button>
          ))}
        </div>

        <div className="prod-qty-row">
          <div className="qty-ctrl">
            <button type="button" onClick={() => changeQty(-1)}>−</button>
            <span>{quantity}</span>
            <button type="button" onClick={() => changeQty(1)}>+</button>
          </div>
        </div>

        <button className="add-cart-btn" onClick={handleAddToCart}>
          Ajouter au panier
        </button>
      </div>
    </div>
  );
}