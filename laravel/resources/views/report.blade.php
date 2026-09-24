<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Database</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    <div class="max-w-5xl mx-auto p-6 space-y-6">
        <a href="/" class="text-blue-600 hover:underline">&larr; Kembali ke Dashboard</a>
        
        <header class="bg-white p-6 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold" id="filename">Memuat Laporan...</h1>
            <p class="text-gray-600">Status: <span id="status" class="font-semibold"></span></p>
        </header>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4">Entity Relationship Diagram (ERD)</h2>
            <div id="erd" class="mermaid border p-4 rounded bg-gray-50 overflow-x-auto"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4">Penjelasan Hubungan (AI)</h2>
            <div id="relationship" class="space-y-2"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4">Analisis Normalisasi</h2>
            <div id="normalization" class="space-y-2"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4 text-red-600">Potensi Masalah</h2>
                <ul id="problems" class="list-disc pl-5 space-y-1"></ul>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4 text-blue-600">Rekomendasi Index</h2>
                <ul id="indexes" class="list-disc pl-5 space-y-1"></ul>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4">Dokumentasi Migrasi</h2>
            <pre id="migration" class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm"></pre>
        </div>
    </div>

    <script>
        mermaid.initialize({ startOnLoad: false });
        const schemaId = Number("{{ $id }}");

        async function loadReport() {
            try {
                const res = await fetch(`/api/report/${schemaId}`);
                if (!res.ok) {
                    alert('Laporan tidak ditemukan.');
                    return;
                }
                const data = await res.json();
                document.getElementById('filename').innerText = `Laporan: ${data.schema.filename}`;
                document.getElementById('status').innerText = data.schema.status;

                const report = data.report;
                if (!report) return;

                // ERD
                const erdDiv = document.getElementById('erd');
                erdDiv.innerHTML = report.erd_mermaid;
                mermaid.init(undefined, erdDiv);

                // Relationship
                const relDiv = document.getElementById('relationship');
                if (report.relationship_explanation) {
                    let rel = report.relationship_explanation;
                    if (typeof rel === 'string') rel = { summary: rel, relations: [] };
                    relDiv.innerHTML = `<p class="font-medium">${rel.summary || ''}</p>`;
                    if (rel.relations && Array.isArray(rel.relations)) {
                        const ul = document.createElement('ul');
                        ul.className = 'list-disc pl-5 mt-2';
                        rel.relations.forEach(r => {
                            const li = document.createElement('li');
                            li.innerText = typeof r === 'string' ? r : JSON.stringify(r);
                            ul.appendChild(li);
                        });
                        relDiv.appendChild(ul);
                    }
                }

                // Normalization
                const normDiv = document.getElementById('normalization');
                if (report.normalization_analysis) {
                    let norm = report.normalization_analysis;
                    if (typeof norm === 'string') norm = { status: 'Normal', details: norm };
                    normDiv.innerHTML = `<p class="font-semibold text-green-600">${norm.status || ''}</p><p class="text-gray-700 mt-1">${norm.details || ''}</p>`;
                }

                // Problems
                const probUl = document.getElementById('problems');
                if (report.potential_problems && Array.isArray(report.potential_problems)) {
                    report.potential_problems.forEach(p => {
                        const li = document.createElement('li');
                        li.innerText = typeof p === 'string' ? p : (p.description || JSON.stringify(p));
                        probUl.appendChild(li);
                    });
                }

                // Indexes
                const idxUl = document.getElementById('indexes');
                if (report.index_recommendations && Array.isArray(report.index_recommendations)) {
                    report.index_recommendations.forEach(i => {
                        const li = document.createElement('li');
                        li.innerText = typeof i === 'string' ? i : (i.reason || JSON.stringify(i));
                        idxUl.appendChild(li);
                    });
                }

                // Migration
                document.getElementById('migration').innerText = report.migration_documentation || 'Tidak ada dokumentasi migrasi.';

            } catch (err) {
                console.error(err);
                alert('Gagal memuat data laporan.');
            }
        }

        loadReport();
    </script>
</body>
</html>
