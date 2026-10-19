import { createContext, useContext, useEffect, useMemo, useState } from 'react';

const CartContext = createContext(null);
const STORAGE_KEY = 'swado_cart';

function loadInitial() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved ? JSON.parse(saved) : { restaurantId: null, restaurantName: null, items: [] };
  } catch {
    return { restaurantId: null, restaurantName: null, items: [] };
  }
}

// A cart line is keyed by the menu item AND its chosen variant / add-ons, so
// "Paneer Roll · Large + Extra cheese" and a plain "Paneer Roll" are separate
// lines. Lines with no options keep a numeric `id` equal to the menu item id
// (backward compatible with the plain quick-add call sites).
export function cartLineKey(menuItemId, variant, addOns) {
  const v = variant?.name ? `v:${variant.name}` : '';
  const a = (addOns || [])
    .map((o) => o.name)
    .filter(Boolean)
    .sort()
    .join('+');
  const suffix = [v, a].filter(Boolean).join('|');
  return suffix ? `${menuItemId}#${suffix}` : menuItemId;
}

// Unit price for a chosen combination. `variant.price` and `addOn.price` are
// both "extra price" deltas that stack on top of the item's base price -- this
// matches the admin editor ("Extra Price" field) and the backend order maths
// (OrderController: finalPrice + variant.price + sum(addOns)).
export function cartUnitPrice(item, variant, addOns) {
  const base = Number(item.final_price ?? item.discounted_price ?? item.price ?? 0);
  const variantExtra = variant ? Number(variant.price ?? 0) : 0;
  const addOnTotal = (addOns || []).reduce((sum, o) => sum + Number(o.price ?? 0), 0);
  return base + variantExtra + addOnTotal;
}

function buildLine(item, quantity, { variant = null, addOns = [] } = {}) {
  const menuItemId = item.menu_item_id ?? item.id;
  return {
    id: cartLineKey(menuItemId, variant, addOns),
    menuItemId,
    name: item.name,
    price: cartUnitPrice(item, variant, addOns),
    image: item.image_url || item.image || null,
    isVeg: item.is_veg,
    variant: variant || null,
    addOns: addOns && addOns.length ? addOns : [],
    quantity,
  };
}

export function CartProvider({ children }) {
  const [cart, setCart] = useState(loadInitial);
  const [conflict, setConflict] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  // Set when the customer arrives via a coupon-code promotion (see
  // RestaurantDetail's promo banner) so the cart drawer can prefill it
  // instead of making them hunt down the code themselves.
  const [couponCode, setCouponCode] = useState('');

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
  }, [cart]);

  function addItem(restaurant, item, quantity = 1, options = {}) {
    const line = buildLine(item, quantity, options);
    setCart((prev) => {
      if (prev.restaurantId && prev.restaurantId !== restaurant.id && prev.items.length > 0) {
        setConflict({ restaurant, item, quantity, options });
        return prev;
      }
      const items = [...prev.items];
      const idx = items.findIndex((i) => i.id === line.id);
      if (idx >= 0) {
        items[idx] = { ...items[idx], quantity: items[idx].quantity + quantity };
      } else {
        items.push(line);
      }
      return { restaurantId: restaurant.id, restaurantName: restaurant.name, items };
    });
  }

  function confirmSwap() {
    if (!conflict) return;
    const { restaurant, item, quantity, options } = conflict;
    setCart({
      restaurantId: restaurant.id,
      restaurantName: restaurant.name,
      items: [buildLine(item, quantity, options || {})],
    });
    setConflict(null);
  }

  function dismissConflict() {
    setConflict(null);
  }

  function updateQuantity(lineId, quantity) {
    setCart((prev) => {
      if (quantity <= 0) {
        const items = prev.items.filter((i) => i.id !== lineId);
        return items.length ? { ...prev, items } : { restaurantId: null, restaurantName: null, items: [] };
      }
      return { ...prev, items: prev.items.map((i) => (i.id === lineId ? { ...i, quantity } : i)) };
    });
  }

  function clearCart() {
    setCart({ restaurantId: null, restaurantName: null, items: [] });
  }

  function openDrawer() {
    setIsDrawerOpen(true);
  }

  function closeDrawer() {
    setIsDrawerOpen(false);
  }

  // Payload rows for /orders, /orders/summary and /payments/checkout/create —
  // carries the chosen variant / add-ons the way the Flutter checkout does.
  const orderItems = useMemo(
    () =>
      cart.items.map((i) => ({
        id: i.menuItemId ?? i.id,
        quantity: i.quantity,
        ...(i.variant ? { selected_variant: i.variant } : {}),
        ...(i.addOns && i.addOns.length ? { selected_add_ons: i.addOns } : {}),
      })),
    [cart.items]
  );

  const subtotal = useMemo(
    () => cart.items.reduce((sum, i) => sum + i.price * i.quantity, 0),
    [cart.items]
  );
  const itemCount = useMemo(() => cart.items.reduce((sum, i) => sum + i.quantity, 0), [cart.items]);

  return (
    <CartContext.Provider
      value={{
        cart,
        addItem,
        updateQuantity,
        clearCart,
        orderItems,
        subtotal,
        itemCount,
        conflict,
        confirmSwap,
        dismissConflict,
        isDrawerOpen,
        openDrawer,
        closeDrawer,
        couponCode,
        setCouponCode,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  return useContext(CartContext);
}
