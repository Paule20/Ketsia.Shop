import { useState } from 'react';
import { NavLink, Outlet, Link } from 'react-router-dom';

const linkClass = ({ isActive }) => `admin-nav-item ${isActive ? 'on' : ''}`;

export default function AdminLayout() {
  const [menuOpen, setMenuOpen] = useState(false);

  function closeMenu() { setMenuOpen(false); }

  return (
    <div className="admin-layout">
      {/* ── Barre mobile : visible uniquement < 900px (voir CSS) ── */}
      <div className="admin-mobile-bar">
        <button className="admin-burger" onClick={() => setMenuOpen(true)} aria-label="Ouvrir le menu">
          ☰
        </button>
        <div className="admin-mobile-brand">Ketsia<em>.</em>shop</div>
      </div>

      {/* ── Fond assombri quand le menu mobile est ouvert ── */}
      {menuOpen && <div className="admin-sidebar-overlay" onClick={closeMenu}></div>}

      <aside className={`admin-sidebar ${menuOpen ? 'open' : ''}`}>
        <button className="admin-sidebar-close" onClick={closeMenu} aria-label="Fermer le menu">✕</button>
        <div className="admin-brand">
          Ketsia<em>.</em>shop
          <small>Administration</small>
        </div>
        <NavLink to="/admin" end className={linkClass} onClick={closeMenu}><span>📊</span> Tableau de bord</NavLink>
        <NavLink to="/admin/products" className={linkClass} onClick={closeMenu}><span>👗</span> Produits</NavLink>
        <NavLink to="/admin/orders" className={linkClass} onClick={closeMenu}><span>📦</span> Commandes</NavLink>
        <NavLink to="/admin/users" className={linkClass} onClick={closeMenu}><span>👥</span> Utilisateurs</NavLink>
        <NavLink to="/admin/contact" className={linkClass} onClick={closeMenu}><span>✉️</span> Messages</NavLink>
        <Link to="/" className="admin-nav-item" style={{ marginTop: 40 }} onClick={closeMenu}><span>↩</span> Retour au site</Link>
      </aside>

      <div className="admin-content">
        <Outlet />
      </div>
    </div>
  );
}

