import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';
import ProductCard from '../components/ProductCard';
import { useCart } from '../context/CartContext';

export default function WishlistPage() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const { wishlistIds } = useCart();

  useEffect(() => {
    api.get('/api/wishlist')
      .then((res) => {
        setItems(res.data);
        setLoading(false);
      })
      .catch(() => {
        setError(true);
        setLoading(false);
      });
  }, []);

  const visibleItems = items.filter((item) => wishlistIds.includes(item.product.id));

  return (
    <>
      <div className="bc">Accueil / <span>Ma wishlist</span></div>

      <div className="wishlist-page">
        <div className="wishlist-title">
          Ma wishlist <span className="count">({visibleItems.length} article{visibleItems.length > 1 ? 's' : ''})</span>
        </div>

        {loading ? (
          <div className="page-loading">Chargement...</div>
        ) : error ? (
          <p className="wishlist-empty">
            Connectez-vous pour voir votre wishlist. <Link to="/login">Se connecter</Link>
          </p>
        ) : visibleItems.length === 0 ? (
          <p className="wishlist-empty">
            Votre wishlist est vide. <Link to="/">Voir le catalogue</Link>
          </p>
        ) : (
          <div className="prod-grid">
            {visibleItems.map((item) => (
              <ProductCard
                key={item.id}
                categorySlug={item.product.category?.slug}
                product={{
                  id:       item.product.id,
                  name:     item.product.name,
                  category: item.product.category?.name ?? item.product.subCategory ?? '',
                  price:    item.product.price,
                  image:    item.product.imageUrl,
                  sizes:    item.product.sizes ?? null,
                }}
              />
            ))}
          </div>
        )}
      </div>
    </>
  );
}
