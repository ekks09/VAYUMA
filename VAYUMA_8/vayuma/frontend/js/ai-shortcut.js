(() => {
  const addShortcut = () => {
    if (!document.body || document.querySelector('.va-ai-shortcut')) return;
    const link = document.createElement('a');
    link.className = 'va-ai-shortcut';
    link.href = 'feature_access.php?feature=ai';
    const sw = document.documentElement.lang.toLowerCase().startsWith('sw');
    link.setAttribute('aria-label', sw ? 'Fungua VAYUMA TRIX AI' : 'Open VAYUMA TRIX AI');
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '22'); svg.setAttribute('height', '22'); svg.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', 'frontend/images/icons.svg#va-icon-ai'); svg.appendChild(use);
    const label = document.createElement('span'); label.textContent = 'VAYUMA TRIX AI';
    link.append(svg, label); document.body.appendChild(link);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addShortcut, {once:true});
  else addShortcut();
})();
