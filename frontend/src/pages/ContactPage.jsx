import { useState } from 'react';
import api from '../api';
import { useToast } from '../context/ToastContext';

export default function ContactPage() {
  const [form, setForm] = useState({ name: '', email: '', subject: '', message: '' });
  const [errors, setErrors] = useState({});
  const [apiError, setApiError] = useState('');
  const [loading, setLoading] = useState(false);
  const { showToast } = useToast();

  function update(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  function validate() {
    const errs = {};
    if (!form.name.trim()) errs.name = 'Le nom est requis.';
    if (!/^\S+@\S+\.\S+$/.test(form.email)) errs.email = 'Email invalide.';
    if (!form.subject.trim()) errs.subject = 'Le sujet est requis.';
    if (!form.message.trim()) errs.message = 'Le message est requis.';
    return errs;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setApiError('');
    const errs = validate();
    setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    setLoading(true);
    try {
      await api.post('/api/contact', form);
      showToast('Message envoyé avec succès !');
      setForm({ name: '', email: '', subject: '', message: '' });
      setErrors({});
    } catch (err) {
      const backendErrors = err.response?.data?.errors;
      if (backendErrors) {
        setErrors(backendErrors);
      } else {
        setApiError('Une erreur est survenue. Réessayez.');
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <div className="auth-logo">Ketsia<em>.</em>shop</div>
        <div className="auth-title">Contactez-nous</div>
        <div className="auth-sub">Une question ? Écrivez-nous, nous répondons sous 48h.</div>

        {apiError && <div className="alert-box alert-err">{apiError}</div>}

        <form onSubmit={handleSubmit} noValidate>
          <div className={`field ${errors.name ? 'err' : ''}`}>
            <label>Nom</label>
            <input
              value={form.name}
              onChange={(e) => update('name', e.target.value)}
              placeholder="Votre nom"
            />
            {errors.name && <span className="field-err">{errors.name}</span>}
          </div>

          <div className={`field ${errors.email ? 'err' : ''}`}>
            <label>Adresse email</label>
            <input
              type="email"
              value={form.email}
              onChange={(e) => update('email', e.target.value)}
              placeholder="vous@email.com"
            />
            {errors.email && <span className="field-err">{errors.email}</span>}
          </div>

          <div className={`field ${errors.subject ? 'err' : ''}`}>
            <label>Sujet</label>
            <input
              value={form.subject}
              onChange={(e) => update('subject', e.target.value)}
              placeholder="Objet de votre message"
            />
            {errors.subject && <span className="field-err">{errors.subject}</span>}
          </div>

          <div className={`field ${errors.message ? 'err' : ''}`}>
            <label>Message</label>
            <textarea
              rows={5}
              value={form.message}
              onChange={(e) => update('message', e.target.value)}
              placeholder="Votre message..."
            />
            {errors.message && <span className="field-err">{errors.message}</span>}
          </div>

          <button type="submit" disabled={loading} className="btn btn-rose btn-md btn-full">
            {loading ? 'Envoi...' : 'Envoyer le message'}
          </button>
        </form>
      </div>
    </div>
  );
}
