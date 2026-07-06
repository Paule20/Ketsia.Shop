import { useEffect, useState } from 'react';
import api from '../../api';

const STATUSES = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
const STATUS_LABELS = { pending: 'En attente', paid: 'Payée', shipped: 'Expédiée', delivered: 'Livrée', cancelled: 'Annulée' };

export default function AdminOrdersPage() {
  const [orders, setOrders]     = useState([]);
  const [filter, setFilter]     = useState('');
  const [selected, setSelected] = useState(null); // commande affichée dans la modale

  useEffect(() => {
    api.get('/api/admin/orders').then((r) => setOrders(r.data)).catch(() => {});
  }, []);

  async function handleStatusChange(orderId, status) {
    setOrders((prev) => prev.map((o) => o.id === orderId ? { ...o, status } : o));
    // Garde la modale synchronisée si la commande affichée est celle qu'on modifie
    setSelected((prev) => prev && prev.id === orderId ? { ...prev, status } : prev);
    try {
      await api.patch(`/api/admin/orders/${orderId}/status`, { status });
    } catch {
      alert('Erreur lors de la mise à jour du statut.');
    }
  }

  function closeModal() { setSelected(null); }

  const visible = filter ? orders.filter((o) => o.status === filter) : orders;

  return (
    <>
      <div className="admin-header">
        <div className="admin-title">Commandes</div>
        <select className="sort-select" style={{ fontSize: 12 }} value={filter} onChange={(e) => setFilter(e.target.value)}>
          <option value="">Tous les statuts</option>
          {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
        </select>
      </div>

      <table className="data-table">
        <thead>
          <tr><th>Commande</th><th>Client</th><th>Montant</th><th>Date</th><th>Statut</th><th>Détail</th></tr>
        </thead>
        <tbody>
          {visible.map((o) => (
            <tr key={o.id}>
              <td><strong>#{o.id}</strong></td>
              <td>{o.user?.email}</td>
              <td>{parseFloat(o.total).toFixed(2)} €</td>
              <td style={{ color: 'var(--mid)' }}>{new Date(o.createdAt).toLocaleDateString('fr-FR')}</td>
              <td>
                <select
                  className="status-select"
                  value={o.status}
                  onChange={(e) => handleStatusChange(o.id, e.target.value)}
                >
                  {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
                </select>
              </td>
              <td>
                <button className="btn btn-outline btn-sm" onClick={() => setSelected(o)}>
                  Voir
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {/* ── Modale de détail commande ── */}
      <div className={`modal-bg ${selected ? 'show' : ''}`} onClick={(e) => e.target === e.currentTarget && closeModal()}>
        {selected && (
          <div className="modal" style={{ maxWidth: 560 }}>
            <div className="modal-head">
              <span className="modal-title">Commande #{selected.id}</span>
              <button className="modal-close" onClick={closeModal}>✕</button>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 18, fontSize: 13 }}>
              <div>
                <div style={{ color: 'var(--mid)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 4 }}>Client</div>
                <div>{selected.user?.firstName} {selected.user?.lastName}</div>
                <div style={{ color: 'var(--mid)' }}>{selected.user?.email}</div>
              </div>
              <div>
                <div style={{ color: 'var(--mid)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 4 }}>Date</div>
                <div>{new Date(selected.createdAt).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}</div>
              </div>
              <div>
                <div style={{ color: 'var(--mid)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 4 }}>Adresse de livraison</div>
                <div>{selected.shippingAddress}</div>
              </div>
              <div>
                <div style={{ color: 'var(--mid)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 4 }}>Statut</div>
                <select
                  className="status-select"
                  value={selected.status}
                  onChange={(e) => handleStatusChange(selected.id, e.target.value)}
                >
                  {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
                </select>
              </div>
            </div>

            <div style={{ color: 'var(--mid)', fontSize: 11, textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 8 }}>
              Articles commandés
            </div>
            <table className="data-table" style={{ marginBottom: 16 }}>
              <thead>
                <tr><th>Produit</th><th>Qté</th><th>Prix unitaire</th><th>Sous-total</th></tr>
              </thead>
              <tbody>
                {selected.items?.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        {item.product?.imageUrl && (
                          <img className="prod-thumb-sm" src={item.product.imageUrl} alt={item.product.name} />
                        )}
                        {item.product?.name}
                      </div>
                    </td>
                    <td>{item.quantity}</td>
                    <td>{parseFloat(item.unitPrice).toFixed(2)} €</td>
                    <td>{parseFloat(item.subtotal ?? item.unitPrice * item.quantity).toFixed(2)} €</td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div style={{ display: 'flex', justifyContent: 'flex-end', fontSize: 15, fontWeight: 700 }}>
              Total : {parseFloat(selected.total).toFixed(2)} €
            </div>
          </div>
        )}
      </div>
    </>
  );
}

