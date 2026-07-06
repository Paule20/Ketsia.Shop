import { useState } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { login } from '../auth';

export default function LoginPage() {
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [error, setError]       = useState('');
  const [loading, setLoading]   = useState(false);
  const navigate                = useNavigate();
  const location                = useLocation();
  const registered              = location.state?.registered;

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email, password);
      navigate('/');
    } catch (err) {
      if (err.response?.status === 401) {
        setError('Email ou mot de passe incorrect.');
      } else if (!err.response) {
        setError('Impossible de contacter le serveur. Vérifiez votre connexion ou réessayez.');
      } else {
        setError(err.response?.data?.error ?? 'Une erreur est survenue. Merci de réessayer.');
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <div className="auth-logo">Ketsia<em>.</em>shop</div>
        <div className="auth-title">Connexion</div>
        <div className="auth-sub">Heureux de vous revoir !</div>

        {registered && (
          <div className="alert-box alert-ok">
            ✓ Compte créé avec succès ! Vous pouvez maintenant vous connecter.
          </div>
        )}
        {error && <div className="alert-box alert-err">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="field">
            <label>Adresse email</label>
            <input
              type="email"
              placeholder="vous@email.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>
          <div className="field">
            <label>Mot de passe</label>
            <input
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          <div style={{ textAlign: 'right', margin: '-8px 0 18px', fontSize: 12 }}>
            <a style={{ color: 'var(--rose)', cursor: 'pointer' }}>Mot de passe oublié ?</a>
          </div>
          <button type="submit" disabled={loading} className="btn btn-rose btn-md btn-full">
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <div className="form-footer">
          Pas encore de compte ? <Link to="/register">S'inscrire</Link>
        </div>
      </div>
    </div>
  );
}
