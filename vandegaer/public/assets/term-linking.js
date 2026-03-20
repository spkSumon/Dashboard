// ========================================
// DYNAMIC TERM LINKING
// ========================================

let glossaryTermsIndex = [];
let termLinkingEnabled = true;

/**
 * Initialize glossary term linking on page load
 */
async function initializeTermLinking() {
  try {
    const res = await fetch(apiUrl('api/glossary'));
    const terms = await res.json();

    if (res.ok && Array.isArray(terms)) {
      glossaryTermsIndex = terms
        .map(t => ({
          term: t.term,
          definition: t.definition,
          id: t.id,
          variants: generateTermVariants(t.term)
        }))
        .sort((a, b) => b.term.length - a.term.length); // Longer terms first to avoid partial matches

      console.log(`Loaded ${glossaryTermsIndex.length} terms for dynamic linking`);
    }
  } catch (err) {
    console.error('Failed to load glossary for term linking:', err);
  }
}

/**
 * Generate term variants for matching (singular/plural, case variations)
 */
function generateTermVariants(term) {
  const variants = [term.toLowerCase()];

  // Add plural variants
  if (!term.endsWith('s')) {
    variants.push(term.toLowerCase() + 's');
  }

  // Add Dutch common plurals
  if (term.endsWith('e')) {
    variants.push(term.toLowerCase() + 'n');
  }

  return variants;
}

/**
 * Apply term linking to a specific element
 * @param {HTMLElement} element - Element to scan and link terms
 * @param {Object} options - { linkFirstOnly: boolean, excludeSelectors: string[] }
 */
function applyTermLinking(element, options = {}) {
  if (!element || !termLinkingEnabled || glossaryTermsIndex.length === 0) return;

  const {
    linkFirstOnly = true,
    excludeSelectors = ['a', 'button', 'input', 'textarea', 'code', 'pre', '.glossary-term', '.no-link']
  } = options;

  const linkedTerms = new Set();

  // Get all text nodes
  const walker = document.createTreeWalker(
    element,
    NodeFilter.SHOW_TEXT,
    {
      acceptNode: (node) => {
        // Skip if parent is excluded selector
        if (excludeSelectors.some(sel => node.parentElement.closest(sel))) {
          return NodeFilter.FILTER_REJECT;
        }

        // Skip empty or whitespace-only nodes
        if (!node.textContent.trim()) {
          return NodeFilter.FILTER_REJECT;
        }

        return NodeFilter.FILTER_ACCEPT;
      }
    }
  );

  const textNodes = [];
  let currentNode;
  while (currentNode = walker.nextNode()) {
    textNodes.push(currentNode);
  }

  // Process each text node
  textNodes.forEach(textNode => {
    let text = textNode.textContent;
    let replacements = [];

    // Find all matching terms
    glossaryTermsIndex.forEach(termData => {
      if (linkFirstOnly && linkedTerms.has(termData.term)) return;

      termData.variants.forEach(variant => {
        // Create regex for word boundary matching
        const regex = new RegExp(`\\b(${escapeRegex(variant)})\\b`, 'gi');
        let match;

        while ((match = regex.exec(text)) !== null) {
          // Check if this position overlaps with existing replacement
          const overlaps = replacements.some(r =>
            (match.index >= r.start && match.index < r.end) ||
            (match.index + match[0].length > r.start && match.index + match[0].length <= r.end)
          );

          if (!overlaps) {
            replacements.push({
              start: match.index,
              end: match.index + match[0].length,
              matchedText: match[0],
              termData: termData
            });

            if (linkFirstOnly) {
              linkedTerms.add(termData.term);
              return; // Stop after first match
            }
          }
        }
      });
    });

    // Apply replacements (reverse order to maintain indices)
    if (replacements.length > 0) {
      replacements.sort((a, b) => b.start - a.start);

      const fragment = document.createDocumentFragment();
      let lastIndex = text.length;

      replacements.forEach(replacement => {
        // Add text after this replacement
        if (lastIndex > replacement.end) {
          fragment.insertBefore(
            document.createTextNode(text.substring(replacement.end, lastIndex)),
            fragment.firstChild
          );
        }

        // Create linked term element
        const termElement = createLinkedTerm(replacement.matchedText, replacement.termData);
        fragment.insertBefore(termElement, fragment.firstChild);

        lastIndex = replacement.start;
      });

      // Add remaining text before first replacement
      if (lastIndex > 0) {
        fragment.insertBefore(
          document.createTextNode(text.substring(0, lastIndex)),
          fragment.firstChild
        );
      }

      // Replace text node with fragment
      textNode.parentNode.replaceChild(fragment, textNode);
    }
  });
}

/**
 * Create a linked term element with tooltip
 */
function createLinkedTerm(matchedText, termData) {
  const span = document.createElement('span');
  span.className = 'glossary-term';
  span.textContent = matchedText;
  span.setAttribute('data-term-id', termData.id);
  span.setAttribute('data-term', termData.term);
  span.setAttribute('title', termData.definition);

  // Click handler
  span.onclick = (e) => {
    e.preventDefault();
    window.location.hash = '#/glossary';

    // Highlight term after navigation
    setTimeout(() => {
      const termCard = document.querySelector(`[data-term-id="${termData.id}"]`);
      if (termCard) {
        termCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        termCard.classList.add('glossary-card--highlight');
        setTimeout(() => termCard.classList.remove('glossary-card--highlight'), 2000);
      }
    }, 300);
  };

  // Create custom tooltip
  const tooltip = document.createElement('span');
  tooltip.className = 'glossary-tooltip';
  tooltip.innerHTML = `
    <strong>${escapeHtml(termData.term)}</strong><br>
    ${escapeHtml(termData.definition.substring(0, 150))}${termData.definition.length > 150 ? '...' : ''}
  `;

  span.appendChild(tooltip);

  return span;
}

/**
 * Escape regex special characters
 */
function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Apply term linking to dashboard KPIs
 */
function linkTermsInDashboard() {
  // Link terms in KPI labels
  const kpiLabels = document.querySelectorAll('.kpi__value + .kpi__label, .field__label, .insight-card__message, .insight-card__recommendation');
  kpiLabels.forEach(label => {
    applyTermLinking(label, { linkFirstOnly: true });
  });
}

/**
 * Apply term linking to post detail modal
 */
function linkTermsInPostDetail() {
  const modal = document.getElementById('postDetailModal');
  if (modal) {
    const content = modal.querySelector('.modal__body');
    if (content) {
      applyTermLinking(content, { linkFirstOnly: true });
    }
  }
}

/**
 * Apply term linking to glossary term detail modal
 */
function linkTermsInTermDetail() {
  const modal = document.getElementById('termDetailModal');
  if (modal) {
    const content = modal.querySelector('.modal__body');
    if (content) {
      // In glossary, link all occurrences (not just first)
      applyTermLinking(content, { linkFirstOnly: false });
    }
  }
}

// Initialize term linking on page load
document.addEventListener('DOMContentLoaded', () => {
  initializeTermLinking();

  // Apply term linking after initial load
  setTimeout(() => {
    linkTermsInDashboard();
  }, 1000); // Wait for dashboard data to load
});

// Re-apply term linking after route changes
window.addEventListener('hashchange', () => {
  setTimeout(() => {
    const route = window.location.hash.replace('#', '') || '/overview';

    if (route === '/overview' || route === '/posts') {
      linkTermsInDashboard();
    }
  }, 800); // Wait for content to render
});

// Helper function to trigger term linking after dynamic content loads
// Call this from app.js after loading insights, KPIs, etc.
function triggerTermLinking() {
  if (glossaryTermsIndex.length === 0) {
    // Terms not loaded yet, initialize first
    initializeTermLinking().then(() => {
      linkTermsInDashboard();
    });
  } else {
    linkTermsInDashboard();
  }
}
