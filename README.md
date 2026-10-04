<p align="center">
  <img src="assets/trust-engine-banner.svg" alt="Titan Trust Engine — field evidence moves through a governed assurance trail" width="100%" />
</p>

<h1 align="center">Trust-Engine</h1>

<p align="center"><strong>Titan Trust Engine turns field activity into traceable evidence, explainable readiness, and governed review.</strong></p>

<p align="center">PHP/Laravel extension for Titan Zero/MagicAI</p>

<p align="center">
  <a href="#product-overview">Product</a> ·
  <a href="#capabilities">Capabilities</a> ·
  <a href="#architecture">Architecture</a> ·
  <a href="#evidence">Evidence</a> ·
  <a href="#quickstart">Quickstart</a>
</p>

[![Trust signal evaluation](https://github.com/Masterleeaus/Trust-Engine/actions/workflows/trust-signal-eval.yml/badge.svg)](https://github.com/Masterleeaus/Trust-Engine/actions/workflows/trust-signal-eval.yml)

---

## Product overview

Trust-Engine is an evidence-assurance extension for job-based operations. It helps field teams capture proof at the point of work, helps reviewers understand whether the required evidence is present, and preserves the context needed to make an accountable decision.

Built for field-service, operations, compliance, and platform teams, the package connects **files and photos, attendance and presence, client sign-off, incidents, evidence rules, and job timelines** to the host platform's company context and Titan Zero Assurance services. Evidence is not just stored: it can be inspected with its job, capture, file-hash, and trust metadata intact.

The product value is straightforward:

- reviewers see what is required, what was captured, and what is still missing;
- assurance integrations receive canonical evidence references with useful provenance;
- exceptions become incidents, timeline events, and permission-checked review actions;
- host workflows can consume read projections and governed capabilities without turning a trust signal into authority.

## Capabilities

| Capability | What the implementation delivers | Source |
|---|---|---|
| **Evidence capture and integrity** | Captures job, item, incident, and site evidence; records file metadata and SHA-256 hashes; applies MIME and upload-size policy. | [`CaptureController.php`](System/Http/Controllers/CaptureController.php), [`EvidenceController.php`](System/Http/Controllers/EvidenceController.php), [`config/titantrust.php`](config/titantrust.php) |
| **Policy-driven readiness** | Selects the most specific tenant rule by template, job type, and site type, then returns required, captured, missing, and ready values. | [`EvidenceReadiness.php`](System/Services/EvidenceReadiness.php) |
| **Presence and trust signals** | Derives attendance boundaries from evidence captures and evaluates GPS presence, reported accuracy, and source provenance into explicit flags and a coarse level. | [`AttendanceDeriver.php`](System/Services/AttendanceDeriver.php), [`TrustEvaluator.php`](System/Services/TrustEvaluator.php) |
| **Canonical assurance mapping** | Maps evidence, sign-offs, incidents, and attendance into the host's [`EvidenceRef`](System/Assurance/EvidenceRefFactory.php) model; incident signals can be emitted through the assurance bridge. | [`EvidenceRefFactory.php`](System/Assurance/EvidenceRefFactory.php), [`AssuranceBridge.php`](System/Assurance/AssuranceBridge.php) |
| **Review and audit** | Writes typed job events, maintains compliance state, exposes job timelines, and supports incident resolution, sign-off, rules, and manager review. | [`JobEventWriter.php`](System/Audit/JobEventWriter.php), [`ComplianceState.php`](System/Compliance/ComplianceState.php), [`ManagerReviewController.php`](Http/Controllers/ManagerReviewController.php) |
| **Governed host integration** | Publishes risk-classified workforce capabilities and authority-neutral app contributions for the host's workflow and interface layers. | [`workforce-manifest.json`](resources/workforce/workforce-manifest.json), [`interface-manifest.json`](resources/interface/interface-manifest.json) |

## Architecture

<p align="center">
  <img src="assets/trust-engine-architecture.svg" alt="Trust-Engine flow from evidence capture and policy checks through assurance records, human review, and separate governed authority" width="100%" />
</p>

Trust-Engine follows a deliberate path from field input to reviewable assurance:

| Layer | Design choice | Result |
|---|---|---|
| **Capture** | Evidence, attendance, sign-off, and incident records share job and company context. | Reviewers can connect proof to the work it is meant to support. |
| **Evidence policy** | Rules are selected by structured context rather than hidden in free-form instructions. | Readiness is explainable: the response includes requirements, counts, gaps, and a final ready value. |
| **Assurance bridge** | `EvidenceRefFactory` preserves identifiers, hashes, storage references, and job/capture metadata. | Downstream assurance services receive canonical, provenance-rich evidence. |
| **Review and audit** | Typed events, compliance states, timelines, incidents, and explicit authorization work together. | Exceptions remain visible and reviewable instead of disappearing into a binary trust label. |
| **Host boundary** | Workforce and interface manifests describe capabilities, risk, offline behavior, and governed writes. | Composition can happen in the host while domain authority stays with the extension and its permissions. |

## Engineering choices worth noticing

- **Fail-closed tenancy:** `TenantScoped` resolves `company_id` from the host execution context or authenticated company, and blocks scoped reads and writes when no valid context exists.
- **Evidence is not authority:** `TrustAuthorization` checks explicit permissions for review, override, incident resolution, and rule management; capability manifests classify writes as governed domain actions.
- **Explainable policy results:** `EvidenceReadiness` exposes the selected rule and the evidence gap behind `ready`, making the result inspectable rather than opaque.
- **Offline means read-only:** interface contributions allow cached projections, while governed actions remain online and permissioned.
- **Host-compatible installation:** `extension.json` describes PHP/Laravel dependencies, migrations, seeders, named routes, and integrity hashes for the Titan host.

## Integration surface for AI-enabled workflows

Trust-Engine is designed as a reliable evidence and control layer that a host workflow or agent can consume. The workforce manifest exposes concrete capabilities such as `trust.evidence.readiness`, `trust.presence.evaluate`, `trust.incident.resolve`, and `trust.compliance.override` with risk profiles and mutation modes. The interface manifest contributes trust summaries, review requirements, and incident review projections with `executable_ui: false`.

That division keeps the integration useful for AI-enabled operations while keeping domain semantics and authority explicit. The host may compose these projections into its own workflow or agent experience; this repository owns the evidence, policy, provenance, and governed action surface.

## Evidence

### Reproducible trust-signal evaluation

The repository includes a deterministic evaluator for the packaged GPS heuristic. It checks missing coordinates, accuracy thresholds, source-provenance flags, and boundary values. It measures this small signal only; GPS does not establish attendance or truth.

The final PR head was evaluated successfully on **4 October 2026** with PHP 8.2.34 in [CI run 37176074419](https://github.com/Masterleeaus/Trust-Engine/actions/runs/37176074419). The run evaluated merge ref `847efa9dfec546c019396b672e4d99203ae10fa6` from PR head `b08409203dfc9f1e6213837ee47bddf02f4bffb4` and audited base `0497585a786e44125eb3ed9ac554321ff7b1a2f3`.

| Measurement | Result |
|---|---:|
| Trust-level mismatches | 0 / 32 |
| Quality-flag mismatches | 0 / 32 |
| Scenario failures | 0 / 32 |
| High signals with no GPS | 0 / 6 |
| High signals with accuracy over 500 m | 0 / 5 |
| Correct medium signals for 100 m < accuracy <= 500 m | 8 / 8 |
| Missing-accuracy cases flagged `no_accuracy` | 3 / 3 |
| Illustrative always-high/no-flags baseline mismatches | 25 / 32 |

The missing-accuracy cases are intentionally visible as a design diagnostic: coordinates without reported accuracy still receive a high tier while carrying `no_accuracy`. The baseline is an illustrative always-high/no-flags bypass, not a competing product.

Reproduce the evaluator from the repository root:

```bash
php scripts/trust-signal-eval.php
```

The fixed corpus is [`evaluations/trust-signal/scenarios.json`](evaluations/trust-signal/scenarios.json); committed [Markdown results](eval-results/trust-signal-latest.md) and [machine-readable results](eval-results/trust-signal-latest.json) preserve the measured output and scenario hash.

## Quickstart

### Standalone checks

The evaluator and the first two checks require PHP CLI 8.0+ on `PATH`. They use only the PHP standard library; Composer, Laravel, a database, and a host service are not required for these commands.

```bash
php scripts/trust-signal-eval.php
php tests/standalone-agent3-pass6-trust-authorization.php
php tests/standalone-agent3-titan-apps-convergence.php
```

`Tests/Pass4EvidenceConvergenceTest.php` is an extraction-context regression check that expects a preserved donor ZIP in the surrounding package workspace, so it is not advertised as a standalone repository test.

### Install into the Titan host

This repository is an extension package, not a standalone Composer application. The manifest declares PHP `>=8.0`, Laravel `>=8`, and the host `menu` extension. Use the host's extension installer so the provider, routes, migrations, and menus are registered.

For a compatible host using the Artisan workflow:

```bash
php artisan optimize:clear
php artisan module:migrate TitanTrust
php artisan optimize:clear
```

Then open the Trust review, evidence, and incident screens in the host dashboard. Review `config/titantrust.php`, `config/jobs-evidence.php`, `extension.json`, and the workforce manifests against the specific host version before enabling governed writes.

## Repository map

| Path | Role |
|---|---|
| [`System/Services/TrustEvaluator.php`](System/Services/TrustEvaluator.php) | GPS heuristic and explicit quality flags |
| [`System/Services/EvidenceReadiness.php`](System/Services/EvidenceReadiness.php) | Context-specific evidence rule selection and gap counts |
| [`System/Assurance/EvidenceRefFactory.php`](System/Assurance/EvidenceRefFactory.php) | Canonical evidence, sign-off, incident, and presence provenance |
| [`System/Models/Concerns/TenantScoped.php`](System/Models/Concerns/TenantScoped.php) | Fail-closed company execution-context scoping |
| [`System/Security/TrustAuthorization.php`](System/Security/TrustAuthorization.php) | Explicit permission checks for governed review actions |
| [`System/TitanTrustServiceProvider.php`](System/TitanTrustServiceProvider.php) | Host views, translations, routes, migrations, and extension boot |
| [`System/Http/Controllers/`](System/Http/Controllers/) | Capture, evidence, presence, incidents, rules, and sign-off flows |
| [`System/Audit/`](System/Audit/) | Typed job events and timeline queries |
| [`resources/workforce/workforce-manifest.json`](resources/workforce/workforce-manifest.json) | Risk-classified workforce capabilities |
| [`resources/interface/interface-manifest.json`](resources/interface/interface-manifest.json) | Authority-neutral app contributions |
| [`database/migrations/`](database/migrations/) | Evidence, sign-off, attendance, incident, and job-schema migrations |
| [`tests/`](tests/) and [`Tests/`](Tests/) | Standalone checks and extraction-context regression fixture |
| [`PROVENANCE.md`](PROVENANCE.md) | Source extraction and attribution/licensing status |

## Boundaries and source

- Trust-Engine integrates into a compatible Titan Zero/MagicAI Laravel host; it is not a self-contained web application.
- GPS output is a coarse review signal, not proof of attendance, identity, or truth.
- The public client sign-off controller writes signature images to Laravel's `public` storage disk in this v2.1.0 package; review storage exposure against the host's privacy requirements.
- A repository commit does not imply that a deployment-specific Laravel integration test has run.
- The extracted package version is **Titan Trust Master v2.1.0**, recorded in [`extension.json`](extension.json).
- [`PROVENANCE.md`](PROVENANCE.md) records the source extraction and states the current licensing/attribution decision. No license is inferred from the repository's public visibility.

---

**Trust-Engine makes field evidence usable for assurance: connected to the work it supports, clear about what it proves, and governed when it matters.**
