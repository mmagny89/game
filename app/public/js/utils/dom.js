export const el = (tag, cls) => { const n = document.createElement(tag); if (cls) n.className = cls; return n; };
export const text = (n, t) => { n.textContent = t; return n; };
export const clear = (node) => { node.innerHTML = ''; };
export const scrollBottom = (node) => { node.scrollTop = node.scrollHeight; };
