import { describe, it, expect, beforeEach } from 'vitest';
import { isAuthenticated, isAdmin, getUser, logout } from './auth';

describe('auth.js', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe('isAuthenticated', () => {
    it('retourne false si aucun token stocké', () => {
      expect(isAuthenticated()).toBe(false);
    });

    it('retourne true si un token est présent', () => {
      localStorage.setItem('token', 'fake-jwt-token');
      expect(isAuthenticated()).toBe(true);
    });
  });

  describe('getUser', () => {
    it('retourne null si aucun utilisateur stocké', () => {
      expect(getUser()).toBeNull();
    });

    it('retourne l\'utilisateur parsé depuis le localStorage', () => {
      const user = { id: 1, email: 'admin@ketsia.shop', roles: ['ROLE_ADMIN'] };
      localStorage.setItem('user', JSON.stringify(user));
      expect(getUser()).toEqual(user);
    });
  });

  describe('isAdmin', () => {
    it('retourne false si aucun utilisateur connecté', () => {
      expect(isAdmin()).toBe(false);
    });

    it('retourne false pour un utilisateur avec seulement ROLE_USER', () => {
      localStorage.setItem('user', JSON.stringify({ roles: ['ROLE_USER'] }));
      expect(isAdmin()).toBe(false);
    });

    it('retourne true pour un utilisateur avec ROLE_ADMIN', () => {
      localStorage.setItem('user', JSON.stringify({ roles: ['ROLE_USER', 'ROLE_ADMIN'] }));
      expect(isAdmin()).toBe(true);
    });
  });

  describe('logout', () => {
    it('supprime le token et l\'utilisateur du localStorage', () => {
      localStorage.setItem('token', 'fake-jwt-token');
      localStorage.setItem('user', JSON.stringify({ id: 1 }));

      logout();

      expect(localStorage.getItem('token')).toBeNull();
      expect(localStorage.getItem('user')).toBeNull();
    });
  });
});