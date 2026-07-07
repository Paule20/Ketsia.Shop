import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import LoginPage from './LoginPage';

const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return { ...actual, useNavigate: () => mockNavigate };
});

vi.mock('../auth', () => ({
  login: vi.fn(),
}));

import { login } from '../auth';

function renderLoginPage(initialState) {
  return render(
    <MemoryRouter initialEntries={[{ pathname: '/login', state: initialState }]}>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
      </Routes>
    </MemoryRouter>
  );
}

describe('LoginPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('affiche le message de succès si on arrive depuis une inscription', () => {
    renderLoginPage({ registered: true });
    expect(screen.getByText(/Compte créé avec succès/)).toBeInTheDocument();
  });

  it('n\'affiche pas le message de succès en arrivée normale', () => {
    renderLoginPage();
    expect(screen.queryByText(/Compte créé avec succès/)).not.toBeInTheDocument();
  });

  it('appelle login() avec l\'email et le mot de passe saisis, puis redirige vers "/"', async () => {
    login.mockResolvedValueOnce({ id: 1, email: 'admin@ketsia.shop' });
    renderLoginPage();

    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: 'admin@ketsia.shop' } });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), { target: { value: 'Admin1234!' } });
    fireEvent.click(screen.getByRole('button', { name: /Se connecter/ }));

    await waitFor(() => {
      expect(login).toHaveBeenCalledWith('admin@ketsia.shop', 'Admin1234!');
      expect(mockNavigate).toHaveBeenCalledWith('/');
    });
  });

  it('affiche "Email ou mot de passe incorrect" sur une réponse 401', async () => {
    login.mockRejectedValueOnce({ response: { status: 401 } });
    renderLoginPage();

    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: 'x@x.com' } });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), { target: { value: 'wrong' } });
    fireEvent.click(screen.getByRole('button', { name: /Se connecter/ }));

    expect(await screen.findByText('Email ou mot de passe incorrect.')).toBeInTheDocument();
    expect(mockNavigate).not.toHaveBeenCalled();
  });

  it('affiche un message réseau si le serveur ne répond pas', async () => {
    login.mockRejectedValueOnce({ response: undefined });
    renderLoginPage();

    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: 'x@x.com' } });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), { target: { value: 'x' } });
    fireEvent.click(screen.getByRole('button', { name: /Se connecter/ }));

    expect(await screen.findByText(/Impossible de contacter le serveur/)).toBeInTheDocument();
  });

  it('affiche le message d\'erreur renvoyé par le serveur pour les autres codes', async () => {
    login.mockRejectedValueOnce({ response: { status: 500, data: { error: 'Erreur serveur interne.' } } });
    renderLoginPage();

    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: 'x@x.com' } });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), { target: { value: 'x' } });
    fireEvent.click(screen.getByRole('button', { name: /Se connecter/ }));

    expect(await screen.findByText('Erreur serveur interne.')).toBeInTheDocument();
  });

  it('désactive le bouton et affiche "Connexion..." pendant la requête', async () => {
    let resolveLogin;
    login.mockReturnValueOnce(new Promise((resolve) => { resolveLogin = resolve; }));
    renderLoginPage();

    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: 'x@x.com' } });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), { target: { value: 'x' } });
    fireEvent.click(screen.getByRole('button', { name: /Se connecter/ }));

    expect(await screen.findByRole('button', { name: /Connexion.../ })).toBeDisabled();

    resolveLogin({ id: 1 });
    await waitFor(() => expect(mockNavigate).toHaveBeenCalled());
  });
});
