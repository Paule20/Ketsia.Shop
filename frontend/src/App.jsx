import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { isAuthenticated, isAdmin } from './auth';
import { CartProvider } from './context/CartContext';
import { ToastProvider } from './context/ToastContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import ProductsPage from './pages/ProductsPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import WishlistPage from './pages/WishlistPage';
import CartPage from './pages/CartPage';
import CheckoutPage from './pages/CheckoutPage';
import SuccessPage from './pages/SuccessPage';
import OrdersPage from './pages/OrdersPage';
import AdminLayout from './pages/admin/AdminLayout';
import AdminDashboard from './pages/admin/AdminDashboard';
import AdminOrdersPage from './pages/admin/AdminOrdersPage';
import AdminProductsPage from './pages/admin/AdminProductsPage';
import AdminUsersPage from './pages/admin/AdminUsersPage';
import DevNav from './components/DevNav';
import ProfilePage from './pages/ProfilePage';

function PrivateRoute({ children }) {
  if (import.meta.env.DEV) return children;
  return isAuthenticated() ? children : <Navigate to="/login" replace />;
}

function AdminRoute({ children }) {
  if (!isAuthenticated()) return <Navigate to="/login" replace />;
  if (!isAdmin())         return <Navigate to="/"      replace />;
  return children;
}

function PublicLayout({ children }) {
  return (
    <>
      <Navbar />
      <main style={{ minHeight: 'calc(100vh - 70px)' }}>
        {children}
      </main>
      <Footer />
    </>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <CartProvider>
        <ToastProvider>
        <Routes>
          {/* ── Pages publiques ── */}
          <Route path="/" element={<PublicLayout><HomePage /></PublicLayout>} />
          <Route path="/catalogue" element={<PublicLayout><ProductsPage /></PublicLayout>} />
          <Route path="/login"    element={<PublicLayout><LoginPage /></PublicLayout>} />
          <Route path="/register" element={<PublicLayout><RegisterPage /></PublicLayout>} />
          <Route path="/cart"     element={<PublicLayout><CartPage /></PublicLayout>} />

          {/* ── Commande & Confirmation (header minimal, sans Navbar ni Footer) ── */}
          <Route path="/commande" element={
            <PrivateRoute><CheckoutPage /></PrivateRoute>
          } />
          <Route path="/confirmation" element={
            <PrivateRoute><SuccessPage /></PrivateRoute>
          } />

          {/* ── Pages privées ── */}
          <Route path="/wishlist" element={
            <PublicLayout>
              <PrivateRoute><WishlistPage /></PrivateRoute>
            </PublicLayout>
          } />
          <Route path="/orders" element={
            <PublicLayout>
              <PrivateRoute><OrdersPage /></PrivateRoute>
            </PublicLayout>
          } />
          <Route path="/profil" element={
            <PublicLayout>
              <PrivateRoute><ProfilePage /></PrivateRoute>
            </PublicLayout>
          } />

          {/* ── Administration ── */}
          <Route path="/admin" element={
            <AdminRoute><AdminLayout /></AdminRoute>
          }>
            <Route index          element={<AdminDashboard />} />
            <Route path="orders"  element={<AdminOrdersPage />} />
            <Route path="products" element={<AdminProductsPage />} />
            <Route path="users"   element={<AdminUsersPage />} />
          </Route>

          {/* Fallback */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
         <DevNav />
        </ToastProvider>
      </CartProvider>
    </BrowserRouter>
  );
}
