import { useLocation, Link } from 'react-router-dom';

export default function SuccessPage() {
  const location = useLocation();
  const { orderId, total } = location.state ?? {};

  return (
    <>
      {/* Header minimal — logo seul */}
      <header>
        <div className="header-wrap">
          <Link to="/" className="logo">Ketsia<em>.</em>shop</Link>
        </div>
      </header>

      <div style={{ padding: '48px 24px 100px' }}>
        <div className="success-page">
          <div className="success-card">
            <div className="success-icon">🎉</div>
            <div className="success-title">Commande confirmée !</div>
            <p className="success-sub">
              Merci pour votre commande. Un email de confirmation a été envoyé.
              Votre colis sera expédié sous 24-48h.
            </p>

            <div style={{ background: 'var(--sand)', borderRadius: 4, padding: '16px 20px', marginBottom: 24, textAlign: 'left' }}>
              {orderId && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13, marginBottom: 6 }}>
                  <span style={{ color: '#777' }}>Numéro de commande</span>
                  <strong>#{orderId}</strong>
                </div>
              )}
              {total && (
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13, marginBottom: 6 }}>
                  <span style={{ color: '#777' }}>Montant payé</span>
                  <strong>{total} €</strong>
                </div>
              )}
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13 }}>
                <span style={{ color: '#777' }}>Livraison estimée</span>
                <strong>3-5 jours ouvrés</strong>
              </div>
            </div>

            <div style={{ display: 'flex', gap: 10, justifyContent: 'center', flexWrap: 'wrap' }}>
              <Link to="/orders" className="btn btn-rose btn-md">Mes commandes</Link>
              <Link to="/" className="btn btn-outline btn-md">Continuer les achats</Link>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
