import { useEffect, useState } from 'react';
import api from '../../api';

const STATUSES = ['new', 'read'];
const STATUS_LABELS = { new: 'Nouveau', read: 'Traité' };

export default function AdminContactPage() {
  const [messages, setMessages] = useState([]);
  const [filter, setFilter]     = useState('');
  const [selected, setSelected] = useState(null); // message affiché dans la modale

  useEffect(() => {
    api.get('/api/admin/contact').then((r) => setMessages(r.data)).catch(() => {});
  }, []);

  async function handleStatusChange(id, status) {
    setMessages((prev) => prev.map((m) => m.id === id ? { ...m, status } : m));
    setSelected((prev) => prev && prev.id === id ? { ...prev, status } : prev);
    try {
      await api.patch(`/api/admin/contact/${id}/status`, { status });
    } catch {
      alert('Erreur lors de la mise à jour du statut.');
    }
  }

  function closeModal() { setSelected(null); }

  const visible = filter ? messages.filter((m) => m.status === filter) : messages;

  return (
    <>
      <div className="admin-header">
        <div className="admin-title">Messages de contact</div>
        <select className="sort-select" style={{ fontSize: 12 }} value={filter} onChange={(e) => setFilter(e.target.value)}>
          <option value="">Tous les statuts</option>
          {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
        </select>
      </div>

      <table className="data-table">
        <thead>
          <tr><th>De</th><th>Sujet</th><th>Date</th><th>Statut</th><th>Détail</th></tr>
        </thead>
        <tbody>
          {visible.map((m) => (
            <tr key={m.id}>
              <td>
                <strong>{m.name}</strong><br />
                <span style={{ color: 'var(--mid)' }}>{m.email}</span>
              </td>
              <td>{m.subject}</td>
              <td style={{ color: 'var(--mid)' }}>{new Date(m.createdAt).toLocaleDateString('fr-FR')}</td>
              <td>
                <select
                  className="status-select"
                  value={m.status}
                  onChange={(e) => handleStatusChange(m.id, e.target.value)}
                >
                  {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
                </select>
              </td>
              <td>
                <button className="btn btn-outline btn-sm" onClick={() => setSelected(m)}>
                  Voir
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {/* ── Modale de détail message ── */}
      <div className={`modal-bg ${selected ? 'show' : ''}`} onClick={(e) => e.target === e.currentTarget && closeModal()}>
        {selected && (
          <div className="modal" style={{ maxWidth: 560 }}>
            <div className="modal-head">
              <span className="modal-title">{selected.subject}</span>
              <button className="modal-close" onClick={closeModal}>✕</button>
            </div>

            <div style={{ marginBottom: 14, fontSize: 13, color: 'var(--mid)' }}>
              {selected.name} — {selected.email} — {new Date(selected.createdAt).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}
            </div>

            <div style={{ whiteSpace: 'pre-wrap', marginBottom: 18 }}>{selected.message}</div>

            <select
              className="status-select"
              value={selected.status}
              onChange={(e) => handleStatusChange(selected.id, e.target.value)}
            >
              {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
            </select>
          </div>
        )}
      </div>
    </>
  );
}
