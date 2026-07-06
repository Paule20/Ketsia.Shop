import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';

const HERO_IMAGES = {
  femme:  'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=80',
  homme:  'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=400&q=80',
  enfant: 'https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=400&q=80',
};

const CATEGORY_IMAGES = {
  femme:  'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=500&q=80',
  homme:  'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=500&q=80',
  fille:  'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=500&q=80',
  garcon: 'https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=500&q=80',
};

const FALLBACK_CATEGORIES = [
  { id: 'femme',  slug: 'femme',  name: 'Femme',  count: 248 },
  { id: 'homme',  slug: 'homme',  name: 'Homme',  count: 186 },
  { id: 'fille',  slug: 'fille',  name: 'Fille',  count: 134 },
  { id: 'garcon', slug: 'garcon', name: 'Garçon', count: 119 },
];

export default function HomePage() {
  const [categories, setCategories] = useState([]);
  const [email, setEmail] = useState('');

  useEffect(() => {
    api.get('/api/categories').then((r) => setCategories(r.data)).catch(() => {});
  }, []);

  function handleNewsletter(e) {
    e.preventDefault();
    setEmail('');
    alert('Merci ! Vérifiez votre boîte mail.');
  }

  return (
    <>
      {/* ── HERO ─────────────────────────────────────── */}
      <section className="hero">
        <div className="hero-copy">
          <div className="hero-eyebrow">Mode Femme · Homme · Enfants</div>
          <h1 className="hero-h1">La mode qui<br />vous <em>ressemble</em></h1>
          <p className="hero-sub">
            Vêtements tendance pour toute la famille. Des looks soigneusement
            sélectionnés à prix accessibles.
          </p>
        </div>

        <div className="hero-mosaic">
          <div className="hero-tile">
            <div className="tile-img" style={{ backgroundImage: `url('${HERO_IMAGES.femme}')`, height: '100%' }} />
            <span className="tile-label">Femme</span>
          </div>
          <div className="hero-tile">
            <div className="tile-img" style={{ backgroundImage: `url('${HERO_IMAGES.homme}')`, height: '100%' }} />
            <span className="tile-label">Homme</span>
          </div>
          <div className="hero-tile">
            <div className="tile-img" style={{ backgroundImage: `url('${HERO_IMAGES.enfant}')`, height: '100%' }} />
            <span className="tile-label">Enfants</span>
          </div>
        </div>
      </section>

      {/* ── CATÉGORIES ───────────────────────────────── */}
      <div className="section">
        <div className="section-head">
          <h2 className="section-title">Nos <span>Catégories</span></h2>
        </div>
        <div className="cat-grid">
          {(categories.length > 0 ? categories : FALLBACK_CATEGORIES).map((cat) => (
            <CategoryCard key={cat.id} cat={cat} />
          ))}
        </div>
      </div>

      {/* ── NEWSLETTER ───────────────────────────────── */}
      <div className="newsletter">
        <div className="nl-eyebrow">Newsletter</div>
        <div className="nl-title">Rejoignez la communauté Ketsia</div>
        <div className="nl-sub">
          Recevez −10% sur votre première commande + les tendances en avant-première.
        </div>
        <form className="nl-form" onSubmit={handleNewsletter}>
          <input
            type="email"
            placeholder="votre@email.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
          <button type="submit">S'inscrire</button>
        </form>
      </div>
    </>
  );
}

function CategoryCard({ cat }) {
  const img = CATEGORY_IMAGES[cat.slug] ?? CATEGORY_IMAGES.femme;

  return (
    <Link to={`/catalogue?cat=${cat.slug}`} className="cat-card">
      <div className="cat-img" style={{ backgroundImage: `url('${img}')` }} />
      <div className="cat-veil" />
      <div className="cat-body">
        <div className="cat-name">{cat.name}</div>
        <div className="cat-count">{cat.productsCount ?? cat.count ?? ''} articles</div>
        <span className="cat-cta">Explorer</span>
      </div>
    </Link>
  );
}
