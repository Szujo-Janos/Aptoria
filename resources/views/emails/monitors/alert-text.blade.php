Aptoria monitor alert

Project: {{ $payload['project'] ?? '-' }}
Monitor: {{ $payload['monitor'] ?? '-' }}
Status: {{ $payload['status'] ?? '-' }}
Previous status: {{ $payload['previous_status'] ?? '-' }}
Severity: {{ $payload['severity'] ?? '-' }}
Message: {{ $payload['message'] ?? '-' }}
Trigger summary: {{ $payload['trigger_summary'] ?? '-' }}
Triggered at: {{ $payload['triggered_at'] ?? '-' }}
Next run: {{ $payload['next_run_at'] ?? '-' }}

Review the monitor in the Aptoria dashboard for scan, snapshot and compare evidence.
