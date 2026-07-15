import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import ContactPage from './ContactPage';

vi.mock('../api', () => ({
  default: {
    post: vi.fn(),
  },
}));

const showToast = vi.fn();
vi.mock('../context/ToastContext', () => ({
  useToast: () => ({ showToast }),
}));

import api from '../api';

describe('ContactPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  function fillForm({ name = 'Alice', email = 'alice@example.com', subject = 'Sujet', message = 'Message' } = {}) {
    fireEvent.change(screen.getByPlaceholderText('Votre nom'), { target: { value: name } });
    fireEvent.change(screen.getByPlaceholderText('vous@email.com'), { target: { value: email } });
    fireEvent.change(screen.getByPlaceholderText('Objet de votre message'), { target: { value: subject } });
    fireEvent.change(screen.getByPlaceholderText('Votre message...'), { target: { value: message } });
  }

  it('affiche des erreurs de validation et n\'appelle pas l\'API si les champs sont vides', async () => {
    render(<ContactPage />);

    fireEvent.click(screen.getByRole('button', { name: /Envoyer le message/ }));

    expect(await screen.findByText('Le nom est requis.')).toBeInTheDocument();
    expect(screen.getByText('Email invalide.')).toBeInTheDocument();
    expect(screen.getByText('Le sujet est requis.')).toBeInTheDocument();
    expect(screen.getByText('Le message est requis.')).toBeInTheDocument();
    expect(api.post).not.toHaveBeenCalled();
  });

  it('n\'affiche qu\'une erreur ciblée si seul l\'email est invalide', async () => {
    render(<ContactPage />);
    fillForm({ email: 'pas-un-email' });

    fireEvent.click(screen.getByRole('button', { name: /Envoyer le message/ }));

    expect(await screen.findByText('Email invalide.')).toBeInTheDocument();
    expect(screen.queryByText('Le nom est requis.')).not.toBeInTheDocument();
  });

  it('soumet le formulaire, affiche un toast de succès et le réinitialise', async () => {
    api.post.mockResolvedValueOnce({ data: { success: true } });
    render(<ContactPage />);
    fillForm();

    fireEvent.click(screen.getByRole('button', { name: /Envoyer le message/ }));

    await waitFor(() => {
      expect(api.post).toHaveBeenCalledWith('/api/contact', {
        name: 'Alice',
        email: 'alice@example.com',
        subject: 'Sujet',
        message: 'Message',
      });
      expect(showToast).toHaveBeenCalledWith('Message envoyé avec succès !');
    });

    expect(screen.getByPlaceholderText('Votre nom').value).toBe('');
  });

  it('mappe les erreurs de champ renvoyées par le serveur', async () => {
    api.post.mockRejectedValueOnce({ response: { data: { errors: { email: 'Email deja utilise dans une reclamation.' } } } });
    render(<ContactPage />);
    fillForm();

    fireEvent.click(screen.getByRole('button', { name: /Envoyer le message/ }));

    expect(await screen.findByText('Email deja utilise dans une reclamation.')).toBeInTheDocument();
  });

  it('affiche une erreur générique si le serveur ne répond pas', async () => {
    api.post.mockRejectedValueOnce({ response: undefined });
    render(<ContactPage />);
    fillForm();

    fireEvent.click(screen.getByRole('button', { name: /Envoyer le message/ }));

    expect(await screen.findByText('Une erreur est survenue. Réessayez.')).toBeInTheDocument();
  });
});
