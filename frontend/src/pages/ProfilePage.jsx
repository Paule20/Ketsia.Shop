import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../api';
import { logout, getUser } from '../auth';

export default function ProfilePage() {
  const navigate = useNavigate();
  const cachedUser = getUser();
  const [profile, setProfile] = useState(cachedUser);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    api.get('/api/me')
      .then((res) => {
        setProfile(res.data);
        setLoading(false);
      })
      .catch(() => {
        setError(true);
        setLoading(false);
      });
  }, []);

  function handleLogout() {
    logout();
    navigate('/login');
  }

  const initials = profile
    ? `${profile.firstName?.[0] ?? ''}${profile.lastName?.[0] ?? ''}`.toUpperCase()
    : '';

  return (
    <>
      <div className="bc">Accueil / <span>Mon profil</span></div>

      <div className="wishlist-page" style={{ maxWidth: 640 }}>
        <div className="wishlist-title">Mon profil</div>

        {loading ? (
          <div className="page-loading">Chargement...</div>
        ) : error || !profile ? (
          <p className="wishlist-empty">
            Impossible de charger votre profil. <Link to="/login">Se reconnecter</Link>
          </p>
        ) : (
          <div className="auth-card" style={{ margin: 0, maxWidth: 'none' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 18, marginBottom: 28 }}>
              <div style={{
                width: 64, height: 64, borderRadius: '50%',
                background: 'var(--rose-lt)', color: 'var(--rose)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 22, fontWeight: 700, fontFamily: 'var(--font-serif)',
                flexShrink: 0,
              }}>
                {initials || '👤'}
              </div>
              <div>
                <div style={{ fontFamily: 'var(--font-serif)', fontSize: 22, fontWeight: 600 }}>
                  {profile.firstName} {profile.lastName}
                </div>
                {profile.roles?.includes('ROLE_ADMIN') && (
                  <span style={{ fontSize: 11, fontWeight: 700, color: 'var(--rose)', letterSpacing: .5 }}>
                    ⚙ Administrateur
                  </span>
                )}
              </div>
            </div>

            <div className="field">
              <label>Adresse email</label>
              <input value={profile.email} disabled style={{ background: 'var(--sand)', color: 'var(--mid)' }} />
            </div>

            {profile.createdAt && (
              <div className="field">
                <label>Membre depuis</label>
                <input
                  value={new Date(profile.createdAt).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })}
                  disabled
                  style={{ background: 'var(--sand)', color: 'var(--mid)' }}
                />
              </div>
            )}

            <div style={{ display: 'flex', gap: 10, marginTop: 24, flexWrap: 'wrap' }}>
              <Link to="/orders" className="btn btn-outline btn-sm">Mes commandes</Link>
              <Link to="/wishlist" className="btn btn-outline btn-sm">Ma wishlist</Link>
            </div>

            <button
              className="btn btn-outline btn-sm btn-full"
              style={{ marginTop: 24, borderColor: '#c0392b', color: '#c0392b' }}
              onClick={handleLogout}
            >
              Se déconnecter
            </button>
          </div>
        )}
      </div>
    </>
  );
}