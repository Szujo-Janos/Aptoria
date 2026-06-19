# Aptoria Import Adapter Layer

## Purpose

Aptoria must not become a Postman, Newman or Jira clone. The import layer exists to keep those tools useful while converting their output into Aptoria's own evidence-first QA model.

The adapter layer does four things:

1. Accepts external artifacts from known QA/dev tools.
2. Normalizes them into Aptoria entities.
3. Detects duplicates, updates, conflicts and review-needed items before applying anything.
4. Preserves traceability so imported records can be audited and reverted when safe.

## Supported adapter inputs in v0.0.48

| Adapter | Input | Normalized output |
|---|---|---|
| Postman collection | JSON collection | Endpoints, assertion rules |
| Newman JSON | JSON run result | Endpoints, assertions, failed-assertion findings, execution evidence |
| Jira CSV | Issue export CSV | Findings, evidence notes |
| Jira JSON | Issue export JSON | Findings, evidence notes |
| OpenAPI JSON | OpenAPI 3.x / Swagger-style JSON with `paths` | Endpoints, assertions, contract evidence, contract findings |
| Generic QA CSV | Flexible QA/test/evidence CSV | Endpoints, assertions, findings, evidence/test-result evidence |
| Browser HAR JSON | HAR log export | Endpoints, HTTP evidence, HTTP-error findings |

## Normalized Aptoria model

External data must never be applied as a raw mirror of the source tool. The target is always one of these Aptoria records:

- `Endpoint`
- `EndpointAssertionRule`
- `Finding`
- `FindingEvidence`

This keeps Aptoria independent while still allowing external tooling to feed it.

## Generic QA CSV columns

The QA CSV adapter accepts flexible column names. Common supported columns:

```text
entity,type,kind,method,path,url,operation,title,name,summary,description,
result,status,outcome,severity,priority,expected,actual,expected_result,
actual_result,steps,reproduction_steps,recommendation,source_label,content,
request,response,request_excerpt,response_excerpt,auth_required,expected_status,
expected_content_type,tags,owner,assignee,external_key,key,id
```

Typical rows:

```csv
entity,method,path,title,result,severity,expected,actual,source_label
test_result,GET,/v1/customers,List customers smoke test,fail,high,HTTP 200,HTTP 500,Manual QA
evidence,GET,/v1/health,Health endpoint smoke proof,pass,low,HTTP 200,HTTP 200,Manual QA
```

Failing `test_result` rows create both:

- a `Finding` with `source=test_case`
- a `FindingEvidence` record with `type=test_result`

## OpenAPI adapter behavior

The OpenAPI adapter reads the `paths` object and creates:

- endpoint inventory rows from operations;
- status-code assertions from preferred 2xx responses;
- content-type assertions when response content is declared;
- contract evidence with the operation payload;
- a medium finding when an operation has no `responses` object.

Only JSON is supported in v0.0.48. YAML support can be added later if a YAML parser dependency is accepted.

## Apply safety

The import layer keeps the existing safety rules:

- preview first;
- conflict rows block apply;
- duplicate rows are skipped;
- applied records are traceable;
- undo can delete created records or restore updated records when original payload snapshots exist;
- imported evidence is stored through the Evidence Repository checksum/lifecycle path.

## UI rules

The import creation flow must remain a full page, not a large modal. It is a complex intake form and must follow Aptoria's form standard:

- card/panel shell;
- short explanation;
- sectioned fields;
- labels, placeholders, help text and validation feedback;
- footer Save/Cancel actions;
- semantic icons per adapter.

## Future adapter candidates

Future adapters should be added only if they normalize into Aptoria records and do not turn Aptoria into a clone of the source product.

Possible future inputs:

- Playwright JSON summary;
- Cypress JSON/JUnit XML;
- GitHub Issues JSON;
- GitLab Issues JSON;
- generic JUnit XML;
- HTTP archive with environment mapping.
