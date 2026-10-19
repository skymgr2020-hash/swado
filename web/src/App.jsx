import { Routes, Route } from 'react-router-dom';
import { useBranding } from './context/BrandingContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import LocationPicker from './components/LocationPicker';
import LoginModal from './components/LoginModal';
import CartDrawer from './components/CartDrawer';
import CartConflictModal from './components/CartConflictModal';
import BottomNav from './components/BottomNav';
import Home from './pages/Home';
import Search from './pages/Search';
import RestaurantDetail from './pages/RestaurantDetail';
import Register from './pages/Register';
import Orders from './pages/Orders';
import Checkout from './pages/Checkout';
import OrderConfirmation from './pages/OrderConfirmation';
import OrderTracking from './pages/OrderTracking';
import InfoPage from './pages/InfoPage';

export default function App() {
  const { branding } = useBranding();
  const backgroundImage = branding?.frontend_background_image;
  const mainStyle = backgroundImage
    ? {
        backgroundImage: `linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.97)), url("${backgroundImage}")`,
        backgroundPosition: 'center top',
        backgroundRepeat: 'no-repeat',
        backgroundSize: 'cover',
      }
    : undefined;

  return (
    <>
      <Navbar />
      <LocationPicker />
      <LoginModal />
      <CartDrawer />
      <CartConflictModal />
      <main className="flex-1 bg-canvas" style={mainStyle}>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/search" element={<Search />} />
          <Route path="/restaurants/:id" element={<RestaurantDetail />} />
          <Route path="/register" element={<Register />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/checkout" element={<Checkout />} />
          <Route path="/order/confirmation" element={<OrderConfirmation />} />
          <Route path="/order/:id/track" element={<OrderTracking />} />
          <Route path="/p/:slug" element={<InfoPage />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </main>
      <Footer />
      <BottomNav />
    </>
  );
}

function NotFound() {
  return <div className="mx-auto max-w-lg px-4 py-24 text-center text-ink-soft">Page not found.</div>;
}