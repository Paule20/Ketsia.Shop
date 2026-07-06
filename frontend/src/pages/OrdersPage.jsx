import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';

const STATUS = {
  pending:   { label: 'En attente', cls: 'st-paid' },
  paid:      { label: 'Payée',      cls: 'st-paid' },
  shipped:   { label: 'Expédiée',   cls: 'st-ship' },
  delivered: { label: 'Livrée',     cls: 'st-done' },
  cancelled: { label: 'Annulée',    cls: 'st-cancel' },
};

export default function OrdersPage() {
  const [orders, setOrders]   = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(false);

  useEffect(() => {
    api.get('/api/orders')
      .then((r) => { setOrders(r.data); setLoading(false); })
      .catch(() => { setError(true); setLoading(false); });
  }, []);

  if (loading) return <div className="page-loading">Chargement...</div>;

  return (
    <>
      <div className="bc">Accueil / <span>Mes commandes</span></div>

      <div className="orders-wrap">
        <div className="wishlist-title">Mes commandes</div>

        {error ? (
          <p className="wishlist-empty">
            Impossible de charger vos commandes pour le moment. Réessayez plus tard.
          </p>
        ) : orders.length === 0 ? (
          <p className="wishlist-empty">
            Vous n'avez pas encore passé de commande. <Link to="/catalogue">Voir le catalogue</Link>
          </p>
        ) : (
          orders.map((order) => {
            const st = STATUS[order.status] ?? STATUS.pending;
            const date = new Date(order.createdAt).toLocaleDateString('fr-FR', {
              day: 'numeric', month: 'long', year: 'numeric',
            });

            return (
              <div className="order-card" key={order.id}>
                <div className="order-head">
                  <div>
                    <div className="order-id">#{order.id}</div>
                    <div className="order-meta">
                      {date} · {order.items.length} article{order.items.length > 1 ? 's' : ''} · {parseFloat(order.total).toFixed(2)} €
                    </div>
                  </div>
                  <span className={`status ${st.cls}`}>{st.label}</span>
                </div>

                <div className="order-items">
                  {order.items.map((item) => (
                    <img
                      key={item.id}
                      className="order-item-thumb"
                      src={item.product?.imageUrl}
                      alt={item.product?.name}
                    />
                  ))}
                </div>

                <div className="order-footer">
                  <span style={{ fontSize: 13, color: '#777' }}>
                    {order.shippingAddress}
                  </span>
                  <button className="btn btn-outline btn-sm">Détails</button>
                </div>
              </div>
            );
          })
        )}
      </div>
    </>
  );
}

