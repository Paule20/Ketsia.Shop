import { Link, useLocation } from 'react-router-dom';

function DevLink({ to, children }) {
  const location = useLocation();
  const [toPath, toQuery] = to.split('?');

  const isActive = (() => {
    if (location.pathname !== toPath) return false;
    if (!toQuery) return !location.search;

    const current = new URLSearchParams(location.search);
    const target = new URLSearchParams(toQuery);
    for (const [key, value] of target) {
      if (current.get(key) !== value) return false;
    }
    return true;
  })();

  return (
    <Link to={to} className={isActive ? 'active' : ''}>
      {children}
    </Link>
  );
}

export default function DevNav() {
  if (import.meta.env.PROD) return null;

  return (
    <div className="devnav">
      <span className="devnav-label">Pages</span>
      <DevLink to="/">🏠 Accueil</DevLink>
      <DevLink to="/catalogue?cat=femme">👩 Femme</DevLink>
      <DevLink to="/catalogue?cat=homme">👨 Homme</DevLink>
      <DevLink to="/catalogue?cat=fille">👧 Fille</DevLink>
      <DevLink to="/catalogue?cat=garcon">👦 Garçon</DevLink>
      <DevLink to="/cart">🛍️ Panier</DevLink>
      <DevLink to="/wishlist">🤍 Wishlist</DevLink>
      <DevLink to="/commande">💳 Paiement</DevLink>
      <DevLink to="/confirmation">✅ Confirmation</DevLink>
      <DevLink to="/orders">📦 Commandes</DevLink>
      <span className="devnav-label" style={{ marginLeft: 8 }}>Auth</span>
      <DevLink to="/register">📝 Inscription</DevLink>
      <DevLink to="/login">🔑 Connexion</DevLink>
    </div>
  );
}
