import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import AdminOrdersPage from './AdminOrdersPage';

vi.mock('../../api', () => ({
  default: {
    get: vi.fn(),
    patch: vi.fn(() => Promise.resolve({ data: {} })),
  },
}));

import api from '../../api';

const mockOrders = [
  {
    id: 3,
    status: 'paid',
    total: '74.97',
    shippingAddress: '12 rue des Fleurs, Paris',
    createdAt: '2026-07-05T10:00:00+00:00',
    user: { firstName: 'Anna', lastName: 'BOUKOULOU', email: 'annaoush@test.com' },
    items: [
      { id: 1, quantity: 2, unitPrice: '37.49', subtotal: '74.98', product: { id: 5, name: 'Robe fleurie', imageUrl: null } },
    ],
  },
  {
    id: 2,
    status: 'shipped',
    total: '24.99',
    shippingAddress: '3 avenue Victor Hugo, Lyon',
    createdAt: '2026-06-02T10:00:00+00:00',
    user: { firstName: 'Marie', lastName: 'Dupont', email: 'user@ketsia.shop' },
    items: [],
  },
];

describe('AdminOrdersPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.alert = vi.fn();
  });

  it('affiche la liste des commandes renvoyées par l\'API', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    render(<AdminOrdersPage />);

    expect(await screen.findByText('#3')).toBeInTheDocument();
    expect(screen.getByText('#2')).toBeInTheDocument();
    expect(screen.getByText('annaoush@test.com')).toBeInTheDocument();
    expect(api.get).toHaveBeenCalledWith('/api/admin/orders');
  });

  it('filtre les commandes par statut', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    render(<AdminOrdersPage />);
    await screen.findByText('#3');

    fireEvent.change(screen.getByDisplayValue('Tous les statuts'), { target: { value: 'shipped' } });

    expect(screen.queryByText('#3')).not.toBeInTheDocument();
    expect(screen.getByText('#2')).toBeInTheDocument();
  });

  it('change le statut d\'une commande et appelle l\'API', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    render(<AdminOrdersPage />);
    await screen.findByText('#3');

    const row = screen.getByText('#3').closest('tr');
    const statusSelect = within(row).getByDisplayValue('Payée');

    fireEvent.change(statusSelect, { target: { value: 'shipped' } });

    await waitFor(() => {
      expect(api.patch).toHaveBeenCalledWith('/api/admin/orders/3/status', { status: 'shipped' });
    });
  });

  it('affiche une alerte si la mise à jour du statut échoue', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    api.patch.mockRejectedValueOnce(new Error('network error'));
    render(<AdminOrdersPage />);
    await screen.findByText('#3');

    const row = screen.getByText('#3').closest('tr');
    fireEvent.change(within(row).getByDisplayValue('Payée'), { target: { value: 'cancelled' } });

    await waitFor(() => {
      expect(window.alert).toHaveBeenCalledWith('Erreur lors de la mise à jour du statut.');
    });
  });

  it('ouvre la modale de détail avec les articles de la commande', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    render(<AdminOrdersPage />);
    await screen.findByText('#3');

    const row = screen.getByText('#3').closest('tr');
    fireEvent.click(within(row).getByText('Voir'));

    expect(screen.getByText('Commande #3')).toBeInTheDocument();
    expect(screen.getByText('12 rue des Fleurs, Paris')).toBeInTheDocument();
    expect(screen.getByText('Robe fleurie')).toBeInTheDocument();
    expect(screen.getByText('Total : 74.97 €')).toBeInTheDocument();
  });

  it('ferme la modale au clic sur la croix', async () => {
    api.get.mockResolvedValueOnce({ data: mockOrders });
    render(<AdminOrdersPage />);
    await screen.findByText('#3');

    fireEvent.click(within(screen.getByText('#3').closest('tr')).getByText('Voir'));
    expect(screen.getByText('Commande #3')).toBeInTheDocument();

    fireEvent.click(screen.getByText('✕'));
    expect(screen.queryByText('Commande #3')).not.toBeInTheDocument();
  });
});
