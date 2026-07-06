import { useEffect, useState } from 'react';
import api from '../../api';

const SIZES_BY_SLUG = {
  femme:  ['XS', 'S', 'M', 'L', 'XL'],
  homme:  ['XS', 'S', 'M', 'L', 'XL'],
  fille:  ['2A', '4A', '6A', '8A', '10A', '12A', '14A'],
  garcon: ['2A', '4A', '6A', '8A', '10A', '12A', '14A'],
};

const EMPTY_FORM = { name: '', categoryId: '', subCategory: '', price: '', stock: '', description: '', imageUrl: '' };

export default function AdminProductsPage() {
  const [products, setProducts]   = useState([]);
  const [categories, setCategories] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [form, setForm]           = useState(EMPTY_FORM);
  const [saving, setSaving]       = useState(false);
  const [toast, setToast]         = useState('');

  useEffect(() => {
    api.get('/api/products').then((r) => setProducts(r.data)).catch(() => {});
    api.get('/api/categories').then((r) => setCategories(r.data)).catch(() => {});
  }, []);

  function openAddModal() {
    setEditingId(null);
    setForm(EMPTY_FORM);
    setShowModal(true);
  }

  function openEditModal(p) {
    setEditingId(p.id);
    setForm({
      name: p.name,
      categoryId: p.category?.id ?? '',
      subCategory: p.subCategory ?? '',
      price: p.price,
      stock: p.stock,
      description: p.description ?? '',
      imageUrl: p.imageUrl ?? '',
    });
    setShowModal(true);
  }

  function closeModal() { setShowModal(false); }
  function update(k, v) { setForm((f) => ({ ...f, [k]: v })); }

  function notify(msg) {
    setToast(msg);
    setTimeout(() => setToast(''), 2800);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);

    const selectedCat = categories.find((c) => c.id === Number(form.categoryId));
    const sizes = selectedCat ? (SIZES_BY_SLUG[selectedCat.slug] ?? []) : [];

    const payload = {
      name: form.name,
      description: form.description,
      price: form.price,
      stock: Number(form.stock),
      subCategory: form.subCategory,
      imageUrl: form.imageUrl,
      categoryId: Number(form.categoryId),
      sizes,
    };

    try {
      if (editingId) {
        const res = await api.put(`/api/products/${editingId}`, payload);
        setProducts((prev) => prev.map((p) => p.id === editingId ? res.data : p));
        notify('Produit modifié ✓');
      } else {
        const res = await api.post('/api/products', payload);
        setProducts((prev) => [...prev, res.data]);
        notify('Produit ajouté ✓');
      }
      closeModal();
    } catch (err) {
      notify(err.response?.data?.error ?? 'Erreur lors de l\'enregistrement.');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!confirm('Supprimer ce produit ?')) return;
    try {
      await api.delete(`/api/products/${id}`);
      setProducts((prev) => prev.filter((p) => p.id !== id));
      notify('Produit supprimé');
    } catch {
      alert('Erreur lors de la suppression.');
    }
  }

  return (
    <>
      {toast && (
        <div style={{
          position: 'fixed', bottom: 90, right: 24, background: 'var(--ink)', color: '#fff',
          padding: '14px 20px', borderRadius: 4, fontSize: 13, zIndex: 8888,
          boxShadow: '0 8px 32px rgba(0,0,0,.2)', borderLeft: '3px solid var(--green)',
        }}>
          {toast}
        </div>
      )}

      {/* Modal — Ajouter / Modifier un produit */}
      <div className={`modal-bg ${showModal ? 'show' : ''}`} onClick={(e) => e.target === e.currentTarget && closeModal()}>
        <div className="modal">
          <div className="modal-head">
            <span className="modal-title">{editingId ? 'Modifier le produit' : 'Ajouter un produit'}</span>
            <button className="modal-close" onClick={closeModal}>✕</button>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="field">
              <label>Nom du produit</label>
              <input placeholder="Ex : Robe d'été fleurie" value={form.name} onChange={(e) => update('name', e.target.value)} required />
            </div>
            <div className="field-row">
              <div className="field">
                <label>Catégorie</label>
                <select value={form.categoryId} onChange={(e) => update('categoryId', e.target.value)} required>
                  <option value="">— Choisir —</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              <div className="field">
                <label>Sous-catégorie</label>
                <input placeholder="Ex : Robes" value={form.subCategory} onChange={(e) => update('subCategory', e.target.value)} />
              </div>
            </div>
            <div className="field-row">
              <div className="field">
                <label>Prix (€)</label>
                <input type="number" step="0.01" placeholder="29.99" value={form.price} onChange={(e) => update('price', e.target.value)} required />
              </div>
              <div className="field">
                <label>Stock</label>
                <input type="number" placeholder="50" value={form.stock} onChange={(e) => update('stock', e.target.value)} required />
              </div>
            </div>
            <div className="field">
              <label>URL de l'image</label>
              <input placeholder="https://images.unsplash.com/..." value={form.imageUrl} onChange={(e) => update('imageUrl', e.target.value)} />
            </div>
            <div className="field">
              <label>Description</label>
              <textarea placeholder="Décrivez le produit..." value={form.description} onChange={(e) => update('description', e.target.value)} />
            </div>
            <div style={{ display: 'flex', gap: 10, marginTop: 8 }}>
              <button type="submit" className="btn btn-rose btn-md btn-full" disabled={saving}>
                {saving ? 'Enregistrement...' : editingId ? 'Enregistrer les modifications' : 'Ajouter le produit'}
              </button>
              <button type="button" className="btn btn-outline btn-md" onClick={closeModal}>
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="admin-header">
        <div className="admin-title">Produits</div>
        <button className="btn btn-rose btn-sm" onClick={openAddModal}>+ Ajouter un produit</button>
      </div>

      <table className="data-table">
        <thead>
          <tr>
            <th>Produit</th>
            <th>Catégorie</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {products.map((p) => (
            <tr key={p.id}>
              <td>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <img className="prod-thumb-sm" src={p.imageUrl} alt={p.name} />
                  {p.name}
                </div>
              </td>
              <td>{p.category?.name}{p.subCategory ? ` — ${p.subCategory}` : ''}</td>
              <td>{parseFloat(p.price).toFixed(2)} €</td>
              <td>
                <span style={{ color: p.stock > 5 ? 'var(--green)' : 'var(--rose)', fontWeight: 600 }}>
                  {p.stock}
                </span>
              </td>
              <td>
                <div className="tbl-actions">
                  <button onClick={() => openEditModal(p)}>Modifier</button>
                  <button className="del" onClick={() => handleDelete(p.id)}>Supprimer</button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
}
