# Aptoria v0.0.52 – QA Cockpit / Coverage / Blind Spot Foundation

## Purpose

The QA Cockpit is the first central decision view that explains whether a project has enough evidence for a release decision.

It is not a Postman, Jira or Newman clone. It does not execute requests, replace issue management or run external test collections. It collects the outputs from those tools and from Aptoria's own modules, then shows what is missing before a release gate.

## Inputs

The cockpit reads from existing Aptoria data:

- endpoint inventory
- safe scan results
- quick endpoint test runs
- native test cases and test runs
- evidence repository records
- verified evidence state
- open findings
- release readiness runs
- release gates
- evidence packs and report versions

## Core outputs

- QA confidence score
- coverage signal bars
- blind spot list
- endpoint coverage matrix
- latest readiness/gate pointers

## Coverage signals

- Safe scan coverage: safe endpoints that have scan results.
- Quick test coverage: endpoints with quick test execution evidence.
- Native test coverage: endpoints linked to native Aptoria test cases.
- Repository evidence coverage: endpoints with non-archived repository evidence.
- Verified evidence ratio: verified evidence compared to total active evidence.

## Blind spot examples

- no endpoint inventory
- no safe scan evidence
- no test evidence
- no repository evidence
- no verified evidence
- open high/critical findings
- failed or blocked native test runs
- no readiness run
- no release gate
- no evidence pack or fixed report export

## Design rule

The cockpit is a read model. It should not become a separate issue tracker or test runner. Its job is to show what existing proof exists, what is missing, and what action should happen next.
