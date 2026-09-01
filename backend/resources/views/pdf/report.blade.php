<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>{{ $project->name }} Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 26px;
            background: #ffffff;
        }
        .header {
            border-bottom: 3px solid {{ $secondaryColor }};
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .org-name {
            font-size: 20px;
            font-weight: 700;
            color: {{ $primaryColor }};
        }
        .meta {
            margin-top: 14px;
            font-size: 12px;
            color: #475569;
        }
        .logo {
            max-height: 64px;
            max-width: 180px;
            object-fit: contain;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            margin: 0 0 8px;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: {{ $primaryColor }};
        }
        .stats {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .stats td {
            width: 25%;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px;
            font-size: 12px;
        }
        .stat-value {
            display: block;
            font-size: 20px;
            font-weight: 700;
            color: {{ $primaryColor }};
            margin-bottom: 4px;
        }
        .narrative {
            font-size: 12px;
            line-height: 1.7;
        }
        .narrative p {
            margin: 0 0 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: {{ $secondaryColor }};
            color: #fff;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div>
                <div class="org-name">{{ $organization->name }}</div>
                <div class="meta">{{ $project->name }} · {{ $report->type }} · {{ $report->period_start }} to {{ $report->period_end }}</div>
            </div>
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" class="logo" alt="Organisation logo" />
            @endif
        </div>
    </div>

    <div class="section">
        <div class="badge">Project overview</div>
        <div class="stats">
            <tr>
                <td><span class="stat-value">{{ $report->auto_stats['total_submissions'] ?? 0 }}</span> Entries</td>
                <td><span class="stat-value">{{ $report->auto_stats['unique_respondents'] ?? 0 }}</span> Unique respondents</td>
                <td><span class="stat-value">{{ $report->auto_stats['average_vulnerability_score'] ?? '—' }}</span> Avg. vuln score</td>
                <td><span class="stat-value">{{ count($report->auto_stats['by_village_top10'] ?? []) }}</span> Villages reached</td>
            </tr>
        </div>
    </div>

    <div class="section">
        <h3>Summary</h3>
        <div class="narrative">
            <p>{{ $report->narrative['summary'] ?? 'No summary entered yet.' }}</p>
        </div>
    </div>

    <div class="section">
        <h3>Key achievements</h3>
        <div class="narrative">
            <p>{{ $report->narrative['achievements'] ?? 'No achievements added yet.' }}</p>
        </div>
    </div>

    <div class="section">
        <h3>Challenges encountered</h3>
        <div class="narrative">
            <p>{{ $report->narrative['challenges'] ?? 'No challenges recorded yet.' }}</p>
        </div>
    </div>

    <div class="section">
        <h3>Lessons learned</h3>
        <div class="narrative">
            <p>{{ $report->narrative['lessons_learned'] ?? 'No lessons learned logged yet.' }}</p>
        </div>
    </div>

    <div class="section">
        <h3>Planned next steps</h3>
        <div class="narrative">
            <p>{{ $report->narrative['next_steps'] ?? 'No next steps added yet.' }}</p>
        </div>
    </div>
</body>
</html>
