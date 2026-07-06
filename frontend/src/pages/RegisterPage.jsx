import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { register } from '../auth';

export default function RegisterPage() {
  const [form, setForm] = useState({ firstName: '', lastName: '', email: '', password: '' });
  const [errors, setErrors] = useState({});
  const [apiError, setApiError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  function update(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
  }

  function validate() {
    const errs = {};
    if (!form.firstName.trim()) errs.firstName = 'Le prénom est requis.';
    if (!form.lastName.trim())  errs.lastName  = 'Le nom est requis.';
    if (!/^\S+@\S+\.\S+$/.test(form.email)) errs.email = 'Email invalide.';
    if (form.password.length < 8) errs.password = '8 caractères minimum requis.';
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
    await register(form);
    navigate('/login', { state: { registered: true } });
  } catch (err) {
    const backendErrors = err.response?.data?.errors;
    if (backendErrors?.email) {
      setApiError('exists');
    } else if (backendErrors) {
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
        <div className="auth-title">Créer un compte</div>
        <div className="auth-sub">Rejoignez la communauté Ketsia.</div>

        {apiError === 'exists' && (
          <div className="alert-box alert-err">
            Cet email est déjà utilisé. <Link to="/login" style={{ color: 'var(--rose)' }}>Se connecter ?</Link>
          </div>
        )}
        {apiError && apiError !== 'exists' && (
          <div className="alert-box alert-err">{apiError}</div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="field-row">
            <div className={`field ${errors.firstName ? 'err' : ''}`}>
              <label>Prénom</label>
              <input
                value={form.firstName}
                onChange={(e) => update('firstName', e.target.value)}
                placeholder="Votre prénom"
              />
              {errors.firstName && <span className="field-err">{errors.firstName}</span>}
            </div>
            <div className={`field ${errors.lastName ? 'err' : ''}`}>
              <label>Nom</label>
              <input
                value={form.lastName}
                onChange={(e) => update('lastName', e.target.value)}
                placeholder="Votre nom"
              />
              {errors.lastName && <span className="field-err">{errors.lastName}</span>}
            </div>
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

          <div className={`field ${errors.password ? 'err' : ''}`}>
            <label>Mot de passe</label>
            <input
              type="password"
              value={form.password}
              onChange={(e) => update('password', e.target.value)}
              placeholder="8 caractères minimum"
            />
            {errors.password && <span className="field-err">{errors.password}</span>}
          </div>

          <button type="submit" disabled={loading} className="btn btn-rose btn-md btn-full">
            {loading ? 'Création...' : 'Créer mon compte'}
          </button>
        </form>

        <div className="form-footer">
          Déjà un compte ? <Link to="/login">Se connecter</Link>
        </div>
      </div>
    </div>
  );
}

