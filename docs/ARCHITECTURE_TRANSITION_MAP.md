# Architecture transition map

This document summarizes the transition from the archived Aptoria `1.1.34` line to the current `0.0.x` evidence-first rebuild.

## Strategy

The current package is a fresh replacement. It does not attempt to migrate the legacy database or preserve the old UI structure. The goal is to keep the useful product idea while rebuilding the application around API QA evidence, release gates, decision packages and a cleaner self-hosted runtime model.

## High-level comparison

| Area | Legacy `1.1.34` line | Current `0.0.x` rebuild |
| --- | --- | --- |
| Product focus | Broad legacy workflow application | Evidence-first API QA and release decision platform |
| UI direction | Older HOMER-era interface | New Aptoria UI with dedicated QA workflow screens |
| Installation | Legacy local install flow | Setup wizard, XAMPP update scripts and public hygiene rules |
| Database | Legacy schema | Fresh Laravel migrations for projects, endpoints, evidence, findings and release gates |
| Evidence model | Limited / mixed workflow artifacts | Checksum-backed Evidence Repository and exportable evidence packs |
| API QA model | Not the central product layer | Endpoint inventory, safe scans, imports, native test evidence and QA cockpit |
| Release decision | Not a primary workflow | Release Gate Workflow and decision package exports |
| Access model | Legacy account handling | Project membership and role foundation |
| Licensing direction | Local/runtime protection not central | Local activation package and optional aptoria.dev online authority direction |
| Public repository policy | Not prepared as public source-available package | README, license, notices, hygiene workflow and public-safe documentation |

## Replacement boundaries

The following should be treated as new implementation boundaries:

- project workspace;
- endpoint inventory;
- environment and auth profile foundation;
- safe scan evidence;
- import adapter layer;
- native test evidence;
- evidence repository;
- findings, merge and retest workflow;
- QA cockpit and coverage/blind spot review;
- release gate workflow;
- report and evidence pack exports;
- license activation and optional online authority client direction.

## What is intentionally not migrated

- old runtime state;
- old local `.env` values;
- old SQLite databases;
- old setup lock files;
- generated public storage;
- customer license files;
- private issuer keys;
- cached report/evidence output;
- legacy UI-only assets that are not used by the rebuild.

## Practical rule

Treat `1.1.34` as archived historical code. Treat `0.0.x` as the active public product line.
