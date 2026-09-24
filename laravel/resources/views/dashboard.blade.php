<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExplainMyDatabase - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    <div class="max-w-4xl mx-auto p-6">
        <header class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-blue-600">ExplainMyDatabase</h1>
            <p class="text-gray-600 mt-2">Upload file SQL atau paste DDL Anda untuk analisis otomatis, ERD, dan rekomendasi AI.</p>
        </header>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-bold mb-4">Upload Skema Database (SQL)</h2>
            <form id="analyzeForm" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Pilih File SQL (.sql, .txt):</label>
                    <input type="file" id="sql_file" name="sql_file" accept=".sql,.txt" class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block font-medium mb-1">Atau Paste DDL SQL di Sini:</label>
                    <textarea id="raw_sql" name="raw_sql" rows="6" class="w-full border p-2 rounded font-mono text-sm" placeholder="CREATE TABLE users (...);"></textarea>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded font-semibold hover:bg-blue-700">Analisis Skema</button>
            </form>
            <div id="loading" class="hidden mt-4 text-blue-600 font-semibold">Sedang menganalisis database, mohon tunggu...</div>
        </div>

        <div id="resultSection" class="hidden bg-white p-6 rounded-lg shadow-md space-y-6">
            <h2 class="text-2xl font-bold text-gray-800">Hasil Analisis</h2>
            <div class="flex space-x-4">
                <a id="reportLink" href="#" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded font-semibold hover:bg-green-700">Buka Haporan Detail</a>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-2">ERD Mermaid</h3>
                <div id="erdContainer" class="mermaid border p-4 rounded bg-gray-50 overflow-x-auto"></div>
            </div>
        </div>
    </div>

    <script>
        mermaid.initialize({ startOnLoad: false });

        document.getElementById('analyzeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const loading = document.getElementById('loading');
            const resultSection = document.getElementById('resultSection');
            
            loading.classList.remove('hidden');
            resultSection.classList.add('hidden');

            try {
                const res = await fetch('/api/analyze', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!res.ok) {
                    alert(data.error || 'Terjadi kesalahan.');
                    loading.classList.add('hidden');
                    return;
                }

                const schemaId = data.schema_id;
                
                // Polling for completion
                let status = data.status;
                let reportData = null;

                while (status !== 'completed' && status !== 'failed') {
                    await new Promise(r => setTimeout(r, 2000));
                    const reportRes = await fetch(`/api/report/${schemaId}`);
                    if (reportRes.ok) {
                        const json = await reportRes.json();
                        status = json.schema.status;
                        reportData = json;
                    }
                }

                loading.classList.add('hidden');

                if (status === 'failed') {
                    alert('Analisis gagal.');
                    return;
                }

                if (reportData && reportData.report) {
                    document.getElementById('reportLink').href = `/report-view/${schemaId}`;
                    
                    const erdDiv = document.getElementById('erdContainer');
                    erdDiv.innerHTML = reportData.report.erd_mermaid;
                    mermaid.init(undefined, erdDiv);
                    
                    resultSection.classList.remove('hidden');
                }

            } catch (err) {
                console.error(err);
                alert('Gagal menghubungi server.');
                loading.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
