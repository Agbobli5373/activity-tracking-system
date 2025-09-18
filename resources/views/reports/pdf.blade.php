<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .header p {
            color: #6b7280;
            margin: 5px 0;
        }
        
        .period-info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .period-info h3 {
            margin: 0 0 10px 0;
            color: #374151;
        }
        
        .statistics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            width: 22%;
            margin-bottom: 10px;
        }
        
        .stat-card h4 {
            margin: 0 0 5px 0;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h3 {
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th,
        table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        
        table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-done {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .priority-high {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .priority-medium {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .priority-low {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Activity Report</h1>
        <p>Generated on {{ date('F j, Y \a\t g:i A') }}</p>
        @if(isset($filters) && !empty(array_filter($filters)))
            <p>Applied Filters: 
                @if(!empty($filters['status'])) Status: {{ ucfirst($filters['status']) }} @endif
                @if(!empty($filters['priority'])) Priority: {{ ucfirst($filters['priority']) }} @endif
                @if(!empty($filters['department'])) Department: {{ $filters['department'] }} @endif
            </p>
        @endif
    </div>

    <div class="period-info">
        <h3>Report Period</h3>
        <p><strong>From:</strong> {{ \Carbon\Carbon::parse($period['start_date'])->format('F j, Y') }}</p>
        <p><strong>To:</strong> {{ \Carbon\Carbon::parse($period['end_date'])->format('F j, Y') }}</p>
    </div>

    <div class="statistics">
        <div class="stat-card">
            <h4>Total Activities</h4>
            <div class="value">{{ $statistics['total_activities'] }}</div>
        </div>
        <div class="stat-card">
            <h4>Completed</h4>
            <div class="value">{{ $statistics['completed_activities'] }}</div>
        </div>
        <div class="stat-card">
            <h4>Pending</h4>
            <div class="value">{{ $statistics['pending_activities'] }}</div>
        </div>
        <div class="stat-card">
            <h4>Completion Rate</h4>
            <div class="value">{{ $statistics['completion_rate'] }}%</div>
        </div>
    </div>

    @if(!empty($statistics['user_statistics']))
    <div class="section">
        <h3>User Performance Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Department</th>
                    <th>Total</th>
                    <th>Completed</th>
                    <th>Pending</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['user_statistics'] as $user)
                <tr>
                    <td>{{ $user['user_name'] }}</td>
                    <td>{{ $user['department'] ?? 'N/A' }}</td>
                    <td>{{ $user['total_activities'] }}</td>
                    <td>{{ $user['completed_activities'] }}</td>
                    <td>{{ $user['pending_activities'] }}</td>
                    <td>{{ $user['completion_rate'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($statistics['priority_breakdown']))
    <div class="section">
        <h3>Priority Distribution</h3>
        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = $statistics['total_activities'];
                @endphp
                <tr>
                    <td><span class="status-badge priority-high">High</span></td>
                    <td>{{ $statistics['priority_breakdown']['high'] }}</td>
                    <td>{{ $total > 0 ? round(($statistics['priority_breakdown']['high'] / $total) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td><span class="status-badge priority-medium">Medium</span></td>
                    <td>{{ $statistics['priority_breakdown']['medium'] }}</td>
                    <td>{{ $total > 0 ? round(($statistics['priority_breakdown']['medium'] / $total) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td><span class="status-badge priority-low">Low</span></td>
                    <td>{{ $statistics['priority_breakdown']['low'] }}</td>
                    <td>{{ $total > 0 ? round(($statistics['priority_breakdown']['low'] / $total) * 100, 1) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    @if($activities->count() > 0)
    <div class="section page-break">
        <h3>Activities Detail</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Creator</th>
                    <th>Assignee</th>
                    <th>Created</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                <tr>
                    <td>{{ $activity->name }}</td>
                    <td>
                        <span class="status-badge status-{{ $activity->status }}">
                            {{ ucfirst($activity->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge priority-{{ $activity->priority }}">
                            {{ ucfirst($activity->priority) }}
                        </span>
                    </td>
                    <td>{{ $activity->creator->name ?? 'N/A' }}</td>
                    <td>{{ $activity->assignee->name ?? 'N/A' }}</td>
                    <td>{{ $activity->created_at->format('M j, Y') }}</td>
                    <td>{{ $activity->due_date ? $activity->due_date->format('M j, Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($statistics['daily_statistics']))
    <div class="section page-break">
        <h3>Daily Activity Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Total</th>
                    <th>Completed</th>
                    <th>Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['daily_statistics'] as $day)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($day['date'])->format('M j, Y') }}</td>
                    <td>{{ $day['day_name'] }}</td>
                    <td>{{ $day['total_activities'] }}</td>
                    <td>{{ $day['completed_activities'] }}</td>
                    <td>{{ $day['pending_activities'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Activity Tracking System - Report generated automatically</p>
        <p>For questions or support, please contact your system administrator</p>
    </div>
</body>
</html>