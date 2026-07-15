import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { getUser, isAdmin } from '../auth';
import { useCart } from '../context/CartContext';

export default function Navbar() {
  const user     = getUser();
  const navigate = useNavigate();
  const { cartCount, wishlistCount } = useCart();
  const [search, setSearch]         = useState('');
  const [mobileOpen, setMobileOpen] = useState(false);
  const [enfantsOpen, setEnfantsOpen] = useState(false);

  function handleSearch(e) {
    e.preventDefault();
    if (search.trim()) navigate(`/catalogue?q=${encodeURIComponent(search.trim())}`);
    closeMobile();
  }

  function closeMobile() {
    setMobileOpen(false);
    setEnfantsOpen(false);
  }

  return (
    <header>
      <div className="header-wrap">
        {/* Bouton burger — visible seulement < 900px (voir CSS) */}
        <button
          className="navbar-burger"
          onClick={() => setMobileOpen(true)}
          aria-label="Ouvrir le menu"
        >
          ☰
        </button>

        {/* Logo */}
        <Link to="/" className="logo" onClick={closeMobile}>Ketsia<em>.</em>shop</Link>

        {/* Nav desktop (cachée < 900px par CSS, remplacée par le tiroir mobile) */}
        <nav className="main-nav">
          <div className="nav-item">
            <Link to="/catalogue?cat=femme" className="nav-link">Femme</Link>
          </div>
          <div className="nav-item">
            <Link to="/catalogue?cat=homme" className="nav-link">Homme</Link>
          </div>
          <div className="nav-item">
            <div className="nav-link">
              Enfants
              <svg viewBox="0 0 10 6" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path d="M1 1l4 4 4-4" />
              </svg>
            </div>
            <div className="dropdown">
              <Link to="/catalogue?cat=fille">Fille</Link>
              <Link to="/catalogue?cat=garcon">Garçon</Link>
            </div>
          </div>
          <div className="nav-item">
            <Link to="/contact" className="nav-link">Contact</Link>
          </div>
        </nav>

        {/* Actions */}
        <div className="header-actions">
          <form className="search-wrap" onSubmit={handleSearch}>
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="8.5" cy="8.5" r="6.5" />
              <path d="M13 13l4.5 4.5" />
            </svg>
            <input
              type="text"
              placeholder="Rechercher..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </form>

          {isAdmin() && (
            <Link to="/admin" className="nav-link admin-link-desktop" style={{ color: 'var(--rose)', fontWeight: 700 }}>
              ⚙ Admin
            </Link>
          )}

          {user ? (
            <Link to="/profil" className="icon-btn" title="Mon profil">👤</Link>
          ) : (
            <Link to="/login" className="icon-btn" title="Mon compte">👤</Link>
          )}

          <Link to="/wishlist" className="icon-btn" title="Wishlist">
            🤍
            {wishlistCount > 0 && <span className="badge">{wishlistCount}</span>}
          </Link>

          <Link to="/cart" className="icon-btn" title="Panier">
            🛍️
            {cartCount > 0 && <span className="badge">{cartCount}</span>}
          </Link>
        </div>
      </div>

      {/* ── Tiroir mobile ── */}
      {mobileOpen && <div className="navbar-overlay" onClick={closeMobile}></div>}

      <div className={`navbar-drawer ${mobileOpen ? 'open' : ''}`}>
        <div className="navbar-drawer-head">
          <span className="logo">Ketsia<em>.</em>shop</span>
          <button className="navbar-drawer-close" onClick={closeMobile} aria-label="Fermer le menu">✕</button>
        </div>

        <form className="navbar-drawer-search" onSubmit={handleSearch}>
          <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="8.5" cy="8.5" r="6.5" />
            <path d="M13 13l4.5 4.5" />
          </svg>
          <input
            type="text"
            placeholder="Rechercher..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </form>

        <nav className="navbar-drawer-nav">
          <Link to="/catalogue?cat=femme" onClick={closeMobile}>Femme</Link>
          <Link to="/catalogue?cat=homme" onClick={closeMobile}>Homme</Link>

          <button
            type="button"
            className={`navbar-drawer-toggle ${enfantsOpen ? 'open' : ''}`}
            onClick={() => setEnfantsOpen((v) => !v)}
          >
            Enfants
            <svg viewBox="0 0 10 6" fill="none" stroke="currentColor" strokeWidth="1.5">
              <path d="M1 1l4 4 4-4" />
            </svg>
          </button>
          {enfantsOpen && (
            <div className="navbar-drawer-submenu">
              <Link to="/catalogue?cat=fille" onClick={closeMobile}>Fille</Link>
              <Link to="/catalogue?cat=garcon" onClick={closeMobile}>Garçon</Link>
            </div>
          )}

          <Link to="/contact" onClick={closeMobile}>Contact</Link>

          <div className="navbar-drawer-sep"></div>

          {user ? (
            <Link to="/profil" onClick={closeMobile}>👤 Mon profil</Link>
          ) : (
            <Link to="/login" onClick={closeMobile}>👤 Mon compte</Link>
          )}
          <Link to="/wishlist" onClick={closeMobile}>🤍 Wishlist{wishlistCount > 0 ? ` (${wishlistCount})` : ''}</Link>
          <Link to="/cart" onClick={closeMobile}>🛍️ Panier{cartCount > 0 ? ` (${cartCount})` : ''}</Link>

          {isAdmin() && (
            <Link to="/admin" onClick={closeMobile} style={{ color: 'var(--rose)', fontWeight: 700 }}>
              ⚙ Admin
            </Link>
          )}
        </nav>
      </div>
    </header>
  );
}
