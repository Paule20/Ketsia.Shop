import { useEffect, useState } from 'react';
import api from '../../api';

export default function AdminDashboard() {
  const [stats, setStats] = useState(null);
  const [recentOrders, setRecentOrders] = useState([]);

  useEffect(() => {
    api.get('/api/admin/stats').then((r) => setStats(r.data)).catch(() => {});
    api.get('/api/admin/orders').then((r) => setRecentOrders(r.data.slice(0, 5))).catch(() => {});
  }, []);

  const today = new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });

  return (
    <>
      <div className="admin-header">
        <div className="admin-title">Tableau de bord</div>
        <span style={{ fontSize: 12, color: 'var(--mid)' }}>{today}</span>
      </div>

      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-label">Commandes ce mois</div>
          <div className="stat-value rose">{stats?.ordersThisMonth ?? '—'}</div>
          <div className="stat-delta">↑ {stats?.ordersDelta ?? ''}</div>
        </div>
        <div className="stat-card">
          <div className="stat-label">Chiffre d'affaires</div>
          <div className="stat-value">{stats?.revenue ? `${stats.revenue} €` : '—'}</div>
          <div className="stat-delta">↑ {stats?.revenueDelta ?? ''}</div>
        </div>
        <div className="stat-card">
          <div className="stat-label">Clients actifs</div>
          <div className="stat-value">{stats?.activeUsers ?? '—'}</div>
          <div className="stat-delta">↑ {stats?.newUsers ?? ''}</div>
        </div>
        <div className="stat-card">
          <div className="stat-label">Produits en stock</div>
          <div className="stat-value">{stats?.totalStock ?? '—'}</div>
          <div className="stat-delta" style={{ color: 'var(--rose)' }}>
            ⚠ {stats?.outOfStockCount ?? 0} en rupture
          </div>
        </div>
      </div>

      <div style={{ background: 'var(--white)', border: '1px solid var(--stone)', borderRadius: 6, padding: 24 }}>
        <div style={{ fontWeight: 600, marginBottom: 16, fontSize: 14 }}>Dernières commandes</div>
        <table className="data-table">
          <thead>
            <tr><th>Commande</th><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
          </thead>
          <tbody>
            {recentOrders.map((o) => (
              <tr key={o.id}>
                <td>#{o.id}</td>
                <td>{o.user?.email}</td>
                <td>{parseFloat(o.total).toFixed(2)} €</td>
                <td><StatusBadge status={o.status} /></td>
                <td style={{ color: 'var(--mid)' }}>
                  {new Date(o.createdAt).toLocaleDateString('fr-FR')}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}

function StatusBadge({ status }) {
  const map = {
    pending: { label: 'En attente', cls: 'st-paid' },
    paid: { label: 'Payée', cls: 'st-paid' },
    shipped: { label: 'Expédiée', cls: 'st-ship' },
    delivered: { label: 'Livrée', cls: 'st-done' },
    cancelled: { label: 'Annulée', cls: 'st-cancel' },
  };
  const s = map[status] ?? map.pending;
  return <span className={`status ${s.cls}`}>{s.label}</span>;
}
