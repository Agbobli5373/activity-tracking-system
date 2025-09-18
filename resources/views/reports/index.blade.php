@extends('layouts.app')

@section('title', 'Activity Reports')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Activity Reports</h1>
        <p class="text-gray-600 mt-2">Generate comprehensive reports on activity histories and team performance</p>
    </div>

    <!-- Report Generation Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Generate Report</h2>
        
        <form id="reportForm" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Date Range -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" 
                           id="start_date" 
                           name="start_date" 
                           value="{{ date('Y-m-01') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" 
                           id="end_date" 
                           name="end_date" 
                           value="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" 
                            name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        @foreach($filterOptions['statuses'] as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Creator Filter -->
                <div>
                    <label for="creator_id" class="block text-sm font-medium text-gray-700 mb-1">Creator</label>
                    <select id="creator_id" 
                            name="creator_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Creators</option>
                        @foreach($filterOptions['users'] as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assignee Filter -->
                <div>
                    <label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                    <select id="assignee_id" 
                            name="assignee_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Assignees</option>
                        @foreach($filterOptions['users'] as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select id="priority" 
                            name="priority" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Priorities</option>
                        @foreach($filterOptions['priorities'] as $priority)
                            <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select id="department" 
                            name="department" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Departments</option>
                        @foreach($filterOptions['departments'] as $department)
                            <option value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 pt-4">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Generate Report
                    </span>
                </button>

                <button type="button" 
                        id="exportCsvBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export CSV
                    </span>
                </button>

                <button type="button" 
                        id="exportPdfBtn"
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </span>
                </button>

                <button type="button" 
                        id="exportExcelBtn"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Excel
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-blue-800">Generating report...</span>
        </div>
    </div>

    <!-- Report Results -->
    <div id="reportResults" class="hidden">
        <!-- Statistics Cards -->
        <div id="statisticsCards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Cards will be populated by JavaScript -->
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Activity Trends Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Trends</h3>
                <div style="height: 300px;">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Priority Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority Distribution</h3>
                <div style="height: 300px;">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Additional Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Completion Rate Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">User Completion Rates</h3>
                <div style="height: 300px;">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Overview</h3>
                <div style="height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- User Statistics Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Performance</h3>
            <div class="overflow-x-auto">
                <table id="userStatsTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Activities</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody id="userStatsBody" class="bg-white divide-y divide-gray-200">
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Activities List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activities</h3>
            <div class="overflow-x-auto">
                <table id="activitiesTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesBody" class="bg-white divide-y divide-gray-200">
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <div id="errorMessage" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Error</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p id="errorText"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('reportForm');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const reportResults = document.getElementById('reportResults');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    let trendsChart = null;
    let priorityChart = null;
    let completionChart = null;
    let statusChart = null;

    // Form submission
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport();
    });

    // Export buttons
    document.getElementById('exportCsvBtn').addEventListener('click', () => exportReport('csv'));
    document.getElementById('exportPdfBtn').addEventListener('click', () => exportReport('pdf'));
    document.getElementById('exportExcelBtn').addEventListener('click', () => exportReport('excel'));

    function generateReport() {
        showLoading();
        hideError();
        hideResults();

        const formData = new FormData(reportForm);
        const data = Object.fromEntries(formData.entries());

        fetch('{{ route("reports.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                displayResults(data.data);
            } else {
                showError(data.message || 'Failed to generate report');
            }
        })
        .catch(error => {
            hideLoading();
            showError('An error occurred while generating the report');
            console.error('Error:', error);
        });
    }

    function exportReport(format) {
        const formData = new FormData(reportForm);
        formData.append('format', format);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("reports.export") }}';
        form.style.display = 'none';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);

        // Add form data
        for (let [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function displayResults(reportData) {
        displayStatistics(reportData.statistics);
        displayCharts(reportData.statistics);
        displayUserStats(reportData.statistics.user_statistics);
        displayActivities(reportData.activities);
        showResults();
    }

    function displayStatistics(stats) {
        const cards = [
            { title: 'Total Activities', value: stats.total_activities, color: 'blue' },
            { title: 'Completed', value: stats.completed_activities, color: 'green' },
            { title: 'Pending', value: stats.pending_activities, color: 'yellow' },
            { title: 'Completion Rate', value: stats.completion_rate + '%', color: 'purple' }
        ];

        const container = document.getElementById('statisticsCards');
        container.innerHTML = cards.map(card => `
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600">${card.title}</p>
                        <p class="text-2xl font-bold text-${card.color}-600">${card.value}</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function displayCharts(stats) {
        // Activity Trends Chart with multiple datasets
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        if (trendsChart) trendsChart.destroy();
        
        trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: stats.daily_statistics.map(day => new Date(day.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [
                    {
                        label: 'Total Activities',
                        data: stats.daily_statistics.map(day => day.total_activities),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Completed',
                        data: stats.daily_statistics.map(day => day.completed_activities),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Pending',
                        data: stats.daily_statistics.map(day => day.pending_activities),
                        borderColor: 'rgb(251, 191, 36)',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Activity Trends'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Priority Distribution Chart with enhanced styling
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        if (priorityChart) priorityChart.destroy();
        
        const priorityData = [
            stats.priority_breakdown.high,
            stats.priority_breakdown.medium,
            stats.priority_breakdown.low
        ];
        
        priorityChart = new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: ['High Priority', 'Medium Priority', 'Low Priority'],
                datasets: [{
                    data: priorityData,
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        'rgb(239, 68, 68)',
                        'rgb(251, 191, 36)',
                        'rgb(34, 197, 94)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Priority Distribution'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = priorityData.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // User Completion Rate Chart
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        if (completionChart) completionChart.destroy();
        
        const topUsers = stats.user_statistics.slice(0, 10); // Show top 10 users
        
        completionChart = new Chart(completionCtx, {
            type: 'bar',
            data: {
                labels: topUsers.map(user => user.user_name.length > 15 ? user.user_name.substring(0, 15) + '...' : user.user_name),
                datasets: [{
                    label: 'Completion Rate (%)',
                    data: topUsers.map(user => user.completion_rate),
                    backgroundColor: topUsers.map(user => {
                        if (user.completion_rate >= 80) return 'rgba(34, 197, 94, 0.8)';
                        if (user.completion_rate >= 60) return 'rgba(251, 191, 36, 0.8)';
                        return 'rgba(239, 68, 68, 0.8)';
                    }),
                    borderColor: topUsers.map(user => {
                        if (user.completion_rate >= 80) return 'rgb(34, 197, 94)';
                        if (user.completion_rate >= 60) return 'rgb(251, 191, 36)';
                        return 'rgb(239, 68, 68)';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top User Performance'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();
        
        statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Completed', 'Pending'],
                datasets: [{
                    data: [stats.completed_activities, stats.pending_activities],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 191, 36, 0.8)'
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(251, 191, 36)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Overall Status Distribution'
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = stats.total_activities;
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function displayUserStats(userStats) {
        const tbody = document.getElementById('userStatsBody');
        tbody.innerHTML = userStats.map(user => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.user_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.department || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.total_activities}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">${user.completed_activities}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">${user.pending_activities}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.completion_rate}%</td>
            </tr>
        `).join('');
    }

    function displayActivities(activities) {
        const tbody = document.getElementById('activitiesBody');
        tbody.innerHTML = activities.map(activity => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <a href="/activities/${activity.id}" class="text-blue-600 hover:text-blue-900">${activity.name}</a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${activity.status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${activity.status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getPriorityColor(activity.priority)}">
                        ${activity.priority}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${activity.creator ? activity.creator.name : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${activity.assignee ? activity.assignee.name : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(activity.created_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
    }

    function getPriorityColor(priority) {
        switch(priority) {
            case 'high': return 'bg-red-100 text-red-800';
            case 'medium': return 'bg-yellow-100 text-yellow-800';
            case 'low': return 'bg-green-100 text-green-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    function showLoading() {
        loadingIndicator.classList.remove('hidden');
    }

    function hideLoading() {
        loadingIndicator.classList.add('hidden');
    }

    function showResults() {
        reportResults.classList.remove('hidden');
    }

    function hideResults() {
        reportResults.classList.add('hidden');
    }

    function showError(message) {
        errorText.textContent = message;
        errorMessage.classList.remove('hidden');
    }

    function hideError() {
        errorMessage.classList.add('hidden');
    }
});
</script>
@endsection