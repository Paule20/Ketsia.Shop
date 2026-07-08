import api from './api';

export async function login(email, password) {
  const res = await api.post('/api/login', { email, password });
  localStorage.setItem('token', res.data.token);
  localStorage.setItem('user', JSON.stringify(res.data.user));
   window.dispatchEvent(new Event('auth-changed'));
  return res.data.user;
}

export async function register({ firstName, lastName, email, password }) {
  const res = await api.post('/api/register', { firstName, lastName, email, password });
  return res.data;
}

export function logout() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.dispatchEvent(new Event('auth-changed'));
}

export function getUser() {
  const user = localStorage.getItem('user');
  if (!user) return null;

  try {
    return JSON.parse(user);
  } catch {
    localStorage.removeItem('user');
    return null;
  }
}

export function isAuthenticated() {
  return !!localStorage.getItem('token');
}

export function isAdmin() {
  const user = getUser();
  return user?.roles?.includes('ROLE_ADMIN') ?? false;
}
