import { useCallback, useRef } from 'react';

// Click-and-drag horizontal scrolling for a rail, mouse only (touch/trackpad
// already pan natively). Adds `.is-dragging` while a drag is in progress and
// swallows the click that ends a real drag so cards underneath don't fire.
export function useDragScroll() {
  const ref = useRef(null);
  const state = useRef({ down: false, moved: false, startX: 0, startLeft: 0 });

  const onPointerDown = useCallback((e) => {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    const el = ref.current;
    if (!el) return;
    state.current = {
      down: true,
      moved: false,
      startX: e.clientX,
      startLeft: el.scrollLeft,
    };
  }, []);

  const onPointerMove = useCallback((e) => {
    const s = state.current;
    if (!s.down) return;
    const el = ref.current;
    if (!el) return;
    const dx = e.clientX - s.startX;
    if (!s.moved && Math.abs(dx) < 4) return;
    if (!s.moved) {
      s.moved = true;
      el.classList.add('is-dragging');
      el.setPointerCapture?.(e.pointerId);
    }
    el.scrollLeft = s.startLeft - dx;
  }, []);

  const endDrag = useCallback((e) => {
    const s = state.current;
    const el = ref.current;
    if (s.down && s.moved && el) {
      el.classList.remove('is-dragging');
      el.releasePointerCapture?.(e.pointerId);
      // Block the click that concludes a drag.
      const block = (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
      };
      el.addEventListener('click', block, { capture: true, once: true });
      setTimeout(() => el.removeEventListener('click', block, { capture: true }), 0);
    }
    state.current = { down: false, moved: false, startX: 0, startLeft: 0 };
  }, []);

  return {
    ref,
    handlers: {
      onPointerDown,
      onPointerMove,
      onPointerUp: endDrag,
      onPointerLeave: endDrag,
      onPointerCancel: endDrag,
    },
  };
}
