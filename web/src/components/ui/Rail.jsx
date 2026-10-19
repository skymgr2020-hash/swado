import { useDragScroll } from '../../hooks/useDragScroll';

// Horizontal scroll rail with grab cursor + click-and-drag scrolling.
// Drop-in replacement for `<div className="rail no-scrollbar">`.
export default function Rail({ className = '', children, ...rest }) {
  const { ref, handlers } = useDragScroll();
  return (
    <div ref={ref} className={`rail no-scrollbar ${className}`} {...handlers} {...rest}>
      {children}
    </div>
  );
}
