import { Link } from 'react-router-dom';

export default function Footer() {
  return (
    <footer>
      <div className="footer-top">
        {/* Col 1 — Brand */}
        <div>
          <div className="footer-logo">Ketsia<em>.</em>shop</div>
          <p className="footer-desc">
            Mode tendance pour toute la famille. Livraison rapide, retours gratuits sous 30 jours.
          </p>
          <div className="socials">
            <div className="social">f</div>
            <div className="social">ig</div>
            <div className="social">tk</div>
            <div className="social">p</div>
          </div>
        </div>

        {/* Col 2 — Catégories */}
        <div className="footer-col">
          <h4>Catégories</h4>
          <ul>
            <li><Link to="/catalogue?cat=femme">Femme</Link></li>
            <li><Link to="/catalogue?cat=homme">Homme</Link></li>
            <li><Link to="/catalogue?cat=fille">Enfants — Fille</Link></li>
            <li><Link to="/catalogue?cat=garcon">Enfants — Garçon</Link></li>
          </ul>
        </div>

        {/* Col 3 — Mon compte */}
        <div className="footer-col">
          <h4>Mon compte</h4>
          <ul>
            <li><Link to="/login">Se connecter</Link></li>
            <li><Link to="/register">Créer un compte</Link></li>
            <li><Link to="/orders">Mes commandes</Link></li>
            <li><Link to="/wishlist">Ma wishlist</Link></li>
          </ul>
        </div>

        {/* Col 4 — Aide */}
        <div className="footer-col">
          <h4>Aide</h4>
          <ul>
            <li><a>Livraison</a></li>
            <li><a>Retours &amp; échanges</a></li>
            <li><a>Guide des tailles</a></li>
            <li><a>Contact</a></li>
            <li><a>FAQ</a></li>
          </ul>
        </div>
      </div>

      <div className="footer-bottom">
        <span>© 2025 Ketsia.shop</span>
        <span>Paiement sécurisé 🔒 Stripe · Visa · Mastercard</span>
      </div>
    </footer>
  );
}