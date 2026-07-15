import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import AdminContactPage from './AdminContactPage';

vi.mock('../../api', () => ({
  default: {
    get: vi.fn(),
    patch: vi.fn(() => Promise.resolve({ data: {} })),
  },
}));

import api from '../../api';

const mockMessages = [
  {
    id: 'aaa111',
    name: 'Anna Boukoulou',
    email: 'anna@test.com',
    subject: 'Question livraison',
    message: 'Bonjour, ma commande n\'est pas arrivée.',
    status: 'new',
    createdAt: '2026-07-10T10:00:00+00:00',
  },
  {
    id: 'bbb222',
    name: 'Marc Dupont',
    email: 'marc@test.com',
    subject: 'Retour produit',
    message: 'Comment retourner un article ?',
    status: 'read',
    createdAt: '2026-07-05T10:00:00+00:00',
  },
];

describe('AdminContactPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.alert = vi.fn();
  });

  it('affiche la liste des messages renvoyés par l\'API', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    render(<AdminContactPage />);

    expect(await screen.findByText('Question livraison')).toBeInTheDocument();
    expect(screen.getByText('Retour produit')).toBeInTheDocument();
    expect(api.get).toHaveBeenCalledWith('/api/admin/contact');
  });

  it('filtre les messages par statut', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    render(<AdminContactPage />);
    await screen.findByText('Question livraison');

    fireEvent.change(screen.getByDisplayValue('Tous les statuts'), { target: { value: 'read' } });

    expect(screen.queryByText('Question livraison')).not.toBeInTheDocument();
    expect(screen.getByText('Retour produit')).toBeInTheDocument();
  });

  it('change le statut d\'un message et appelle l\'API', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    render(<AdminContactPage />);
    await screen.findByText('Question livraison');

    const row = screen.getByText('Question livraison').closest('tr');
    const statusSelect = within(row).getByDisplayValue('Nouveau');

    fireEvent.change(statusSelect, { target: { value: 'read' } });

    await waitFor(() => {
      expect(api.patch).toHaveBeenCalledWith('/api/admin/contact/aaa111/status', { status: 'read' });
    });
  });

  it('affiche une alerte si la mise à jour du statut échoue', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    api.patch.mockRejectedValueOnce(new Error('network error'));
    render(<AdminContactPage />);
    await screen.findByText('Question livraison');

    const row = screen.getByText('Question livraison').closest('tr');
    fireEvent.change(within(row).getByDisplayValue('Nouveau'), { target: { value: 'read' } });

    await waitFor(() => {
      expect(window.alert).toHaveBeenCalledWith('Erreur lors de la mise à jour du statut.');
    });
  });

  it('ouvre la modale de détail avec le message complet', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    render(<AdminContactPage />);
    await screen.findByText('Question livraison');

    const row = screen.getByText('Question livraison').closest('tr');
    fireEvent.click(within(row).getByText('Voir'));

    expect(screen.getByText('Bonjour, ma commande n\'est pas arrivée.')).toBeInTheDocument();
    expect(screen.getByText('Question livraison', { selector: '.modal-title' })).toBeInTheDocument();
    expect(screen.getAllByText(/Anna Boukoulou/)).toHaveLength(2);
  });

  it('ferme la modale au clic sur la croix', async () => {
    api.get.mockResolvedValueOnce({ data: mockMessages });
    render(<AdminContactPage />);
    await screen.findByText('Question livraison');

    fireEvent.click(within(screen.getByText('Question livraison').closest('tr')).getByText('Voir'));
    expect(screen.getByText('Bonjour, ma commande n\'est pas arrivée.')).toBeInTheDocument();

    fireEvent.click(screen.getByText('✕'));
    expect(screen.queryByText('Bonjour, ma commande n\'est pas arrivée.')).not.toBeInTheDocument();
  });
});
