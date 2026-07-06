import { useEffect, useState } from 'react';
import api from '../../api';

export default function AdminUsersPage() {
  const [users, setUsers] = useState([]);

  useEffect(() => {
    api.get('/api/admin/users').then((r) => setUsers(r.data)).catch(() => {});
  }, []);

  async function handleDelete(id) {
    if (!confirm('Supprimer cet utilisateur ?')) return;
    try {
      await api.delete(`/api/admin/users/${id}`);
      setUsers((prev) => prev.filter((u) => u.id !== id));
    } catch {
      alert('Erreur lors de la suppression.');
    }
  }

  return (
    <>
      <div className="admin-header">
        <div className="admin-title">Utilisateurs</div>
        <span style={{ fontSize: 13, color: 'var(--mid)' }}>{users.length} comptes</span>
      </div>

      <table className="data-table">
        <thead>
          <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Commandes</th><th>Actions</th></tr>
        </thead>
        <tbody>
          {users.map((u) => {
            const isAdmin = u.roles?.includes('ROLE_ADMIN');
            return (
              <tr key={u.id}>
                <td><strong>{u.firstName} {u.lastName}</strong></td>
                <td>{u.email}</td>
                <td>
                  <span style={{
                    background: isAdmin ? '#e8f5e9' : 'var(--rose-lt)',
                    color: isAdmin ? 'var(--green)' : 'var(--rose)',
                    padding: '3px 8px', borderRadius: 12, fontSize: 11, fontWeight: 600,
                  }}>
                    {isAdmin ? 'Admin' : 'Client'}
                  </span>
                </td>
                <td>{u.ordersCount ?? '—'}</td>
                <td>
                  <div className="tbl-actions">
                    <button>Modifier</button>
                    {!isAdmin && <button className="del" onClick={() => handleDelete(u.id)}>Supprimer</button>}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </>
  );
}
