/* eslint-disable react-hooks/set-state-in-effect */
import { useEffect, useState, useMemo } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../api';
import ProductCard from '../components/ProductCard';

const CAT_LABELS = { femme: 'Femme', homme: 'Homme', fille: 'Fille', garcon: 'Garçon' };

const PER_PAGE = 20;

export default function ProductsPage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading]   = useState(true);
  const [page, setPage]         = useState(1);
  const [searchParams] = useSearchParams();

  const activeCat    = searchParams.get('cat') || '';
  const activeSearch = searchParams.get('q')   || '';

  useEffect(() => {
    setLoading(true);
    setPage(1);
    const params = {};
    if (activeCat) params.category = activeCat;
    api.get('/api/products', { params })
      .then((r) => { setProducts(r.data); setLoading(false); })
      .catch(() => setLoading(false));
  }, [activeCat]);

  const filtered = useMemo(() => {
    return products.filter((p) => {
      if (activeSearch && !p.name.toLowerCase().includes(activeSearch.toLowerCase())) return false;
      return true;
    });
  }, [products, activeSearch]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
  const paginated  = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);

  const heroTitle = activeCat
    ? (CAT_LABELS[activeCat] ?? 'Catalogue')
    : activeSearch ? `"${activeSearch}"` : 'Catalogue';

  function goToPage(n) {
    setPage(n);
    window.scrollTo(0, 0);
  }

  return (
    <>
      {/* ── Hero catégorie ── */}
      <div className="cat-page-hero">
        <div className="cat-page-hero-title">{heroTitle}</div>
        <div className="cat-page-hero-sub">
          {filtered.length} article{filtered.length !== 1 ? 's' : ''}
        </div>
      </div>

      <div className="shop-layout">
        {/* ── Grille produits ── */}
        <main>
          <div className="shop-toolbar">
            <div className="results-count">
              {filtered.length} produit{filtered.length !== 1 ? 's' : ''}
            </div>
          </div>

          {loading ? (
            <div className="page-loading">Chargement...</div>
          ) : paginated.length === 0 ? (
            <div className="page-loading">
              Aucun produit trouvé.<br />
              <Link to="/catalogue" style={{ color: 'var(--rose)', fontWeight: 600 }}>
                Voir tout le catalogue
              </Link>
            </div>
          ) : (
            <div className="prod-grid">
              {paginated.map((p) => (
                <ProductCard
  key={p.id}
  categorySlug={activeCat}
  product={{
    id:       p.id,
    name:     p.name,
    category: p.category?.name ?? p.subCategory ?? '',
    price:    p.price,
    oldPrice: p.oldPrice ?? null,
    image:    p.imageUrl,
    rating:   p.rating,
    ribbon:   p.isNew ? 'Nouveau' : null,
    sizes:    p.sizes ?? null,   // si ton endpoint /api/products renvoie déjà les tailles
  }}
/>
              ))}
            </div>
          )}

          {/* ── Pagination ── */}
          {totalPages > 1 && (
            <div className="pagination">
              {Array.from({ length: totalPages }, (_, i) => (
                <button
                  key={i}
                  className={`page-btn ${page === i + 1 ? 'on' : ''}`}
                  onClick={() => goToPage(i + 1)}
                >
                  {i + 1}
                </button>
              ))}
              {page < totalPages && (
                <button className="page-btn" onClick={() => goToPage(page + 1)}>›</button>
              )}
            </div>
          )}
        </main>
      </div>
    </>
  );
}
