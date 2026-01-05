// Theme Toggle Functionality
(function() {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    
    function createToggleButton() {
        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.innerHTML = theme === 'dark' ? '☀️' : '🌙';
        button.setAttribute('aria-label', 'Toggle theme');
        button.onclick = toggleTheme;
        document.body.appendChild(button);
    }
    
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        const button = document.querySelector('.theme-toggle');
        if (button) {
            button.innerHTML = newTheme === 'dark' ? '☀️' : '🌙';
        }
        
        // Update Chart.js colors if charts exist
        if (typeof Chart !== 'undefined' && window.monthlyChart) {
            updateChartColors();
        }
    }
    
    function updateChartColors() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#E5E7EB' : '#1F2933';
        const gridColor = isDark ? '#334155' : '#E5E7EB';
        
        // Update all Chart.js instances
        Chart.instances.forEach(chart => {
            chart.options.plugins.legend.labels.color = textColor;
            chart.options.scales.x.ticks.color = textColor;
            chart.options.scales.x.grid.color = gridColor;
            chart.options.scales.y.ticks.color = textColor;
            chart.options.scales.y.grid.color = gridColor;
            chart.update();
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createToggleButton);
    } else {
        createToggleButton();
    }
})();