const palette = ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899'];
const map = new Map();
export function colorFor(name){
    if (!name) return '#9ca3af';
    if (!map.has(name)) map.set(name, palette[map.size % palette.length]);
    return map.get(name);
}
