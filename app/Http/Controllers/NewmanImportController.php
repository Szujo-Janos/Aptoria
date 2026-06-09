<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Imports\NewmanImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class NewmanImportController extends Controller
{
    public function create(Project $project): View
    {
        return view('newman_import.create', [
            'project' => $project,
            'sampleJsonPayload' => $this->sampleJsonPayload(),
            'sampleJUnitPayload' => $this->sampleJUnitPayload(),
        ]);
    }

    public function preview(Request $request, Project $project, NewmanImportService $importer): View
    {
        $input = $this->validated($request);
        $payload = $this->payload($input);

        try {
            $preview = $importer->preview($project, (string) $input['format'], $payload);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payload' => $exception->getMessage()])->withInput();
        }

        return view('newman_import.preview', [
            'project' => $project,
            'preview' => $preview,
            'input' => $input + ['payload' => $payload],
        ]);
    }

    public function store(Request $request, Project $project, NewmanImportService $importer): RedirectResponse
    {
        $input = $this->validated($request);
        $payload = $this->payload($input);

        try {
            $summary = $importer->import($project, (string) $input['format'], $payload, (bool) ($input['create_findings'] ?? true));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payload' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('projects.test-execution.index', $project)
            ->with('success', __('messages.newman_import.imported', $summary));
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'format' => ['required', Rule::in(['json', 'junit'])],
            'payload' => ['nullable', 'string', 'max:500000'],
            'payload_encoded' => ['nullable', 'string', 'max:700000'],
            'create_findings' => ['nullable', 'boolean'],
        ]);
    }

    /** @param array<string,mixed> $input */
    private function payload(array $input): string
    {
        if (trim((string) ($input['payload'] ?? '')) !== '') {
            return (string) $input['payload'];
        }
        if (! empty($input['payload_encoded'])) {
            $decoded = base64_decode((string) $input['payload_encoded'], true);
            if ($decoded !== false) {
                return $decoded;
            }
        }
        throw new RuntimeException(__('validation.required', ['attribute' => 'payload']));
    }

    private function sampleJsonPayload(): string
    {
        return <<<'JSON'
{
  "collection": {"info": {"name": "Demo Newman Collection"}},
  "run": {
    "executions": [
      {
        "item": {"name": "List users", "path": ["Users", "List users"]},
        "request": {"method": "GET", "url": {"raw": "https://jsonplaceholder.typicode.com/users", "path": ["users"]}},
        "response": {"code": 200, "status": "OK", "responseTime": 143},
        "assertions": [
          {"assertion": "Status code is 200"},
          {"assertion": "Response time is below 1000ms"}
        ]
      },
      {
        "item": {"name": "Read missing user", "path": ["Users", "Read missing user"]},
        "request": {"method": "GET", "url": {"raw": "https://jsonplaceholder.typicode.com/users/999999", "path": ["users", "999999"]}},
        "response": {"code": 404, "status": "Not Found", "responseTime": 90},
        "assertions": [
          {"assertion": "Status code is 200", "error": {"message": "expected 404 to equal 200"}}
        ]
      }
    ]
  }
}
JSON;
    }

    private function sampleJUnitPayload(): string
    {
        return <<<'XML'
<testsuites>
  <testsuite name="Demo Newman Collection" tests="2" failures="1">
    <testcase classname="Users" name="GET /users" time="0.143" />
    <testcase classname="Users" name="GET /users/999999" time="0.090">
      <failure message="Status code is 200">expected 404 to equal 200</failure>
    </testcase>
  </testsuite>
</testsuites>
XML;
    }
}
