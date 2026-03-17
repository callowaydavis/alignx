import './bootstrap';

// ─── Tab switching (component show page) ────────────────────────────────────

document.addEventListener('click', async (e) => {
    const tabBtn = e.target.closest('[data-switch-tab]');
    if (tabBtn) {
        const tab = tabBtn.dataset.switchTab;
        switchTab(tab);

        if (tab === 'diagrams') {
            const { initDiagrams } = await import('./component-diagrams.js');
            await initDiagrams();
        }
    }

    const diagramTypeBtn = e.target.closest('[data-diagram-type]');
    if (diagramTypeBtn) {
        const { switchDiagramType } = await import('./component-diagrams.js');
        switchDiagramType(diagramTypeBtn.dataset.diagramType);
    }

    const fitBtn = e.target.closest('[data-diagram-fit]');
    if (fitBtn) {
        const { fitDiagram } = await import('./component-diagrams.js');
        fitDiagram();
    }

    const zoomInBtn = e.target.closest('[data-diagram-zoom-in]');
    if (zoomInBtn) {
        const { zoomIn } = await import('./component-diagrams.js');
        zoomIn();
    }

    const zoomOutBtn = e.target.closest('[data-diagram-zoom-out]');
    if (zoomOutBtn) {
        const { zoomOut } = await import('./component-diagrams.js');
        zoomOut();
    }
});

function switchTab(tabName) {
    document.querySelectorAll('[data-tab-content]').forEach((el) => {
        el.classList.toggle('hidden', el.dataset.tabContent !== tabName);
    });

    document.querySelectorAll('[data-switch-tab]').forEach((btn) => {
        const active = btn.dataset.switchTab === tabName;
        btn.classList.toggle('border-blue-500', active);
        btn.classList.toggle('text-blue-600', active);
        btn.classList.toggle('border-transparent', !active);
        btn.classList.toggle('text-gray-500', !active);
        btn.classList.toggle('hover:text-gray-700', !active);
    });
}
