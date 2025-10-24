/**
 * SQL Tooltip System
 * Educational feature that displays SQL queries on hover
 * Provides syntax highlighting and educational explanations
 */

class SQLTooltip {
    constructor() {
        this.tooltip = null;
        this.init();
    }

    init() {
        // Create tooltip element
        this.createTooltip();
        
        // Attach event listeners to all SQL-enabled elements
        this.attachEventListeners();
        
        // Initialize syntax highlighting
        this.initSyntaxHighlighting();
    }

    createTooltip() {
        // Create tooltip container
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'sql-tooltip hidden';
        this.tooltip.innerHTML = `
            <div class="sql-tooltip-header">
                <span class="sql-tooltip-title">📊 SQL Query</span>
                <button class="sql-tooltip-close" onclick="sqlTooltip.hide()">&times;</button>
            </div>
            <div class="sql-tooltip-content">
                <pre><code class="sql-code"></code></pre>
            </div>
            <div class="sql-tooltip-footer">
                <span class="sql-tooltip-type"></span>
                <span class="sql-tooltip-explanation"></span>
            </div>
        `;
        document.body.appendChild(this.tooltip);
    }

    attachEventListeners() {
        // Bind SQL elements immediately (DOM is ready when this is called)
        this.bindSQLElements();
    }

    bindSQLElements() {
        const sqlElements = document.querySelectorAll('[data-sql-query]');
        
        console.log(`SQL Tooltip: Found ${sqlElements.length} elements with data-sql-query`);
        
        sqlElements.forEach(element => {
            // Hover to show
            element.addEventListener('mouseenter', (e) => {
                this.show(e.currentTarget);
            });

            // Leave to hide (with delay)
            element.addEventListener('mouseleave', (e) => {
                setTimeout(() => {
                    if (!this.tooltip.matches(':hover')) {
                        this.hide();
                    }
                }, 200);
            });

            // Click to pin
            element.addEventListener('click', (e) => {
                if (e.currentTarget.hasAttribute('data-sql-query')) {
                    e.preventDefault();
                    this.pin(e.currentTarget);
                }
            });

            // Add visual indicator
            element.classList.add('sql-interactive');
        });
    }

    show(element) {
        const query = element.getAttribute('data-sql-query');
        const explanation = element.getAttribute('data-sql-explanation') || 'No explanation provided';
        const type = element.getAttribute('data-sql-type') || this.detectQueryType(query);

        // Populate tooltip
        this.tooltip.querySelector('.sql-code').textContent = this.formatSQL(query);
        this.tooltip.querySelector('.sql-tooltip-type').textContent = type;
        this.tooltip.querySelector('.sql-tooltip-explanation').textContent = explanation;

        // Position tooltip
        this.position(element);

        // Show tooltip
        this.tooltip.classList.remove('hidden');
        this.tooltip.classList.add('visible');

        // Highlight syntax
        this.highlightSyntax();
    }

    hide() {
        this.tooltip.classList.remove('visible');
        this.tooltip.classList.add('hidden');
    }

    pin(element) {
        const query = element.getAttribute('data-sql-query');
        const explanation = element.getAttribute('data-sql-explanation') || 'No explanation provided';
        const type = element.getAttribute('data-sql-type') || this.detectQueryType(query);

        // Create modal for pinned view
        this.showModal(query, type, explanation);
    }

    position(element) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();

        let top = rect.bottom + window.scrollY + 10;
        let left = rect.left + window.scrollX;

        // Adjust if tooltip goes off screen
        if (left + tooltipRect.width > window.innerWidth) {
            left = window.innerWidth - tooltipRect.width - 20;
        }

        if (top + tooltipRect.height > window.innerHeight + window.scrollY) {
            top = rect.top + window.scrollY - tooltipRect.height - 10;
        }

        this.tooltip.style.top = `${top}px`;
        this.tooltip.style.left = `${left}px`;
    }

    formatSQL(query) {
        // Basic SQL formatting
        let formatted = query.trim();
        
        // Add line breaks for readability
        const keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'INNER JOIN', 'LEFT JOIN', 
                         'RIGHT JOIN', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT', 
                         'INSERT INTO', 'UPDATE', 'DELETE FROM', 'SET', 'VALUES', 'AND', 'OR'];
        
        keywords.forEach(keyword => {
            const regex = new RegExp(`\\b${keyword}\\b`, 'gi');
            formatted = formatted.replace(regex, `\n${keyword}`);
        });

        return formatted.trim();
    }

    detectQueryType(query) {
        const upperQuery = query.trim().toUpperCase();
        
        if (upperQuery.startsWith('SELECT')) return 'SELECT Query';
        if (upperQuery.startsWith('INSERT')) return 'INSERT Operation';
        if (upperQuery.startsWith('UPDATE')) return 'UPDATE Operation';
        if (upperQuery.startsWith('DELETE')) return 'DELETE Operation';
        if (upperQuery.startsWith('CREATE')) return 'DDL - CREATE';
        if (upperQuery.startsWith('ALTER')) return 'DDL - ALTER';
        if (upperQuery.startsWith('DROP')) return 'DDL - DROP';
        if (upperQuery.startsWith('CALL')) return 'Stored Procedure Call';
        
        return 'SQL Query';
    }

    highlightSyntax() {
        const code = this.tooltip.querySelector('.sql-code');
        const text = code.textContent;

        // Simple syntax highlighting
        let highlighted = text
            .replace(/\b(SELECT|FROM|WHERE|JOIN|INNER|LEFT|RIGHT|OUTER|ON|AS|AND|OR|NOT|IN|EXISTS|GROUP BY|ORDER BY|HAVING|LIMIT|OFFSET|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|ALTER|DROP|TABLE|VIEW|INDEX|PROCEDURE|FUNCTION|TRIGGER|CALL)\b/gi, 
                '<span class="sql-keyword">$1</span>')
            .replace(/\b(COUNT|SUM|AVG|MAX|MIN|ROUND|COALESCE|IFNULL|CASE|WHEN|THEN|ELSE|END)\b/gi, 
                '<span class="sql-function">$1</span>')
            .replace(/'([^']*)'/g, '<span class="sql-string">\'$1\'</span>')
            .replace(/\b(\d+)\b/g, '<span class="sql-number">$1</span>')
            .replace(/--.*$/gm, '<span class="sql-comment">$&</span>');

        code.innerHTML = highlighted;
    }

    initSyntaxHighlighting() {
        // Apply syntax highlighting to all pre-existing code blocks
        document.querySelectorAll('.sql-code-display').forEach(block => {
            this.highlightCodeBlock(block);
        });
    }

    highlightCodeBlock(block) {
        const text = block.textContent;
        let highlighted = text
            .replace(/\b(SELECT|FROM|WHERE|JOIN|INNER|LEFT|RIGHT|OUTER|ON|AS|AND|OR|NOT|IN|EXISTS|GROUP BY|ORDER BY|HAVING|LIMIT|OFFSET|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|ALTER|DROP|TABLE|VIEW|INDEX|PROCEDURE|FUNCTION|TRIGGER|CALL|WITH|UNION|INTERSECT|EXCEPT)\b/gi, 
                '<span class="sql-keyword">$1</span>')
            .replace(/\b(COUNT|SUM|AVG|MAX|MIN|ROUND|COALESCE|IFNULL|CASE|WHEN|THEN|ELSE|END|DISTINCT|ALL|ANY|SOME)\b/gi, 
                '<span class="sql-function">$1</span>')
            .replace(/'([^']*)'/g, '<span class="sql-string">\'$1\'</span>')
            .replace(/\b(\d+)\b/g, '<span class="sql-number">$1</span>')
            .replace(/--.*$/gm, '<span class="sql-comment">$&</span>');

        block.innerHTML = highlighted;
    }

    showModal(query, type, explanation) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'sql-modal';
        modal.innerHTML = `
            <div class="sql-modal-content">
                <div class="sql-modal-header">
                    <h3>📋 ${type}</h3>
                    <button class="sql-modal-close" onclick="this.closest('.sql-modal').remove()">&times;</button>
                </div>
                <div class="sql-modal-body">
                    <div class="sql-explanation">
                        <strong>Explanation:</strong> ${explanation}
                    </div>
                    <div class="sql-query-container">
                        <pre><code class="sql-code-display">${this.formatSQL(query)}</code></pre>
                    </div>
                    <div class="sql-features">
                        <strong>SQL Features Demonstrated:</strong>
                        <ul>
                            ${this.extractFeatures(query).map(f => `<li>${f}</li>`).join('')}
                        </ul>
                    </div>
                </div>
                <div class="sql-modal-footer">
                    <button class="btn-copy" onclick="sqlTooltip.copyToClipboard(\`${query.replace(/`/g, '\\`')}\`)">
                        📋 Copy Query
                    </button>
                    <button class="btn-close" onclick="this.closest('.sql-modal').remove()">
                        Close
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Highlight the code in modal
        this.highlightCodeBlock(modal.querySelector('.sql-code-display'));

        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    extractFeatures(query) {
        const features = [];
        const upperQuery = query.toUpperCase();

        if (upperQuery.includes('JOIN')) features.push('Table JOIN operations');
        if (upperQuery.includes('GROUP BY')) features.push('GROUP BY aggregation');
        if (upperQuery.includes('HAVING')) features.push('HAVING clause for filtered aggregation');
        if (upperQuery.includes('ORDER BY')) features.push('ORDER BY sorting');
        if (upperQuery.includes('LIMIT')) features.push('LIMIT for pagination');
        if (upperQuery.includes('COUNT(') || upperQuery.includes('SUM(') || upperQuery.includes('AVG(')) 
            features.push('Aggregate functions (COUNT, SUM, AVG)');
        if (upperQuery.includes('CASE')) features.push('CASE conditional logic');
        if (upperQuery.includes('COALESCE') || upperQuery.includes('IFNULL')) 
            features.push('NULL handling functions');
        if (upperQuery.includes('DISTINCT')) features.push('DISTINCT for unique values');
        if (upperQuery.includes('UNION')) features.push('UNION set operation');
        if (upperQuery.includes('EXISTS')) features.push('EXISTS subquery');
        if (upperQuery.includes('WITH')) features.push('CTE (Common Table Expression)');
        if (upperQuery.includes('CALL')) features.push('Stored Procedure execution');

        return features.length > 0 ? features : ['Basic SQL query'];
    }

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Query copied to clipboard!');
        });
    }
}

// Initialize tooltip system when DOM is ready
let sqlTooltip;

// Check if DOM is already loaded (script is at bottom of page)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('SQL Tooltip: Initializing (DOMContentLoaded)');
        sqlTooltip = new SQLTooltip();
    });
} else {
    // DOM already loaded, initialize immediately
    console.log('SQL Tooltip: Initializing (DOM already loaded)');
    sqlTooltip = new SQLTooltip();
}

// Feature Panel Toggle
function toggleFeaturePanel(panelId) {
    const panel = document.getElementById(panelId);
    if (panel) {
        panel.classList.toggle('collapsed');
    }
}

// Query History Management
class QueryHistory {
    constructor() {
        this.queries = [];
        this.maxQueries = 100;
    }

    add(query, type, page) {
        this.queries.unshift({
            query: query,
            type: type,
            page: page,
            timestamp: new Date()
        });

        // Keep only max queries
        if (this.queries.length > this.maxQueries) {
            this.queries = this.queries.slice(0, this.maxQueries);
        }

        this.save();
    }

    save() {
        sessionStorage.setItem('sql_query_history', JSON.stringify(this.queries));
    }

    load() {
        const stored = sessionStorage.getItem('sql_query_history');
        if (stored) {
            this.queries = JSON.parse(stored);
        }
    }

    getAll() {
        return this.queries;
    }

    clear() {
        this.queries = [];
        this.save();
    }
}

const queryHistory = new QueryHistory();
queryHistory.load();
