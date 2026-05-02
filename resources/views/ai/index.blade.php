@extends('layouts.main')

@section('title', 'AI Analytics Dashboard')

@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-2" style="background: linear-gradient(135deg, #10b981, #3b82f6); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    AI Analytics Dashboard
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">AI Analytics</li>
                    </ol>
                </nav>
                <p class="text-muted mt-2 mb-0">AI-powered insights and predictions for your collection data</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark py-2 px-3">
                    <i class="bi bi-robot me-1" style="color: #10b981;"></i>
                    Powered by AI
                </span>
            </div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="row g-4 mb-4">
        <!-- Report Summarizer Card -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="cursor: pointer; transition: all 0.3s ease;" onclick="openAIModal('summarizer')" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-file-text-fill fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-semibold">Report Summarizer</h5>
                    <p class="text-muted small">Get intelligent summaries of collection reports with key insights and trends.</p>
                    <button class="btn btn-sm btn-outline-success rounded-pill mt-2">Analyze <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>
        </div>

        <!-- Face Value Trend Analysis Card -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="cursor: pointer; transition: all 0.3s ease;" onclick="openAIModal('trends')" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-graph-up fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-semibold">Face Value Trend Analysis</h5>
                    <p class="text-muted small">Analyze allocation patterns and identify usage trends across clerks.</p>
                    <button class="btn btn-sm btn-outline-success rounded-pill mt-2">Analyze <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>
        </div>

        <!-- Exhaustion Prediction Card -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="cursor: pointer; transition: all 0.3s ease;" onclick="openAIModal('prediction')" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-calendar-check fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-semibold">Exhaustion Prediction</h5>
                    <p class="text-muted small">Predict when face value batches will run out based on usage patterns.</p>
                    <button class="btn btn-sm btn-outline-success rounded-pill mt-2">Predict <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Filters Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-bottom">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-funnel me-2 text-success"></i>
                Quick Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Date Range</label>
                    <select id="quickDateRange" class="form-select" onchange="updateDateRange()">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3" id="customDateRangeStart" style="display: none;">
                    <label class="form-label fw-semibold small">Start Date</label>
                    <input type="date" id="customStartDate" class="form-control">
                </div>
                <div class="col-md-3" id="customDateRangeEnd" style="display: none;">
                    <label class="form-label fw-semibold small">End Date</label>
                    <input type="date" id="customEndDate" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Network</label>
                    <select id="quickNetwork" class="form-select">
                        <option value="">All Networks</option>
                        @foreach($networks ?? [] as $network)
                            <option value="{{ $network->id }}">{{ $network->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Site</label>
                    <select id="quickSite" class="form-select">
                        <option value="">All Sites</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent AI Analyses -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-clock-history me-2 text-success"></i>
                    Recent AI Analyses
                </h5>
                <button class="btn btn-sm btn-outline-success rounded-pill" onclick="refreshAnalyses()">
                    <i class="bi bi-arrow-repeat me-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Summary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recentAnalysesTable">
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2 mb-0">No recent analyses found</p>
                                <small>Run an analysis to see results here</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AI Analysis Modal -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-labelledby="aiAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #3b82f6);">
                <div>
                    <h5 class="modal-title text-white" id="aiAnalysisModalLabel">
                        <i class="bi bi-robot me-2"></i>
                        <span id="modalTitle">AI Analysis</span>
                    </h5>
                    <p class="modal-title small text-white-50 mt-1" id="modalSubtitle">
                        AI-powered insights from your data
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="aiAnalysisContent" style="min-height: 400px; max-height: 70vh; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-3 text-muted">Generating AI analysis...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Close
                </button>
                <button type="button" class="btn btn-success" onclick="exportAnalysisAsPDF()">
                    <i class="bi bi-file-pdf me-1"></i>Export PDF
                </button>
                <button type="button" class="btn btn-outline-success" onclick="exportAnalysisAsHTML()">
                    <i class="bi bi-download me-1"></i>Export HTML
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom styles that work with your main layout */
    .analysis-content {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1.5rem;
        line-height: 1.6;
    }
    
    .analysis-content h2 {
        color: #10b981;
        font-size: 1.5rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    
    .analysis-content h3 {
        color: #3b82f6;
        font-size: 1.2rem;
        margin-top: 1.25rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }
    
    .analysis-content p {
        margin-bottom: 0.75rem;
        color: #374151;
    }
    
    .analysis-content ul, .analysis-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .analysis-content li {
        margin-bottom: 0.5rem;
        color: #4b5563;
    }
    
    .analysis-content .stat-box {
        background: white;
        padding: 1.25rem;
        border-radius: 0.5rem;
        margin: 1rem 0;
        border-left: 4px solid #10b981;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .analysis-content .success-box {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02));
        padding: 1.25rem;
        border-radius: 0.5rem;
        margin: 1rem 0;
        border-left: 4px solid #10b981;
    }
    
    .analysis-content .warning-box {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
        padding: 1.25rem;
        border-radius: 0.5rem;
        margin: 1rem 0;
        border-left: 4px solid #f59e0b;
    }
    
    .analysis-content .progress-bar-container {
        position: relative;
        width: 100%;
        background: #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
        height: 28px;
        margin: 0.5rem 0;
    }
    
    .analysis-content .progress-bar-fill {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        transition: width 0.3s ease;
        border-radius: 1rem;
    }
    
    .analysis-content .progress-bar-text {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        font-size: 0.75rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .analysis-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
    }
    
    .analysis-content th {
        background: linear-gradient(135deg, #10b981, #3b82f6);
        color: white;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
    }
    
    .analysis-content td {
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .analysis-content tr:hover {
        background: rgba(16, 185, 129, 0.05);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .analysis-content {
            padding: 1rem;
            font-size: 0.875rem;
        }
        
        .analysis-content h2 {
            font-size: 1.25rem;
        }
        
        .analysis-content h3 {
            font-size: 1rem;
        }
    }
    
    /* Print styles */
    @media print {
        .btn, .modal-footer, .card-header .btn {
            display: none !important;
        }
        
        .analysis-content {
            background: white;
            padding: 0;
        }
        
        .modal-dialog {
            margin: 0;
            width: 100%;
        }
        
        .modal-content {
            box-shadow: none;
            border: none;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    let currentAnalysisType = null;
    let currentAnalysisData = null;
    let currentAnalysisHTML = null;
    
    function openAIModal(type) {
        currentAnalysisType = type;
        const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
        
        const titles = {
            'summarizer': 'Collection Report Summarizer',
            'trends': 'Face Value Trend Analysis',
            'prediction': 'Exhaustion Prediction'
        };
        
        const subtitles = {
            'summarizer': 'AI-generated summary of collection data',
            'trends': 'Analysis of face value allocation patterns',
            'prediction': 'Prediction of batch exhaustion dates'
        };
        
        document.getElementById('modalTitle').textContent = titles[type] || 'AI Analysis';
        document.getElementById('modalSubtitle').textContent = subtitles[type] || 'AI-powered insights';
        
        modal.show();
        fetchAnalysis(type);
    }
    
    function fetchAnalysis(type) {
        const startDate = getStartDate();
        const endDate = getEndDate();
        const network = document.getElementById('quickNetwork').value;
        const site = document.getElementById('quickSite').value;
        
        let url = `/ai/${type}`;
        let params = [];
        
        if (startDate) params.push(`start_date=${startDate}`);
        if (endDate) params.push(`end_date=${endDate}`);
        if (network) params.push(`network=${network}`);
        if (site) params.push(`site=${site}`);
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        document.getElementById('aiAnalysisContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success" role="status"></div>
                <p class="mt-3 text-muted">Generating AI analysis...</p>
            </div>
        `;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            currentAnalysisData = data;
            const htmlContent = data.analysis || data.summary || '';
            currentAnalysisHTML = htmlContent;
            displayAnalysis(data);
            
            if (htmlContent && htmlContent.length > 0) {
                saveToRecentAnalyses(type, data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('aiAnalysisContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error generating analysis: ${error.message}
                </div>
            `;
        });
    }
    
    function displayAnalysis(data) {
        let htmlContent = '';
        
        if (data.analysis || data.summary) {
            htmlContent = data.analysis || data.summary;
            document.getElementById('aiAnalysisContent').innerHTML = `<div class="analysis-content">${htmlContent}</div>`;
            currentAnalysisHTML = htmlContent;
            if (currentAnalysisData) {
                currentAnalysisData.html = htmlContent;
            }
        } else if (data.error) {
            document.getElementById('aiAnalysisContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${data.message || 'Error generating analysis'}
                </div>
            `;
        } else {
            document.getElementById('aiAnalysisContent').innerHTML = `
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    No analysis data available for the selected filters.
                </div>
            `;
        }
    }
    
    function getStartDate() {
        const range = document.getElementById('quickDateRange').value;
        if (range === 'custom') {
            return document.getElementById('customStartDate').value;
        }
        const date = new Date();
        date.setDate(date.getDate() - parseInt(range));
        return date.toISOString().split('T')[0];
    }
    
    function getEndDate() {
        const range = document.getElementById('quickDateRange').value;
        if (range === 'custom') {
            return document.getElementById('customEndDate').value;
        }
        return new Date().toISOString().split('T')[0];
    }
    
    function updateDateRange() {
        const range = document.getElementById('quickDateRange').value;
        const customStart = document.getElementById('customDateRangeStart');
        const customEnd = document.getElementById('customDateRangeEnd');
        
        if (range === 'custom') {
            customStart.style.display = 'block';
            customEnd.style.display = 'block';
        } else {
            customStart.style.display = 'none';
            customEnd.style.display = 'none';
        }
    }
    
    function saveToRecentAnalyses(type, data) {
        const analyses = JSON.parse(localStorage.getItem('ai_analyses') || '[]');
        const htmlContent = data.analysis || data.summary || '';
        
        analyses.unshift({
            id: Date.now(),
            type: type,
            date: new Date().toISOString(),
            summary: getSummaryPreview(data),
            html: htmlContent
        });
        
        while (analyses.length > 10) analyses.pop();
        localStorage.setItem('ai_analyses', JSON.stringify(analyses));
        loadRecentAnalyses();
    }
    
    function getSummaryPreview(data) {
        const htmlContent = data.analysis || data.summary || '';
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        const text = tempDiv.textContent || tempDiv.innerText || '';
        return text.substring(0, 100) + (text.length > 100 ? '...' : '');
    }
    
    function loadRecentAnalyses() {
        const analyses = JSON.parse(localStorage.getItem('ai_analyses') || '[]');
        const tbody = document.getElementById('recentAnalysesTable');
        
        if (analyses.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2 mb-0">No recent analyses found</p>
                        <small>Run an analysis to see results here</small>
                    </td>
                </tr>
            `;
            return;
        }
        
        const typeNames = {
            'summarizer': 'Report Summary',
            'trends': 'Trend Analysis',
            'prediction': 'Exhaustion Prediction'
        };
        
        tbody.innerHTML = analyses.map(analysis => `
            <tr>
                <td>${new Date(analysis.date).toLocaleString()}</td>
                <td><span class="badge bg-success bg-opacity-10 text-success">${typeNames[analysis.type] || analysis.type}</span></td>
                <td class="text-muted small">${analysis.summary}</td>
                <td>
                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="viewSavedAnalysis(${analysis.id})">
                        <i class="bi bi-eye me-1"></i>View
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    function viewSavedAnalysis(id) {
        const analyses = JSON.parse(localStorage.getItem('ai_analyses') || '[]');
        const analysis = analyses.find(a => a.id === id);
        
        if (analysis && analysis.html) {
            currentAnalysisHTML = analysis.html;
            currentAnalysisData = analysis;
            currentAnalysisType = analysis.type;
            
            const typeNames = {
                'summarizer': 'Collection Report Summarizer',
                'trends': 'Face Value Trend Analysis',
                'prediction': 'Exhaustion Prediction'
            };
            
            document.getElementById('modalTitle').textContent = typeNames[analysis.type] || 'AI Analysis';
            document.getElementById('modalSubtitle').textContent = 'Saved analysis from ' + new Date(analysis.date).toLocaleString();
            document.getElementById('aiAnalysisContent').innerHTML = `<div class="analysis-content">${analysis.html}</div>`;
            
            const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
            modal.show();
        } else {
            alert('Unable to load the saved analysis. The data may be corrupted.');
        }
    }
    
    function refreshAnalyses() {
        loadRecentAnalyses();
    }
    
    function exportAnalysisAsPDF() {
        if (!currentAnalysisHTML) {
            alert('No analysis data to export');
            return;
        }
        
        const exportBtn = event?.target;
        let originalText = '';
        if (exportBtn) {
            originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating PDF...';
            exportBtn.disabled = true;
        }
        
        const pdfContent = `
            <div style="padding: 2rem; font-family: system-ui, -apple-system, sans-serif;">
                <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #10b981;">
                    <h1 style="color: #10b981; margin-bottom: 0.5rem;">AI Analysis Report</h1>
                    <p style="color: #6b7280;">Generated on ${new Date().toLocaleString()}</p>
                    <p style="color: #6b7280;">Report Type: ${document.getElementById('modalTitle').textContent}</p>
                </div>
                <div class="analysis-content">${currentAnalysisHTML}</div>
                <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
                    <p>This report was generated by AI. For support, contact your system administrator.</p>
                </div>
            </div>
        `;
        
        const opt = {
            margin: [0.5, 0.5, 0.5, 0.5],
            filename: `AI_Report_${currentAnalysisType}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '-9999px';
        tempDiv.innerHTML = pdfContent;
        document.body.appendChild(tempDiv);
        
        html2pdf().set(opt).from(tempDiv).save().then(() => {
            document.body.removeChild(tempDiv);
            if (exportBtn) {
                setTimeout(() => {
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                }, 500);
            }
        }).catch(error => {
            console.error('PDF generation error:', error);
            document.body.removeChild(tempDiv);
            alert('Error generating PDF. Please try again.');
            if (exportBtn) {
                setTimeout(() => {
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                }, 500);
            }
        });
    }
    
    function exportAnalysisAsHTML() {
        if (!currentAnalysisHTML) {
            alert('No analysis data to export');
            return;
        }
        
        const exportBtn = event?.target;
        let originalText = '';
        if (exportBtn) {
            originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
            exportBtn.disabled = true;
        }
        
        const htmlContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AI Analysis Report</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; padding: 2rem; max-width: 1200px; margin: 0 auto; background: white; }
        h1 { color: #10b981; }
        h2 { color: #10b981; margin-top: 1.5rem; }
        h3 { color: #3b82f6; }
        .header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #10b981; }
        .footer { text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
        .analysis-content { background: #f8fafc; border-radius: 0.5rem; padding: 1.5rem; line-height: 1.6; }
        .stat-box { background: #f9fafb; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border-left: 4px solid #10b981; }
        .success-box { background: #ecfdf5; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border-left: 4px solid #10b981; }
        .warning-box { background: #fffbeb; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border-left: 4px solid #f59e0b; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th { background: linear-gradient(135deg, #10b981, #3b82f6); color: white; padding: 0.75rem; text-align: left; }
        td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Analysis Report</h1>
        <p>Generated on ${new Date().toLocaleString()}</p>
        <p>Report Type: ${document.getElementById('modalTitle').textContent}</p>
    </div>
    <div class="analysis-content">${currentAnalysisHTML}</div>
    <div class="footer">
        <p>This report was generated by AI. For support, contact your system administrator.</p>
    </div>
</body>
</html>`;
        
        const blob = new Blob([htmlContent], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `AI_Report_${currentAnalysisType}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.html`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        if (exportBtn) {
            setTimeout(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }, 500);
        }
    }
    
    // Load sites when network changes
    document.getElementById('quickNetwork').addEventListener('change', function() {
        const networkId = this.value;
        const siteSelect = document.getElementById('quickSite');
        
        if (networkId) {
            siteSelect.innerHTML = '<option value="">Loading...</option>';
            fetch('/get-sites-by-network/' + networkId)
                .then(response => response.json())
                .then(data => {
                    siteSelect.innerHTML = '<option value="">All Sites</option>';
                    data.forEach(site => {
                        siteSelect.innerHTML += `<option value="${site.id}">${site.site_name}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading sites:', error);
                    siteSelect.innerHTML = '<option value="">All Sites</option>';
                });
        } else {
            siteSelect.innerHTML = '<option value="">All Sites</option>';
        }
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadRecentAnalyses();
        updateDateRange();
    });
</script>
@endpush