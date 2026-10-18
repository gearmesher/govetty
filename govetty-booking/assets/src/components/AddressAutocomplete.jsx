import React, { useEffect, useRef, useState } from 'react';
import { api } from '../api';

function newSession() {
  try {
    if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
  } catch (e) {
    // fall through
  }
  return String(Date.now()) + Math.random().toString(16).slice(2);
}

/**
 * Street-address input with a suggestion list (Canadian addresses, served by
 * the plugin's /address/suggest route). Picking a suggestion calls onPick with
 * { street, unit, city, province, postal_code }. Typing still works freely, and
 * if autocomplete is switched off (no Google key) or fails, this behaves as a
 * plain text input, so the form is never blocked on it.
 */
export default function AddressAutocomplete({ id, value, onChange, onPick, enabled }) {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const session = useRef(newSession());
  const timer = useRef(null);
  const latest = useRef(0);
  const box = useRef(null);

  useEffect(() => {
    const away = (e) => {
      if (box.current && !box.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', away);
    return () => document.removeEventListener('mousedown', away);
  }, []);

  useEffect(() => () => clearTimeout(timer.current), []);

  const search = (text) => {
    clearTimeout(timer.current);
    if (!enabled || text.trim().length < 3) {
      setItems([]);
      setOpen(false);
      return;
    }
    timer.current = setTimeout(async () => {
      const mine = ++latest.current;
      const res = await api.addressSuggest(text.trim(), session.current);
      if (mine !== latest.current) return; // a newer keystroke superseded this
      const list = res.ok && Array.isArray(res.data.suggestions) ? res.data.suggestions : [];
      setItems(list);
      setActive(-1);
      setOpen(list.length > 0);
    }, 250);
  };

  const choose = async (item) => {
    setOpen(false);
    setItems([]);
    latest.current++;
    const res = await api.addressPlace(item.place_id, session.current);
    session.current = newSession(); // a pick ends Google's billing session
    if (res.ok && res.data.street !== undefined) {
      onPick(res.data);
    } else {
      onChange(item.text.split(',')[0]); // at least keep the street line
    }
  };

  const onKeyDown = (e) => {
    if (!open || items.length === 0) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActive((a) => (a + 1) % items.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActive((a) => (a <= 0 ? items.length - 1 : a - 1));
    } else if (e.key === 'Enter' && active >= 0) {
      e.preventDefault();
      choose(items[active]);
    } else if (e.key === 'Escape') {
      setOpen(false);
    }
  };

  return (
    <div className="gv-ac" ref={box}>
      <input
        id={id}
        className="gv-input"
        required
        autoComplete="off"
        role={enabled ? 'combobox' : undefined}
        aria-expanded={enabled ? open : undefined}
        aria-autocomplete={enabled ? 'list' : undefined}
        aria-controls={enabled ? `${id}-list` : undefined}
        value={value}
        onChange={(e) => {
          onChange(e.target.value);
          search(e.target.value);
        }}
        onFocus={() => items.length > 0 && setOpen(true)}
        onKeyDown={onKeyDown}
      />
      {open && (
        <ul className="gv-ac-list" id={`${id}-list`} role="listbox">
          {items.map((item, i) => (
            <li
              key={item.place_id}
              role="option"
              aria-selected={i === active}
              className={i === active ? 'gv-ac-item gv-ac-item--active' : 'gv-ac-item'}
              onMouseDown={(e) => {
                e.preventDefault(); // keep focus; choose before blur closes the list
                choose(item);
              }}
              onMouseEnter={() => setActive(i)}
            >
              {item.text}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
