const TYPE_COLORS = {
    'Application': '#3B82F6',
    'Interface': '#8B5CF6',
    'Data Object': '#10B981',
    'IT Component': '#F97316',
    'Provider': '#14B8A6',
    'Process': '#CA8A04',
    'Business Capability': '#EF4444',
};

let cyInstance = null;
let initialized = false;

// ─── Public API ─────────────────────────────────────────────────────────────

export async function initDiagrams() {
    if (initialized) return;
    initialized = true;

    const dataEl = document.getElementById('graph-data');
    if (!dataEl) return;

    const graphData = JSON.parse(dataEl.textContent);
    if (!graphData.edges.length) {
        hideLoading();
        return;
    }

    try {
        const [{ default: cytoscape }, { default: cytoscapeDagre }] = await Promise.all([
            import('cytoscape'),
            import('cytoscape-dagre'),
        ]);

        cytoscape.use(cytoscapeDagre);

        cyInstance = cytoscape({
            container: document.getElementById('cy-canvas'),
            elements: buildElements(graphData),
            style: buildStyle(),
            layout: networkLayout(),
            minZoom: 0.1,
            maxZoom: 4,
            wheelSensitivity: 0.3,
        });

        hideLoading();
    } catch (err) {
        const loading = document.getElementById('diagram-loading');
        if (loading) {
            loading.textContent = 'Failed to load diagram.';
        }
    }
}

export function switchDiagramType(type) {
    const canvas = document.getElementById('cy-canvas');
    const landscape = document.getElementById('landscape-view');
    const toolbar = document.getElementById('diagram-toolbar');

    document.querySelectorAll('[data-diagram-type]').forEach((btn) => {
        const active = btn.dataset.diagramType === type;
        btn.classList.toggle('bg-white', active);
        btn.classList.toggle('shadow-sm', active);
        btn.classList.toggle('text-gray-900', active);
        btn.classList.toggle('text-gray-500', !active);
        btn.classList.toggle('hover:text-gray-700', !active);
    });

    if (type === 'landscape') {
        canvas.classList.add('hidden');
        landscape.classList.remove('hidden');
        toolbar.classList.add('invisible');
        return;
    }

    canvas.classList.remove('hidden');
    landscape.classList.add('hidden');
    toolbar.classList.remove('invisible');

    if (!cyInstance) return;

    const layout = type === 'network' ? networkLayout() : dependencyLayout();
    cyInstance.layout(layout).run();
}

export function fitDiagram() {
    cyInstance?.fit(undefined, 40);
}

export function zoomIn() {
    if (!cyInstance) return;
    cyInstance.zoom({
        level: cyInstance.zoom() * 1.3,
        renderedPosition: { x: cyInstance.width() / 2, y: cyInstance.height() / 2 },
    });
}

export function zoomOut() {
    if (!cyInstance) return;
    cyInstance.zoom({
        level: cyInstance.zoom() / 1.3,
        renderedPosition: { x: cyInstance.width() / 2, y: cyInstance.height() / 2 },
    });
}

// ─── Internals ───────────────────────────────────────────────────────────────

function buildElements(graphData) {
    const nodes = graphData.nodes.map((n) => ({
        data: {
            id: n.id,
            name: n.name,
            type: n.type,
            isRoot: n.isRoot ? 1 : 0,
            color: TYPE_COLORS[n.type] ?? '#6B7280',
        },
    }));

    const edges = graphData.edges.map((e) => ({
        data: {
            id: e.id,
            source: e.source,
            target: e.target,
            label: e.label,
        },
    }));

    return [...nodes, ...edges];
}

function buildStyle() {
    return [
        {
            selector: 'node',
            style: {
                label: 'data(name)',
                'background-color': 'data(color)',
                color: '#ffffff',
                'text-valign': 'center',
                'text-halign': 'center',
                'font-size': '11px',
                width: 140,
                height: 46,
                shape: 'round-rectangle',
                'text-wrap': 'wrap',
                'text-max-width': '128px',
                'font-family': 'ui-sans-serif, system-ui, sans-serif',
            },
        },
        {
            selector: 'node[isRoot = 1]',
            style: {
                'border-width': 3,
                'border-color': 'rgba(255,255,255,0.8)',
                width: 160,
                height: 54,
                'font-size': '12px',
                'font-weight': 'bold',
            },
        },
        {
            selector: 'node:selected',
            style: {
                'border-width': 3,
                'border-color': '#1D4ED8',
                'border-opacity': 1,
            },
        },
        {
            selector: 'edge',
            style: {
                label: 'data(label)',
                'curve-style': 'bezier',
                'target-arrow-shape': 'triangle',
                'arrow-scale': 1.1,
                'line-color': '#CBD5E1',
                'target-arrow-color': '#CBD5E1',
                'font-size': '10px',
                color: '#64748B',
                'text-rotation': 'autorotate',
                'text-margin-y': -9,
                'font-family': 'ui-sans-serif, system-ui, sans-serif',
                'text-background-color': '#ffffff',
                'text-background-opacity': 0.85,
                'text-background-padding': '2px',
            },
        },
        {
            selector: 'edge:selected',
            style: {
                'line-color': '#3B82F6',
                'target-arrow-color': '#3B82F6',
            },
        },
    ];
}

function networkLayout() {
    return {
        name: 'cose',
        nodeRepulsion: 9000,
        gravity: 0.3,
        numIter: 1000,
        animate: true,
        animationDuration: 600,
        fit: true,
        padding: 40,
    };
}

function dependencyLayout() {
    return {
        name: 'dagre',
        rankDir: 'LR',
        rankSep: 130,
        nodeSep: 55,
        padding: 40,
        animate: true,
        animationDuration: 600,
        fit: true,
    };
}

function hideLoading() {
    document.getElementById('diagram-loading')?.remove();
}
