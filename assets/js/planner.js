(() => {
  const root = document.querySelector('[data-pp-planner]');
  if (!root || typeof PPPlanner === 'undefined') return;

  const state = { products: [], selected: [], mode: null, site: {} };
  const $ = (s) => root.querySelector(s);
  const productsEl = $('[data-pp-products]');
  const categoryEl = $('[data-pp-category]');
  const searchEl = $('[data-pp-search]');
  const workspace = $('[data-pp-workspace]');
  const controls = $('[data-pp-controls]');
  const start = $('[data-pp-start]');

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));

  async function loadProducts() {
    const response = await fetch(PPPlanner.restUrl + 'products');
    state.products = await response.json();
    const cats = [...new Set(state.products.map(p => p.category).filter(Boolean))].sort();
    categoryEl.innerHTML = '<option value="">All categories</option>' + cats.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
    renderProducts();
  }

  function renderProducts() {
    const q = (searchEl.value || '').toLowerCase();
    const cat = categoryEl.value;
    const setting = $('[data-pp-setting]').value;
    const age = $('[data-pp-age]').value;
    const list = state.products.filter(p => {
      if (q && !(p.name + ' ' + p.category + ' ' + p.features).toLowerCase().includes(q)) return false;
      if (cat && p.category !== cat) return false;
      if (setting) {
        const settings = String(p.setting || '').toLowerCase();
        if (setting === 'EYFS' && String(p.eyfs).toLowerCase() !== 'yes' && !settings.includes('eyfs')) return false;
        if (setting === 'SEN' && String(p.sen).toLowerCase() !== 'yes' && !settings.includes('sen')) return false;
        if (!['EYFS','SEN'].includes(setting) && !settings.includes(setting.toLowerCase())) return false;
      }
      if (age && p.age_from && p.age_to) {
        const [min, max] = age.split('-').map(Number);
        if (!isNaN(min) && !isNaN(max) && (Number(p.age_to) < min || Number(p.age_from) > max)) return false;
      }
      return true;
    });

    productsEl.innerHTML = list.map(p => `
      <article class="pp-product-card">
        ${p.image ? `<img src="${escapeHtml(p.image)}" alt="">` : '<div class="pp-product-placeholder">3D</div>'}
        <div class="pp-product-body">
          <strong>${escapeHtml(p.name)}</strong>
          <small>${escapeHtml(p.category || '')}</small>
          <button type="button" class="pp-button pp-button-small" data-add="${p.id}">+ Add</button>
        </div>
      </article>`).join('') || '<p class="pp-muted">No products match the current filters.</p>';
  }

  function addProduct(id) {
    const p = state.products.find(x => Number(x.id) === Number(id));
    if (!p) return;
    if (!state.selected.some(x => Number(x.id) === Number(id))) state.selected.push(p);
    renderCanvas();
  }

  function renderCanvas() {
    const canvas = $('[data-pp-canvas]');
    if (!state.selected.length) {
      canvas.innerHTML = '<div class="pp-canvas-empty"><strong>Your playground concept</strong><span>Add products from the left.</span></div>';
      return;
    }
    canvas.innerHTML = `
      <div class="pp-concept-title">Your playground concept</div>
      <div class="pp-concept-grid">${state.selected.map((p, i) => `
        <div class="pp-concept-item" style="--i:${i}">
          ${p.image ? `<img src="${escapeHtml(p.image)}" alt="">` : '<div class="pp-concept-placeholder">3D</div>'}
          <strong>${escapeHtml(p.name)}</strong>
          <small>${escapeHtml(p.category || '')}</small>
          ${p.fall_height ? `<span>Fall height: ${escapeHtml(p.fall_height)}m</span>` : ''}
          ${Number(p.fall_height) > 0 ? '<em>Check surfacing requirements</em>' : ''}
          <button type="button" data-remove="${p.id}">Remove</button>
        </div>`).join('')}</div>`;
  }

  root.addEventListener('click', (e) => {
    const mode = e.target.closest('[data-pp-mode]');
    if (mode) {
      state.mode = mode.dataset.ppMode;
      start.hidden = true;
      if (state.mode === 'plan') controls.hidden = false;
      else { controls.hidden = true; workspace.hidden = false; loadProducts(); }
    }
    const add = e.target.closest('[data-add]');
    if (add) addProduct(add.dataset.add);
    const remove = e.target.closest('[data-remove]');
    if (remove) {
      state.selected = state.selected.filter(p => Number(p.id) !== Number(remove.dataset.remove));
      renderCanvas();
    }
    if (e.target.closest('[data-pp-load]')) {
      state.site = { width: $('[data-pp-width]').value, length: $('[data-pp-length]').value };
      controls.hidden = true;
      workspace.hidden = false;
      loadProducts();
    }
    if (e.target.closest('[data-pp-reset]')) {
      state.selected = [];
      state.site = {};
      state.mode = null;
      start.hidden = false;
      controls.hidden = true;
      workspace.hidden = true;
    }
  });

  searchEl.addEventListener('input', renderProducts);
  categoryEl.addEventListener('change', renderProducts);
  $('[data-pp-setting]').addEventListener('change', () => { if (!workspace.hidden) renderProducts(); });
  $('[data-pp-age]').addEventListener('change', () => { if (!workspace.hidden) renderProducts(); });
})();
